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

// utility functions involving YI

require_once("../inc/host.inc");
require_once("../inc/su_db.inc");
require_once("../inc/su_util.inc");

function admin_only() {
    $user = get_logged_in_user();
    BoincForumPrefs::lookup($user);
    if (!is_admin($user)) {
        error_page("Not admin");
    }
}

function project_status_string($status) {
    switch ($status) {
    case PROJECT_STATUS_HIDE: return "<font color=red>hidden</font>";
    case PROJECT_STATUS_ON_DEMAND: return "<font color=brown>on demand</font>";
    case PROJECT_STATUS_AUTO: return "<font color=green>normal</font>";
    }
    return "unknown: $status";
}

// convert list of project keywords to human-readable string
//
function project_keyword_array_to_string($arr) {
    $x = "";
    foreach ($arr as $y) {
        $pkw = $y[0];
        $wd = $y[1];
        if ($x) {
            $x .= "<br>";
        }
        $x .= $wd;
        $frac = $pkw->fraction;
        if ($frac < 1) {
            $pct = $frac*100;
            $x .= " ($pct%)";
        }
    }
    return $x;
}

// get a project's platform list, as a string
//
function get_platforms_string($project_id) {
    $project_infos = unserialize(file_get_contents("projects.ser"));
    $p = $project_infos[$project_id];
    $x = $p->avs;
    $s = "";
    foreach ($x as $y) {
        $s .= "$y->platform";
        if ($y->gpu) $s .= " + $y->gpu ";
        if ($y->vbox) $s .= " + VirtualBox";
        $s .= "<br>\n";
    }
    return $s;
}

////////////// UI INVOLVING KEYWORDS ////////////////

// look up the given keyword ID in the user KW list
//
function ukw_lookup($ukws, $id) {
    foreach ($ukws as $uwk) {
        if ($uwk->keyword_id == $id) {
            return $uwk;
        }
    }
    return null;
}

// return comma-separated list of KWs
//
function user_keyword_string($ukws, $kws, $category, $type) {
    $first = true;
    $x = '';
    foreach ($kws as $kw) {
        if ($kw->category != $category) continue;
        $ukw = ukw_lookup($ukws, $kw->id);
        if (($ukw && ($ukw->type == $type)) || (!$ukw && $type==KW_MAYBE)) {
            if (!$first) $x .= ", ";
            $first = false;
            $x .= $kw->word;
        }
    }
    if ($first) {
        $x .= "---";
    }
    return $x;
}

function prefs_show($user) {
    $ukws = SUUserKeyword::enum("user_id=$user->id");
    $kws = SUKeyword::enum();
    start_table();
    row_heading('Types of science');
    row2('Yes', user_keyword_string($ukws, $kws, SCIENCE, KW_YES));
    row2('No', user_keyword_string($ukws, $kws, SCIENCE, KW_NO));
    row2('Maybe', user_keyword_string($ukws, $kws, SCIENCE, KW_MAYBE));

    row_heading('Locations');
    row2('Yes', user_keyword_string($ukws, $kws, LOCATION, KW_YES));
    row2('No', user_keyword_string($ukws, $kws, LOCATION, KW_NO));
    row2('Maybe', user_keyword_string($ukws, $kws, LOCATION, KW_MAYBE));
    end_table();

    echo '<p><a class="btn btn-success" href="su_prefs.php?action=prefs_edit_form">Edit preferences</a>
    ';
}

///////////// UI INVOLVING PROJECTS /////////////

function account_status_string($i) {
    switch ($i) {
    case ACCT_INIT:
        return "Initial";
    case ACCT_SUCCESS:
        return "Attached";
    case ACCT_DIFFERENT_PASSWORD:
        return "Password mismatch";
    case ACCT_TRANSIENT_ERROR:
        return "Transient error";
    }
    return "Unknown";
}

function show_user_projects($user) {
    $accounts = SUAccount::enum("user_id=$user->id");
    if (count($accounts) == 0) {
        echo "No accounts yet";
        return;
    }
    start_table('table-striped');
    row_heading_array(array(
        "Name",
        "Status",
        "CPU time",
        "CPU FLOPS",
        "GPU time",
        "GPU FLOPS",
        "# jobs success",
        "# jobs fail"
    ));
    foreach ($accounts as $a) {
        $project = SUProject::lookup_id($a->project_id);
        row_array(array(
            $project->name,
            account_status_string($a->state),
            $a->cpu_time,
            $a->cpu_ec,
            $a->gpu_time,
            $a->gpu_ec,
            $a->njobs_success,
            $a->njobs_fail
        ));
    }
    end_table();
}

function show_problem_projects($user) {
    $accounts = SUAccount::enum(
        sprintf("user_id=%d and state=%d", $user->id, ACCT_DIFFERENT_PASSWD)
    );
    if (!count($accounts)) return;
    echo "
        <h3>Password mismatch</h3>
        Your accounts on the following projects
        have a password that's different from your Science United password.
        We can't use these projects unless the passwords match.
        Please go to the project web site and change your password
        to your Science United password.
    ";
    foreach($accounts as $a) {
        $p = SUProject::lookup_id($a->project_id);
        echo sprintf('<p><a href="%s">%s</a>', $p->url, $p->name);
    }
}

///////////// UI INVOLVING HOSTS /////////////

// table description of a host
//
function show_host_detail($host) {
    row2(tra("Domain name"), $host->domain_name);
    if ($host->product_name) {
        row2(tra("Product name"), $host->product_name);
    }
    $x = $host->timezone/3600;
    if ($x >= 0) $x="+$x";
    row2(tra("Local Standard Time"), tra("UTC %1 hours", $x));
    row2(tra("Created"), time_str($host->create_time));
    row2(tra("Number of times client has contacted server"), $host->rpc_seqno);
    row2(tra("Last time contacted server"), sched_log_link($host->rpc_time));
    row2(tra("Fraction of time BOINC is running"), number_format(100*$host->on_frac, 2)."%");
    if ($host->connected_frac > 0) {
        row2(tra("While BOINC is running, fraction of time computer has an Internet connection"), number_format(100*$host->connected_frac, 2)."%");
    }
    row2(tra("While BOINC is running, fraction of time computing is allowed"), number_format(100*$host->active_frac, 2)."%");
    row2(tra("While is BOINC running, fraction of time GPU computing is allowed"), number_format(100*$host->gpu_active_frac, 2)."%");
}

function show_host_hw_sw($host) {
    row2("Name", $host->domain_name);
    row2(tra("CPU type"), "$host->p_vendor <br> $host->p_model");
    row2(tra("Number of processors"), $host->p_ncpus);
    if ($host->serialnum) {
        row2(tra("Coprocessors"), gpu_desc($host->serialnum));
    }
    row2(tra("Operating System"), "$host->os_name <br> $host->os_version");
    $v = boinc_version($host->serialnum);
    if ($v) {
        row2(tra("BOINC version"), $v);
    }
    $x = $host->m_nbytes/MEGA;
    $y = round($x, 2);
    row2(tra("Memory"), tra("%1 MB", $y));
    if ($host->m_cache > 0) {
        $x = $host->m_cache/KILO;
        $y = round($x, 2);
        row2(tra("Cache"), tra("%1 KB", $y));
    }

    $x = $host->m_swap/MEGA;
    $y = round($x, 2);
    row2(tra("Swap space"), tra("%1 MB", $y));
    $x = $host->d_total/GIGA;
    $y = round($x, 2);
    row2(tra("Total disk space"), tra("%1 GB", $y));
    $x = $host->d_free/GIGA;
    $y = round($x, 2);
    row2(tra("Free Disk Space"), tra("%1 GB", $y));
    $x = $host->p_fpops/(1000*1000);
    $y = round($x, 2);
    row2(tra("Measured floating point speed"), tra("%1 million ops/sec", $y));
    $x = $host->p_iops/(1000*1000);
    $y = round($x, 2);
    row2(tra("Measured integer speed"), tra("%1 million ops/sec", $y));
    $x = $host->n_bwup/KILO;
    $y = round($x, 2);
    if ($y > 0) {
        row2(tra("Average upload rate"), tra("%1 KB/sec", $y));
    } else {
        row2(tra("Average upload rate"), tra("Unknown"));
    }
    $x = $host->n_bwdown/KILO;
    $y = round($x, 2);
    if ($y > 0) {
        row2(tra("Average download rate"), tra("%1 KB/sec", $y));
    } else {
        row2(tra("Average download rate"), tra("Unknown"));
    }

    row2("CPU features", $host->p_features);
    row2("VM extensions disabled?", $host->p_vm_extensions_disabled?"yes":"no");
    row2("VirtualBox version", $host->virtualbox_version);
    row2("BOINC client version", $host->boinc_client_version);
}

// show list of projects a host has computed for
//
function show_host_projects($host) {
    $hps = SUHostProject::enum("host_id=$host->id");
    if (count($hps) == 0) {
        echo "no projects";
        return;
    }
    start_table();
    row_heading_array(array(
        "Name",
        "Last contact",
        "CPU time",
        "CPU FLOPs",
        "GPU time",
        "GPU FLOPs",
        "# jobs success",
        "# jobs fail",
    ));
    foreach ($hps as $hp) {
        $project = SUProject::lookup_id($hp->project_id);
        row_array(array(
            $project->name,
            date_str($hp->last_rpc),
            $hp->cpu_time,
            $hp->cpu_ec,
            $hp->gpu_time,
            $hp->gpu_ec,
            $hp->njobs_success,
            $hp->njobs_fail,
        ));
    }
    end_table();
}

///////////// UI INVOLVING ACCOUNTING ////////////////

// Design philsophy:
// use only the following accounting units:
// - device time in days
// - GFLOPS/TFLOPS/PFLOPS as appropriate

// generic function to show accounting totals
//
function show_accounting_totals($a) {
    if (!$a) {
        echo "No accounting records yet";
        return;
    }
    start_table();
    row2("CPU days", show_days($a->cpu_time_total));
    row2("GPU days", show_days($a->gpu_time_total));
    row2("Successful jobs", $a->njobs_success_total);
    row2("Failed jobs", $a->njobs_fail_total);
    end_table();
}

function show_accounting_deltas($a, $active=false) {
    start_table();
    row2("CPU days", show_days($a->cpu_time_delta));
    row2("GPU days", show_days($a->gpu_time_delta));
    row2("Successful jobs", $a->njobs_success_delta);
    row2("Failed jobs", $a->njobs_fail_delta);
    if ($active) {
        row2("Active volunteers", $a->nactive_users);
        row2("Active computers", $a->nactive_hosts);
        row2("Active computers with GPU", $a->nactive_hosts_gpu);
    }
    end_table();
}

// generic function to show accounting history
//
function show_accounting_history($as) {
    if (count($as) == 0) {
        echo "No records yet";
        return;
    }
    start_table();
    row_heading_array(array(
        "Start",
        "CPU days",
        "CPU GFLOPS",
        "GPU days",
        "GPU GFLOPS",
        "Jobs succeeded",
        "Jobs failed",
    ));

    for($i=0; $i<count($as); $i++) {
        $a = $as[$i];
        if ($i == 0) {
            row_array(array(
                "Totals",
                show_days($a->cpu_time_total),
                "",
                show_days($a->cpu_time_total),
                "",
                $a->njobs_success_total,
                $a->njobs_fail_total,
            ));
            $dt = time() - $a->create_time;
        } else {
            $dt = $as[$i-1]->create_time - $a->create_time ;
        }
        row_array(array(
            time_str($a->create_time),
            //show_days($a->cpu_time_delta),
            $a->cpu_time_delta,
            show_gflops($a->cpu_ec_delta, $dt),
            //show_days($a->gpu_time_delta),
            $a->gpu_time_delta,
            show_gflops($a->gpu_ec_delta, $dt),
            $a->njobs_success_delta,
            $a->njobs_fail_delta,
        ));
    }
    end_table();
}
