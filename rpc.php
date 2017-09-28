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

define('COBBLESTONE_SCALE', 200./86400e9);

$now = 0;

// return error
//
function su_error($num, $msg) {
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
function send_reply($user, $host, $accounts, $req) {
    echo "<acct_mgr_reply>\n"
        ."<name>Onboard</name>\n"
        ."<signing_key>\n"
    ;
    readfile('code_sign_public');
    echo "</signing_key>\n"
        ."<repeat_sec>86400</repeat_sec>\n"
    ;
    send_user_keywords($user);
    echo "$user->global_prefs\n";

    // tell client which projects to attach to
    //
    foreach ($accounts as $a) {
        $proj = $a[0];
        $acct = $a[1];
        echo "<account>\n"
            ."   <url>$proj->url</url>\n"
            ."   <url_signature>\n$proj->url_signature\n</url_signature>\n"
            ."   <authenticator>$acct->authenticator</authenticator>\n"
            ."</account>\n"
        ;
    }

    // tell it to detach from other projects it's currently attached to
    // TODO: leave attached to those with large disk usage,
    // but set resource share to zero.
    //
    foreach ($req->project as $rp) {
        $url = (string)$rp->url;
        if (is_in_accounts($url, $accounts)) {
            continue;
        }
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
        $x .= sprintf("[CUDA|%s|%d]", $c->name, $c->count);
    }
    $c = $req->host_info->coprocs->coproc_ati;
    if ($c) {
        $x .= sprintf("[CAL|%s|%d]", $c->name, $c->count);
    }
    $c = $req->host_info->coprocs->coproc_intel_gpu;
    if ($c) {
        $x .= sprintf("[INTEL|%s|%d]", $c->name, $c->count);
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

    $query = "
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
    $email_addr = (string)$req->name;
    $user = BoincUser::lookup_email_addr($email_addr);
    if (!$user) {
        su_error(-1, 'no account found');
    }

    $passwd_hash = (string)$req->password_hash;
    if ($passwd_hash != $user->passwd_hash) {
        su_error(-1, 'bad password');
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

//
function check_time_delta($dt, $delta, $is_gpu) {
    if ($delta < 0) return 0;
    $max_inst = $is_gpu?8:256;
    $max_delta = $max_inst*$dt;
    return min($delta, $max_delta);
}

function check_ec_delta($dt, $delta, $is_gpu) {
    if ($delta < 0) return 0;
    $max_inst = $is_gpu?8:256;
    $max_rate = $is_gpu?100e12/COBBLESTONE_SCALE:100e9/COBBLESTONE_SCALE;
    $max_delta = $max_inst*$max_rate*$dt;
    return min($delta, $max_delta);
}

// limit on the # of jobs a host could process in one day.
// Let's say 10000
//
function check_njobs($dt, $njobs) {
    if ($njobs < 0) return 0;
    return min($njobs, 10000.*$dt/86400.);
}

// compute deltas for CPU/GPU time and flops.
// update host record.
// create accounting_user record if needed.
// update total, project, host accounting records
//
function do_accounting($req, $user, $host) {
    global $now;
    $dt = $now = $host->rpc_time;
    if ($dt < 0) {
        return;
    }

    // the client reports totals per project.
    // the following vars are sums across all projects
    //
    $sum_cpu_time = 0;
    $sum_cpu_ec = 0;
    $sum_gpu_time = 0;
    $sum_gpu_ec = 0;
    $sum_delta_cpu_time = 0;
    $sum_delta_cpu_ec = 0;
    $sum_delta_gpu_time = 0;
    $sum_delta_gpu_ec = 0;
    $sum_njobs_success = 0;
    $sum_njobs_fail = 0;
    $sum_delta_njobs_success = 0;
    $sum_delta_njobs_fail = 0;

    foreach ($req->project as $rp) {
        $url = (string)$rp->url;
        $project = SUProject::lookup("url='$url'");
        if (!$project) {
            //echo "can't find project $url\n";
            continue;
        }

        // got host/project record
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
        $d = $rp_cpu_time - $hp->cpu_time;
        $d_cpu_time = check_time_delta($dt, $d, false);
        $d = $rp_cpu_ec - $hp->cpu_ec;
        $d_cpu_ec = check_ec_delta($dt, $d, false);
        $d = $rp_gpu_time - $hp->gpu_time;
        $d_gpu_time = check_time_delta($dt, $d, true);
        $d = $rp_gpu_ec - $hp->gpu_ec;
        $d_gpu_ec = check_ec_delta($dt, $d, true);
        $d = $rp_njobs_success - $hp->njobs_success;
        $d_njobs_success = check_njobs($dt, $d);
        $d = $rp_njobs_fail - $hp->njobs_fail;
        $d_njobs_fail = check_njobs($dt, $d);

        // update host/project record
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
        if (!$ret) su_error(-1, "hp->update failed");

        // add deltas to project's accounting record
        //
        $ap = SUAccountingProject::last($project->id);
        $ret = $ap->update("
            cpu_ec_delta = cpu_ec_delta + $d_cpu_ec,
            gpu_ec_delta = gpu_ec_delta + $d_gpu_ec,
            cpu_time_delta = cpu_time_delta + $d_cpu_time,
            gpu_time_delta = gpu_time_delta + $d_gpu_time,
            njobs_success_delta = njobs_success_delta + $d_njobs_success,
            njobs_fail_delta = njobs_fail_delta + $d_njobs_fail
        ");
        if (!$ret) su_error(-1, "ap->update failed");

        // add to all-project totals
        //
        $sum_delta_cpu_time += $d_cpu_time;
        $sum_delta_cpu_ec += $d_cpu_ec;
        $sum_delta_gpu_time += $d_gpu_time;
        $sum_delta_gpu_ec += $d_gpu_ec;
        $sum_cpu_time += $rp_cpu_time;
        $sum_cpu_ec += $rp_cpu_ec;
        $sum_gpu_time += $rp_gpu_time;
        $sum_gpu_ec += $rp_gpu_ec;
        $sum_njobs_success += $rp_njobs_success;
        $sum_njobs_fail += $rp_njobs_fail;
        $sum_delta_njobs_success += $d_njobs_success;
        $sum_delta_njobs_fail += $d_njobs_fail;
    }

    // update user accounting record with deltas summed over projects
    //
    $au = SUAccountingUser::last($user->id);
    if (!$au) {
        SUAccountingUser::insert(
            "(user_id, create_time) values($user->id, $now)"
        );
        $au = SUAccountingUser::last($user->id);
    }
    $ret = $au->update("
        cpu_time_delta = cpu_time_delta + $sum_delta_cpu_time,
        cpu_ec_delta = cpu_ec_delta + $sum_delta_cpu_ec,
        gpu_time_delta = gpu_time_delta + $sum_delta_gpu_time,
        gpu_ec_delta = gpu_ec_delta + $sum_delta_gpu_ec,
        njobs_success_delta = njobs_success_delta + $sum_delta_njobs_success,
        njobs_fail_delta = njobs_fail_delta + $sum_delta_njobs_fail
    ");
    if (!$ret) {
        su_error(-1, "au->update failed");
    }

    // update global accounting record
    //
    $acc = SUAccounting::last();
    $ret = $acc->update("
        cpu_time_delta = cpu_time_delta + $sum_delta_cpu_time,
        cpu_ec_delta = cpu_ec_delta + $sum_delta_cpu_ec,
        gpu_time_delta = gpu_time_delta + $sum_delta_gpu_time,
        gpu_ec_delta = gpu_ec_delta + $sum_delta_gpu_ec,
        njobs_success_delta = njobs_success_delta + $sum_delta_njobs_success,
        njobs_fail_delta = njobs_fail_delta + $sum_delta_njobs_fail
    ");
    if (!$ret) {
        su_error(-1, "acc->update failed");
    }
}

function main() {
    global $now;

    $req = simplexml_load_file('php://input');
    //$req = simplexml_load_file('req.xml');
    if (!$req) {
        su_error(-1, "can't parse request");
    }

    $now = time();

    xml_header();   // do this before DB access

    list($user, $host) = lookup_records($req);
    do_accounting($req, $user, $host);
    $accounts_to_send = choose_projects_rpc($user, $host);
    send_reply($user, $host, $accounts_to_send, $req);
}

main();

?>
