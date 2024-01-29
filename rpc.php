<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2017 University of California
//
// BOINC is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// BOINC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

// AM RPC handler
// request: a list of projects the client is currently attached to,
//      with info about work done for each.
// reply: list of projects to add (or detach when done)
//
// calls:
// do_accounting()
//      update accounting fields of various tables
// choose_projects_rpc() (su_schedule.inc)
//      choose projects that cover all the client's resources
//      rank_projects(): order projects based on prefs, share
//          populate_score_info(): get resources from req
//      select_projects_resource()
//  

// The user account already exists; the host may not.

require_once("../inc/xml.inc");
require_once("../inc/boinc_db.inc");
require_once("../inc/user_util.inc");

require_once("../inc/su_db.inc");
require_once("../inc/su_schedule.inc");
require_once("../inc/su_compute_prefs.inc");
require_once("../inc/su_util.inc");
require_once("../inc/log.inc");

define('REPEAT_DELAY', 86400./2);
    // interval between AM requests
define('REPEAT_DELAY_INITIAL', 30);
    // delay after initial request - enough time for account creation
define('COBBLESTONE_SCALE', 200.*1.e-9/86400.);
    // multiply to convert FLOPS to credit/sec

$in_rpc = true;

// logging options
//
define('LOG_DELTAS', true);

$now = 0;

// return error
//
function su_error($num, $msg) {
    log_write("ERROR: $msg");
    echo "<acct_mgr_reply>
    <error_num>$num</error_num>
    <error_msg>$msg</error_msg>
</acct_mgr_reply>
";
    exit;
}

function send_user_keywords($user) {
    echo '<user_keywords>
';
    $ukws = SUUserKeyword::enum("user_id=$user->id");
    foreach ($ukws as $ukw) {
        if ($ukw->yesno > 0) {
            echo "   <yes>$ukw->keyword_id</yes>\n";
        } else {
            echo "   <no>$ukw->keyword_id</no>\n";
        }
    }
    echo '</user_keywords>
';
}

// return true of URL is in list of accounts
//
function is_in_accounts($url, $accounts) {
    foreach ($accounts as $a) {
        $proj = $a[0];
        if ($url == $proj->url) {
            return true;
        }
    }
    return false;
}

// TODO: move this to boinc/html/inc/util_basic.inc
//
function version_to_int($v) {
    $v = explode(".", $v);
    if (count($v) < 3) return 0;
    return $v[0]*10000 + $v[1]*100 + $v[2];
}

function send_error_reply($msg) {
    echo "<acct_mgr_reply>
    <error_msg>$msg</error_msg>
</acct_mgr_reply>
";
}

// $accounts is an array of array(project, account)
//
function send_reply($user, $host, $req, $accounts) {
    echo "<acct_mgr_reply>\n"
        ."<name>".PROJECT."</name>\n"
        ."<authenticator>$user->authenticator</authenticator>\n"
        ."<signing_key>\n"
    ;
    readfile('code_sign_public');
    $repeat_sec = REPEAT_DELAY;
    echo "</signing_key>\n"
        ."<repeat_sec>$repeat_sec</repeat_sec>\n"
        ."<dynamic/>\n"
    ;
    echo "<user_name>".htmlentities($user->name)."</user_name>\n";
    if ($user->teamid) {
        $team = BoincTeam::lookup($user->teamid);
        if ($team) {
            echo "<team_name>".htmlentities($team->name)."</team_name>\n";
        }
    }
    send_user_keywords($user);
    echo expand_compute_prefs($user->global_prefs);

    // tell client which projects to attach to
    //
    foreach ($accounts as $a) {
        $proj = $a[0];
        $acct = $a[1];
        echo "<account>\n"
            ."   <url>$proj->url</url>\n"
            ."   <url_signature>\n$proj->url_signature\n</url_signature>\n"
            ."   <authenticator>$acct->authenticator</authenticator>\n"
        ;

        // tell client which processing resources to use for this project
        //
        foreach ($host->resources as $r) {
            if (!$proj->use[$r]) {
                echo "   <no_rsc>$r</no_rsc>\n";
            }
        }
        echo "</account>\n";
    }

    // tell client to detach from other projects it's currently attached to,
    // (that we know about, and with attached_via_acct_mgr set)
    // TODO: leave attached to those with large disk usage,
    // but set resource share to zero.
    //
    foreach ($req->project as $rp) {
        if (!(int)$rp->attached_via_acct_mgr) {
            continue;
        }
        $url = BoincDb::escape_string((string)$rp->url);
        if (is_in_accounts($url, $accounts)) {
            continue;
        }
        if (0) {
            $project = SUProject::lookup("url='$url'");
            if (!$project) {
                continue;
            }
        }
        log_write("sending detach from $url");
        echo "<account>\n"
            ."   <url>$url</url>\n"
            ."   <dont_request_more_work/>\n"
            ."   <detach_when_done/>\n"
            ."</account>\n"
        ;
    }

    echo "   <opaque><host_id>$host->id</host_id></opaque>\n"
        ."</acct_mgr_reply>\n"
    ;
}

function make_serialnum($req) {
    $x = sprintf("[BOINC|%s]", (string)($req->client_version));

    $c = $req->host_info->coprocs->coproc_cuda;
    if ($c) {
        $x .= sprintf("[CUDA|%s|%d|%dMB]",
            (string)$c->name,
            (int)$c->count,
            ((double)$c->totalGlobalMem)/MEGA
        );
    }
    $c = $req->host_info->coprocs->coproc_ati;
    if ($c) {
        $x .= sprintf("[CAL|%s|%d|%dMB]",
            (string)$c->name, (int)$c->count,
            (int)$c->localRAM
        );
    }
    $c = $req->host_info->coprocs->coproc_intel_gpu;
    if ($c) {
        $x .= sprintf("[INTEL|%s|%d|%dMB]",
            (string)$c->name, (int)$c->count,
            (int)$c->global_mem_size/MEGA
        );
    }

    // other OpenCL GPUs
    foreach ($req->host_info->coprocs->coproc as $y) {
        if (empty($y->coproc_opencl)) continue;
        $co = $y->coproc_opencl;
        $x .= sprintf("[%s|%s|1|%dMB]",
            $co->vendor, $co->name,
            (int)$co->global_mem_size/MEGA
        );
    }

    $v = (string)$req->host_info->virtualbox_version;
    if ($v) {
        $x .= sprintf("[vbox|%s]", $v);
    }
    return $x;
}

// update the host DB record
// Note: we update rpc_time in the DB, but not the object
//
function update_host($req, $host) {
    //$_SERVER = array();
    //$_SERVER['REMOTE_ADDR'] = "12.4.2.11";
    global $now;

    $hi = $req->host_info;
    $ts = $req->time_stats;
    $ns = $req->net_stats;

    // request message is from client, so don't trust it
    //
    $boinc_client_version = BoincDb::escape_string((string)$req->client_version);
    $n_usable_coprocs = (int)$hi->n_usable_coprocs;
    $timezone = (int)$hi->timezone;
    $domain_name = BoincDb::escape_string((string)$hi->domain_name);
    $host_cpid = BoincDb::escape_string((string)$hi->host_cpid);
    $serialnum = make_serialnum($req);
    $ip_addr = BoincDb::escape_string((string)$hi->ip_addr);
    $external_ip_addr = BoincDb::escape_string($_SERVER['REMOTE_ADDR']);
    $p_ncpus = (int)$hi->p_ncpus;
    $p_vendor = BoincDb::escape_string((string)$hi->p_vendor);
    $p_model = BoincDb::escape_string((string)$hi->p_model);
    $p_features = BoincDb::escape_string((string)$hi->p_features);
    $p_fpops = (double)$hi->p_fpops;
    $p_iops = (double)$hi->p_iops;
    $p_vm_extensions_disabled = (int)$hi->p_vm_extensions_disabled;
    $m_nbytes = (double)$hi->m_nbytes;
    $m_cache = (double)$hi->m_cache;
    $m_swap = (double)$hi->m_swap;
    $d_total = (double)$hi->d_total;
    $d_free = (double)$hi->d_free;
    $os_name = BoincDb::escape_string((string)$hi->os_name);
    $os_version = BoincDb::escape_string((string)$hi->os_version);
    $virtualbox_version = BoincDb::escape_string((string)$hi->virtualbox_version);

    $on_frac = (double)$ts->on_frac;
    $connected_frac = (double)$ts->connected_frac;
    $active_frac = (double)$ts->active_frac;

    $n_bwup = $ns->bwup;
    $n_bwdown = $ns->bwdown;

    $p_ngpus = 0;
    $p_gpu_fpops = 0;
    foreach ($hi->coprocs->children() as $c) {
        $p_ngpus += (int)$c->count;
        $p_gpu_fpops += (double)$c->peak_flops;
    }

    // we use total_credit as a "hidden" flag

    $query = "
        total_credit=0,
        boinc_client_version = '$boinc_client_version',
        virtualbox_version = '$virtualbox_version',
        rpc_time = $now,
        timezone = $timezone,
        domain_name = '$domain_name',
        host_cpid = '$host_cpid',
        serialnum = '$serialnum',
        last_ip_addr = '$ip_addr',
        external_ip_addr = '$external_ip_addr',
        p_ncpus = $p_ncpus,
        p_vendor = '$p_vendor',
        p_model = '$p_model',
        p_features = '$p_features',
        p_fpops = $p_fpops,
        p_iops = $p_iops,
        p_vm_extensions_disabled = $p_vm_extensions_disabled,
        m_nbytes = $m_nbytes,
        m_cache = $m_cache,
        m_swap = $m_swap,
        d_total = $d_total,
        d_free = $d_free,
        os_name = '$os_name',
        os_version = '$os_version',
        on_frac = $on_frac,
        connected_frac = $connected_frac,
        active_frac = $active_frac,
        n_bwup = $n_bwup,
        n_bwdown = $n_bwdown,
        p_ngpus = $p_ngpus,
        p_gpu_fpops = $p_gpu_fpops,
        rpc_seqno = rpc_seqno+1
    ";
    $ret = $host->update($query);
    if (!$ret) {
        su_error(-1, "host update failed: $host->id $query");
    }
}

function create_host($req, $user) {
    $now = time();
    $id = BoincHost::insert(
        "(create_time, userid) values ($now, $user->id)"
    );
    $host = BoincHost::lookup_id($id);
    update_host($req, $host);
    return $host;
}

function similar_host($h, $req) {
    $hi = $req->host_info;
    if ($h->host_cpid != (string)$hi->host_cpid) return false;
    if ($h->domain_name != (string)$hi->domain_name) return false;
    return true;
}

// look up user and host records; create host if needed
//
function lookup_records($req) {
    $authenticator = (string)$req->authenticator;
    if ($authenticator) {
        $user = BoincUser::lookup_auth($authenticator);
        if (!$user) {
            log_write("auth $authenticator not found");
            su_error(-1, 'no account found');
        }
    } else {
        $email_addr = (string)$req->name;
        $user = BoincUser::lookup_email_addr($email_addr);
        if (!$user) {
            log_write("account $email_addr not found");
            su_error(-1, 'no account found');
        }
    }

    if (!$authenticator) {
        $passwd_hash = (string)$req->password_hash;
        if (!check_passwd_hash($user, $passwd_hash)) {
            su_error(-1, 'bad password');
        }
    }

    if (array_key_exists('opaque', $req)) {
        $host_id = (int)$req->opaque->host_id;
        $host = BoincHost::lookup_id($host_id);
        if ($host) {
            log_write("Found host $host_id specified by request");
        } else {
            log_write("No such host $host_id specified by request");
        }
    } else {
        // This host might be re-attaching to the AM.
        // See if there's an existing host record that matches this host
        //
        $host = null;
        $hosts = BoincHost::enum("userid=$user->id");
        foreach ($hosts as $h) {
            if (similar_host($h, $req)) {
                $host = $h;
                break;
            }
        }
        if ($host) {
            log_write("No host specified in request, but found similar $host->id");
        } else {
            log_write("No host specified in request, and no similar host");
        }
    }
    if ($host) {
        update_host($req, $host);
    } else {
        $host = create_host($req, $user);
        log_write("Created new host $host->id");
    }
    return array($user, $host);
}

// sanity-check limits on #s and speed of CPU cores, GPUs
//
// TODO: use BOINC code for these values when it gets merged
//
define("MAX_CPU_INST", 256);
define("DEFAULT_CPU_INST", 4);
define("MAX_CPU_FLOPS", 100.e9);     // max 100 GFLOPS per CPU
define("DEFAULT_CPU_FLOPS", 1.e9);     // default 1 GFLOPS per CPU
define("MAX_GPU_INST", 8);
define("DEFAULT_GPU_INST", 1);
define("MAX_GPU_FLOPS", 100e12);    // max 100 TFLOPS per GPU
define("DEFAULT_GPU_FLOPS", 100e9);    // default 100 GFLOPS per GPU
define("MAX_JOBS_PER_RPC", 5000);
define("DEFAULT_JOBS_PER_RPC", 10);

function check_ncpus($host) {
    if ($host->p_ncpus > MAX_CPU_INST) {
        log_write("Warning: too many CPUs: $host->p_ncpus.  Setting to ".DEFAULT_CPU_INST);
        return DEFAULT_CPU_INST;
    }
    return $host->p_ncpus;
}

function check_ngpus($host) {
    if ($host->p_ngpus > MAX_GPU_INST) {
        log_write("Warning: too many GPUs: $host->p_ngpus.  Setting to ".DEFAULT_GPU_INST);
        return DEFAULT_GPU_INST;
    }
    return $host->p_ngpus;
}

// check ncpus first
//
function check_cpu_flops($host, $ncpus) {
    $fpops = $host->p_fpops;
    if ($fpops > MAX_CPU_FLOPS) {
        log_write("Warning: p_fpops is too high: $fpops.  Setting to ".DEFAULT_CPU_FLOPS);
        $fpops = DEFAULT_CPU_FLOPS;
    }
    return $fpops*$ncpus;
}

// check ngpus first
//
function check_gpu_flops($host, $ngpus) {
    $fpops = $host->p_gpu_fpops;
    if ($fpops > MAX_GPU_FLOPS*$ngpus) {
        log_write("Warning: p_gpu_fpops is too high: $fpops.  Setting to ".DEFAULT_GPU_FLOPS);
        $fpops = DEFAULT_GPU_FLOPS*$ngpus;
    }
    return $fpops;
}

// sanity-check a runtime delta (for CPU or GPU time).
// dt is the wall time delta
// check ninst first
//
function check_time_delta($dt, $delta, $ninst, $project) {
    if ($delta < 0) {
        log_write("Warning: $project->name: negative runtime delta: $delta");
        return 0;
    }
    $max_delta = $ninst*$dt;
    if ($delta > $max_delta) {
        log_write("Warning: $project->name: runtime too large: $delta > $ninst * $dt; setting to ".$max_delta);
        return $max_delta;
    }
    return $delta;
}

// sanity-check an EC delta.
// check flops first
//
function check_ec_delta($dt, $delta, $flops, $project) {
    if ($delta < 0) {
        log_write("Warning: $project->name: negative EC delta: $delta");
        return 0;
    }

    $max_rate = $flops*COBBLESTONE_SCALE;
    $max_delta = $max_rate*$dt;
    if ($delta > $max_delta) {
        log_write("Warning: EC delta is too high: $delta > $max_rate * $dt");
        return $max_delta;
    }
    return $delta;
}

// sanity check limit on the # of jobs a host can process in the given time
//
function check_njobs_delta($dt, $njobs, $project) {
    if ($njobs < 0) {
        log_write("Warning: $project->name: #jobs delta is negative: $njobs");
        return 0;
    }
    if ($njobs > MAX_JOBS_PER_RPC) {
        log_write("Warning: #jobs is too large: $njobs > ".MAX_JOBS_PER_RPC);
        return DEFAULT_JOBS_PER_RPC;
    }
    return $njobs;
}

// lookup a project, mod http/https differences
//
function find_project($url) {
    $project = SUProject::lookup("url='$url'");
    if ($project) {
        return $project;
    }
    $url = strstr($url, "//");
    return SUProject::lookup("url like '%$url'");
}

// - compute accounting deltas based on AM request message
// (which lists per-project totals)
// and totals in host/project records.
// - update host/project records
// - add deltas to latest project accounting records
// - add deltas (summed over projects) to latest user accounting record
// - add deltas (summed over projects) to latest global accounting record
//
// Return list of projects
//
function do_accounting(
    $req,           // AM RPC request as simpleXML object
    $user, $host    // user and host records
) {
    global $now;
    if ($host->rpc_time) {
        $dt = $now - $host->rpc_time;
        if ($dt < 0) {
            log_write("Negative dt: now $now last RPC $host->rpc_time");
            return;
        }
        log_write("time since last RPC: $dt");
    } else {
        log_write("First RPC from this host");
        $dt = 0;
    }

    // get sanity-checked values for various params
    //
    $ncpus = check_ncpus($host);
    $ngpus = check_ngpus($host);
    $cpu_flops = check_cpu_flops($host, $ncpus);
    $gpu_flops = check_gpu_flops($host, $ngpus);
    log_write(sprintf("%d CPUs (%f credit/sec); %d GPUs (%f credit/sec)",
        $ncpus,
        $cpu_flops*COBBLESTONE_SCALE,
        $ngpus,
        $gpu_flops*COBBLESTONE_SCALE
    ));
    log_write("disk free: $host->d_free");

    // The client reports totals per project.
    // This includes work prior to joining SU, which could be years.

    // sum_delta is the sum across all projects
    //
    $sum_delta = new_delta_set();

    $projects = array();
    foreach ($req->project as $rp) {
        $url = (string)$rp->url;
        $project = find_project($url);
        if (!$project) {
            log_write("can't find project $url");
            continue;
        }
        $projects[] = $project;

        // get host/project record; create if needed
        //
        $first = false;
        $hp = SUHostProject::lookup(
            "host_id=$host->id and project_id=$project->id"
        );
        if (!$hp) {
            SUHostProject::insert(
                "(host_id, project_id) values ($host->id, $project->id)"
            );
            $hp = SUHostProject::lookup(
                "host_id=$host->id and project_id=$project->id"
            );
            if (!$hp) {
                log_write("CRITICAL: can't create su_host_project");
                return;
            }
            $first = true;
        }

        $rp_cpu_time = (double)$rp->cpu_time;
        $rp_cpu_ec = (double)$rp->cpu_ec;
        $rp_gpu_time = (double)$rp->gpu_time;
        $rp_gpu_ec = (double)$rp->gpu_ec;
        $rp_njobs_success = (int)$rp->njobs_success;
        $rp_njobs_fail = (int)$rp->njobs_error;

        // compute and sanity-check deltas for this project
        //
        $dproj = new_delta_set();
        $d = $rp_cpu_time - $hp->cpu_time;
        $dproj->cpu_time = check_time_delta($dt, $d, $ncpus, $project);
        $d = $rp_cpu_ec - $hp->cpu_ec;
        $dproj->cpu_ec = check_ec_delta($dt, $d, $cpu_flops, $project);
        $d = $rp_gpu_time - $hp->gpu_time;
        $dproj->gpu_time = check_time_delta($dt, $d, $ngpus, $project);
        $d = $rp_gpu_ec - $hp->gpu_ec;
        $dproj->gpu_ec = check_ec_delta($dt, $d, $gpu_flops, $project);
        $d = $rp_njobs_success - $hp->njobs_success;
        $dproj->njobs_success = check_njobs_delta($dt, $d, $project);
        $d = $rp_njobs_fail - $hp->njobs_fail;
        $dproj->njobs_fail = check_njobs_delta($dt, $d, $project);

        // update host/project record (totals)
        //
        $ret = $hp->update("
            cpu_time = $rp_cpu_time,
            cpu_ec = $rp_cpu_ec,
            gpu_time = $rp_gpu_time,
            gpu_ec = $rp_gpu_ec,
            njobs_success = $rp_njobs_success,
            njobs_fail = $rp_njobs_fail,
            project_host_id = $rp->hostid
            where host_id = $host->id and project_id = $project->id
        ");
        if (!$ret) {
            log_write("up->update failed");
            su_error(-1, "hp->update failed");
        }

        // if no changes, we're done with this project
        //
        if (!delta_set_nonzero($dproj)) {
            continue;
        }

        if (LOG_DELTAS) {
            log_write("Deltas for $project->name:");
            log_write_deltas($dproj);
        }

        // update user/project record (totals)
        //
        $a = new SUAccount;
        $a->user_id = $user->id;
        $a->project_id = $project->id;
        $ret = $a->update("cpu_time = cpu_time + $dproj->cpu_time,
            cpu_ec = cpu_ec + $dproj->cpu_ec,
            gpu_time = gpu_time + $dproj->gpu_time,
            gpu_ec = gpu_ec + $dproj->gpu_ec,
            njobs_success = njobs_success + $dproj->njobs_success,
            njobs_fail = njobs_fail + $dproj->njobs_fail
        ");
        if (!$ret) {
            log_write("account->update failed");
            su_error(-1, "account->update failed");
        }

        // add deltas to deltas in project's current accounting record
        //
        $ap = SUAccountingProject::last($project->id);
        $update_string = delta_update_string($dproj);

        // increment host count if first RPC of accounting period
        //
        if ($host->rpc_time < $ap->create_time) {
            $update_string .= ", nhosts = nhosts+1";
        }

        $ret = $ap->update($update_string);
        if (!$ret) {
            log_write("ap->update failed");
            su_error(-1, "ap->update failed");
        }

        // add to all-project delta sums
        //
        $sum_delta = add_delta_set($dproj, $sum_delta);
    }

    if (LOG_DELTAS) {
        log_write("Total deltas:");
        log_write_deltas($sum_delta);
    }

    // update user accounting record with deltas summed over projects;
    // create record if needed
    //
    $au = SUAccountingUser::last($user->id);
    if (!$au) {
        SUAccountingUser::insert(
            "(user_id, create_time) values($user->id, $now)"
        );
        $au = SUAccountingUser::last($user->id);
    }
    if (delta_set_nonzero($sum_delta)) {
        $ret = $au->update(delta_update_string($sum_delta));
        if (!$ret) {
            log_write("au->update failed");
            su_error(-1, "au->update failed");
        }
    }

    // update global accounting record
    //
    if (delta_set_nonzero($sum_delta)) {
        $acc = SUAccounting::last();
        $ret = $acc->update(delta_update_string($sum_delta));
        if (!$ret) {
            log_write("acc->update failed");
            su_error(-1, "acc->update failed");
        }
    }
    return $projects;
}

function am_error_reply($msg) {
    echo "<acct_mgr_reply>
        <error_msg>$msg</error_msg>
        </acct_mgr_reply>
    ";
    exit;
}


function xml_split($x, $tagname) {
    $open_tag = "<$tagname>";
    $close_tag = "</$tagname>";
    $n = strpos($x, $open_tag);
    $m = strpos($x, $close_tag);
    if ($n === FALSE || $m === FALSE) {
        return array($x, null, null);
    }
    $m += strlen($close_tag);
    return array(
        substr($x, 0, $n),
        substr($x, $n, $m-$n),
        substr($x, $m)
    );

}

// Request messages include global prefs,
// which come from projects and may be invalid XML.
// The following function takes a request message and returns
// - the request message minus the <global_preferences>..</global_preferences>
//   part; we assume this is valid XML
// - working global prefs (valid XML)
// - the <global_preferences> part, which may not be valid.
//
function req_split($r) {
    list($a, $b, $c) = xml_split($r, "working_global_preferences");
    list($d, $e, $f) = xml_split($a.$c, "global_preferences");
    return array(
        $d.$f,
        $b,
        $e
    );
}

function log_request($req_xml, $host) {
    $dir = "host_log/$host->id";
    if (!is_dir($dir)) {
        mkdir($dir);
    }
    $date = date(DATE_RFC822);
    file_put_contents("$dir/$date", $req_xml);
}

function main() {
    global $now;
    log_open("../../log_isaac/rpc_log.txt");

    if (1) {
        log_write("Got request from ".$_SERVER['HTTP_USER_AGENT']);
        $req_xml = file_get_contents('php://input');
    } else {
        $req_xml = file_get_contents('req.xml');
    }
    list($a, $b, $c) = req_split($req_xml);
    $req = @simplexml_load_string(utf8_encode($a));
    if (!$req) {
        log_write("can't parse request: $a");
        su_error(-1, "can't parse request");
    }

    $now = time();

    xml_header();   // do this before DB access

    $client_ver = version_to_int((string)$req->client_version);
    if ($client_ver < 71000) {
        send_error_reply("Science United requires a newer version of BOINC.  Please install the current version from https://boinc.berkeley.edu/download.php");
        log_write("client version too low: ".(string)$req->client_version);
        return;
    }

    if (file_exists("stop_sched")) {
        log_write("down for maintenance");
        send_error_reply("Science United is down for maintenance");
        return;
    }

    list($user, $host) = lookup_records($req);
    log_write("processing request from user $user->id host $host->id");

    // update accounting for projects the host is currently running
    //
    $current_projects = do_accounting($req, $user, $host);

    //log_request($req_xml, $host);

    // pick projects to run
    //
    $accounts_to_send = choose_projects_rpc($user, $host, $req);

    // adjust avg_ec as needed
    //
    adjust_avg_ec($current_projects, $accounts_to_send);

    send_reply($user, $host, $req, $accounts_to_send);

    log_write("------------------------------");
}

main();

?>
