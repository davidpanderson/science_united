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
//
// The user account already exists; the host may not.
// this call must return quickly, else the client will hang.
// So what we do is:
// - decide what projects to have the host run
// - for each project for which the user doesn't have an account,
//   create an account record; a periodic task will create the account later
// - return a list of projects for which the user has a working account

require_once("../inc/xml.inc");
require_once("../inc/boinc_db.inc");

require_once("../inc/su_db.inc");
require_once("../inc/su_schedule.inc");
require_once("../inc/su_compute_prefs.inc");
require_once("../inc/su_util.inc");

define('REPEAT_DELAY', 86400./2);
    // interval between AM requests
define('REPEAT_DELAY_INITIAL', 30);
    // delay after initial request - enough time for account creation
define('COBBLESTONE_SCALE', 200./86400e9);

$now = 0;

$log_file = null;
$verbose = true;

function log_write($x) {
    global $verbose, $log_file;

    if (!$verbose) return;
    if (!$log_file) {
        $log_file = fopen("../../log_isaac/rpc_log.txt", "a");
    }
    fwrite($log_file, sprintf("%s: %s\n", date(DATE_RFC822), $x));
}

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

// $accounts is an array of array(project, account)
//
function send_reply($user, $host, $accounts, $new_accounts, $req) {
    echo "<acct_mgr_reply>\n"
        ."<name>".PROJECT."</name>\n"
        ."<authenticator>$user->authenticator</authenticator>\n"
        ."<signing_key>\n"
    ;
    readfile('code_sign_public');
    $repeat_sec = $new_accounts?REPEAT_DELAY_INITIAL:REPEAT_DELAY;
    echo "</signing_key>\n"
        ."<repeat_sec>$repeat_sec</repeat_sec>\n"
    ;
    send_user_keywords($user);
    echo expand_compute_prefs($user->global_prefs);

    // tell client which projects to attach to
    //
    foreach ($accounts as $a) {
        $proj = $a[0];
        log_write("sending attach to project $proj->url");
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
        $project = SUProject::lookup("url='$url'");
        if (!$project) {
            continue;
        }
        log_write("sending detach from $url");
        echo "<account>\n"
            ."   <url>$url</url>\n"
            ."   <url_signature>\n$project->url_signature\n</url_signature>\n"
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

    $v = (string)$req->host_info->virtualbox_version;
    if ($v) {
        $x .= sprintf("[vbox|%s]", $v);
    }
    return $x;
}

function update_host($req, $host) {
    //$_SERVER = array();
    //$_SERVER['REMOTE_ADDR'] = "12.4.2.11";
    global $now;

    $hi = $req->host_info;
    $ts = $req->time_stats;
    $ns = $req->net_stats;

    // request message is from client, so don't trust it
    //
    $boinc_client_version = (string)$req->client_version;
    $n_usable_coprocs = (int)$hi->n_usable_coprocs;
    $timezone = (int)$hi->timezone;
    $domain_name = BoincDb::escape_string((string)$hi->domain_name);
    $host_cpid = BoincDb::escape_string((string)$req->host_cpid);
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
    foreach ($hi->coprocs->coproc as $c) {
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
        p_gpu_fpops = $p_gpu_fpops
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

// look up user and host records; create host if needed
//
function lookup_records($req) {
    $authenticator = (string)$req->authenticator;
    if ($authenticator) {
        $user = BoincUser::lookup_auth($authenticator);
    } else {
        $email_addr = (string)$req->name;
        $user = BoincUser::lookup_email_addr($email_addr);
    }
    if (!$user) {
        log_write("account $email_addr not found");
        su_error(-1, 'no account found');
    }

    if (!$authenticator) {
        $passwd_hash = (string)$req->password_hash;
        if ($passwd_hash != $user->passwd_hash) {
            su_error(-1, 'bad password');
        }
    }

    if (array_key_exists('opaque', $req)) {
        $host_id = (int)$req->opaque->host_id;
        $host = BoincHost::lookup_id($host_id);
    } else {
        // TODO: this host might be re-attaching to the AM.
        // See if there's an existing host record that matches this host
        $host = null;
    }
    if ($host) {
        update_host($req, $host);
    } else {
        $host = create_host($req, $user);
    }
    return array($user, $host);
}

// sanity-check limits on #s and speed of CPU cores, GPUs
//
define("MAX_CPU_INST", 256);
define("MAX_CPU_FLOPS", 100e9);
define("MAX_GPU_INST", 8);
define("MAX_GPU_FLOPS", 100e12);
define("MAX_JOBS_DAY", 10000);

// make sure a time delta (for CPU or GPU time) is legit
// dt is the wall time delta
//
function check_time_delta($dt, $delta, $is_gpu) {
    if ($delta < 0) return 0;
    $max_inst = $is_gpu?MAX_GPU_INST:MAX_CPU_INST;
    $max_delta = $max_inst*$dt;
    return min($delta, $max_delta);
}

// make sure an EC delta is legit
//
function check_ec_delta($dt, $delta, $is_gpu) {
    if ($delta < 0) return 0;
    $max_inst = $is_gpu?MAX_GPU_INST:MAX_CPU_INST;
    $max_rate = $is_gpu?MAX_GPU_FLOPS/COBBLESTONE_SCALE:MAX_CPU_FLOPS/COBBLESTONE_SCALE;
    $max_delta = $max_inst*$max_rate*$dt;
    return min($delta, $max_delta);
}

// sanity check limit on the # of jobs a host can process in one day.
//
function check_njobs($dt, $njobs) {
    if ($njobs < 0) return 0;
    return min($njobs, MAX_JOBS_DAY*$dt/86400.);
}

// - compute accounting deltas based on AM request message
// (which lists per-project totals)
// and totals in host/project records.
// - update host/project records
// - add deltas to latest project accounting records
// - add deltas (summed over projects) to latest user accounting record
// - add deltas (summed over projects) to latest global accounting record
//
function do_accounting(
    $req,           // AM RPC request as simpleXML object
    $user, $host    // user and host records
) {
    global $now;
    $dt = $now = $host->rpc_time;
    if ($dt < 0) {
        return;
    }

    // the client reports totals per project.
    // the following are sums across all projects
    //
    $sum_delta = new_delta_set();

    foreach ($req->project as $rp) {
        $url = (string)$rp->url;
        $project = SUProject::lookup("url='$url'");
        if (!$project) {
            log_write("can't find project $url");
            continue;
        }

        // get host/project record; create if needed
        //
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
        }

        $rp_cpu_time = (double)$rp->cpu_time;
        $rp_cpu_ec = (double)$rp->cpu_ec;
        $rp_gpu_time = (double)$rp->gpu_time;
        $rp_gpu_ec = (double)$rp->gpu_ec;
        $rp_njobs_success = (int)$rp->njobs_success;
        $rp_njobs_fail = (int)$rp->njobs_error;

        // compute deltas for this project
        //
        $dproj = new_delta_set();
        $d = $rp_cpu_time - $hp->cpu_time;
        $dproj->cpu_time = check_time_delta($dt, $d, false);
        $d = $rp_cpu_ec - $hp->cpu_ec;
        $dproj->cpu_ec = check_ec_delta($dt, $d, false);
        $d = $rp_gpu_time - $hp->gpu_time;
        $dproj->gpu_time = check_time_delta($dt, $d, true);
        $d = $rp_gpu_ec - $hp->gpu_ec;
        $dproj->gpu_ec = check_ec_delta($dt, $d, true);
        $d = $rp_njobs_success - $hp->njobs_success;
        $dproj->njobs_success = check_njobs($dt, $d);
        $d = $rp_njobs_fail - $hp->njobs_fail;
        $dproj->njobs_fail = check_njobs($dt, $d);

        if (1) {
            log_write("Deltas for $project->name:");
            log_write_deltas($dproj);
        }

        // update host/project record (totals)
        //
        $ret = $hp->update("
            cpu_time = $rp_cpu_time,
            cpu_ec = $rp_cpu_ec,
            gpu_time = $rp_gpu_time,
            gpu_ec = $rp_gpu_ec,
            njobs_success = $rp_njobs_success,
            njobs_fail = $rp_njobs_fail
            where host_id = $host->id and project_id = $project->id
        ");
        if (!$ret) {
            log_write("up->update failed");
            su_error(-1, "hp->update failed");
        }

        // update user/project record (totals)
        //
        if (delta_set_nonzero($dproj)) {
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
        }

        // add deltas to deltas in project's current accounting record
        //
        if (delta_set_nonzero($dproj)) {
            $ap = SUAccountingProject::last($project->id);
            $ret = $ap->update(delta_update_string($dproj));
            if (!$ret) {
                log_write("ap->update failed");
                su_error(-1, "ap->update failed");
            }
        }

        // update project balance
        //
        $flops = ec_to_gflops($dproj->cpu_ec + $dproj->gpu_ec);
        log_write("subtracting $flops from balance of $project->name");
        $ret = $project->update("balance = greatest(0, balance-$flops)");
        if (!$ret) {
            log_write("project->update failed");
                su_error(-1, "project->update failed");
        }

        // add to all-project delta sums
        //
        $sum_delta = add_delta_set($dproj, $sum_delta);
    }

    if (1) {
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
}

function am_error_reply($msg) {
    echo "<acct_mgr_reply>
        <error_msg>$msg</error_msg>
        </acct_mgr_reply>
    ";
    exit;
}

function main() {
    global $now;

    $req = simplexml_load_file('php://input');
    //$req = simplexml_load_file('req.xml');
    if (!$req) {
        log_write("can't parse request: $req");
        su_error(-1, "can't parse request");
    }

    $now = time();

    xml_header();   // do this before DB access

    list($user, $host) = lookup_records($req);
    log_write("processing request from user $user->id host $host->id");
    do_accounting($req, $user, $host);
    list($accounts_to_send, $new_accounts) =
        choose_projects_rpc($user, $host, $req)
    ;
    send_reply($user, $host, $accounts_to_send, $new_accounts, $req);
}

main();

?>
