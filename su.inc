<?php

require_once("../inc/host.inc");
require_once("../inc/su_db.inc");

function admin_only() {
    $user = get_logged_in_user();
    BoincForumPrefs::lookup($user);
    if (!is_admin($user)) {
        error_page("Not admin");
    }
}

////////////// UI INVOLVING KEYWORDS ////////////////

// show select for keywords (don't use)

function keyword_select ($keywords, $category) {
    foreach ($keywords as $k) {
        if ($k->category != $category) continue;
        echo '"<option value="'.$k->id.'">'.$k->name.'
        ';
    }
}

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

function show_user_computers($user) {
    $hosts = BoincHost::enum("userid=$user->id");
    start_table('table-striped');
    row_heading_array(array('Name', 'average throughput'));
    foreach($hosts as $host) {
        row_array(array($host->domain_name, $host->expavg_credit));
    }
    end_table();
}

// Show full-page description of $host.
// If $user is non-null, it's both the owner of the host
// and the logged in user (so show some extra fields)
//
function show_host_detail($host, $user, $ipprivate) {
    start_table();
    row1(tra("Computer information"));
    $anonymous = false;
    if ($user) {
        if ($ipprivate) {
            row2(tra("IP address"), "$host->last_ip_addr<br>".tra("(same the last %1 times)", $host->nsame_ip_addr));
            if ($host->last_ip_addr != $host->external_ip_addr) {
                row2(tra("External IP address"), $host->external_ip_addr);
            }
        } else { 
            row2(tra("IP address"), "<a href=show_host.php?hostid=$host->id&ipprivate=1>".tra("Show IP address")."</a>");
        }
        row2(tra("Domain name"), $host->domain_name);
        if ($host->product_name) {
            row2(tra("Product name"), $host->product_name);
        }
        $x = $host->timezone/3600;
        if ($x >= 0) $x="+$x";
        row2(tra("Local Standard Time"), tra("UTC %1 hours", $x));
    } else {
        $owner = BoincUser::lookup_id($host->userid);
        if ($owner && $owner->show_hosts) {
            row2(tra("Owner"), user_links($owner, BADGE_HEIGHT_MEDIUM));
        } else {
            row2(tra("Owner"), tra("Anonymous"));
            $anonymous = true;
        }
    }
    row2(tra("Created"), time_str($host->create_time));
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

    if ($user) {
        $x = $host->m_swap/MEGA;
        $y = round($x, 2);
        row2(tra("Swap space"), tra("%1 MB", $y));
        $x = $host->d_total/GIGA;
        $y = round($x, 2);
        row2(tra("Total disk space"), tra("%1 GB", $y));
        $x = $host->d_free/GIGA;
        $y = round($x, 2);
        row2(tra("Free Disk Space"), tra("%1 GB", $y));
    }
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
    $x = $host->avg_turnaround/86400;
    row2(tra("Average turnaround time"), tra("%1 days", round($x, 2)));

    if ($user) {
        row2(tra("Number of times client has contacted server"), $host->rpc_seqno);
        row2(tra("Last time contacted server"), sched_log_link($host->rpc_time));
        row2(tra("Fraction of time BOINC is running"), number_format(100*$host->on_frac, 2)."%");
        if ($host->connected_frac > 0) {
            row2(tra("While BOINC is running, fraction of time computer has an Internet connection"), number_format(100*$host->connected_frac, 2)."%");
        }
        row2(tra("While BOINC is running, fraction of time computing is allowed"), number_format(100*$host->active_frac, 2)."%");
        row2(tra("While is BOINC running, fraction of time GPU computing is allowed"), number_format(100*$host->gpu_active_frac, 2)."%");
        if ($host->cpu_efficiency) {
            row2(tra("Average CPU efficiency"), $host->cpu_efficiency);
        }
    } else {
        row2(tra("Number of times client has contacted server"), $host->rpc_seqno);
        row2(tra("Last contact"), date_str($host->rpc_time));
    }
    row2("CPU features", $host->p_features);
    row2("BOINC client version", $host->boinc_client_version);
    row2("Has usable GPU?", $host->has_usable_gpu?"yes":"no");
    row2("VM extensions disabled?", $host->p_vm_extensions_disabled?"yes":"no");
    row2("VirtualBox version", $host->virtualbox_version);
    row2("CPU time", $host->cpu_time);
    row2("CPU FLOPs", $host->cpu_ec);
    row2("GPU time", $host->gpu_time);
    row2("GPU FLOPs", $host->gpu_ec);
    row2("# jobs success", $host->njobs_success);
    row2("# jobs fail", $host->njobs_fail);
    echo "</table>\n";
}

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
//
function show_user_acct_totals($user) {
    $au = SUAccountingUser::last($user->id);
    if (!$au) {
        echo "No accounting records yet";
        return;
    }
    start_table();
    row2("CPU time", $au->cpu_time_total);
    row2("CPU FLOPS", $au->cpu_ec_total);
    row2("GPU time", $au->gpu_time_total);
    row2("GPU FLOPS", $au->gpu_ec_total);
    row2("Jobs: successful", $au->njobs_success_total);
    row2("Jobs: failed", $au->njobs_fail_total);
    end_table();
}

function show_user_acct_history($user) {
    $saus = SUAccountingUser::enum(
        "user_id=$user->id", "order by id desc limit 200"
    );
    if (count($saus) == 0) {
        echo "No history yet";
        return;
    }
    start_table();
    row_heading_array(array(
        "When",
        "CPU time",
        "CPU FLOPS",
        "GPU time",
        "GPU FLOPS",
        "Jobs succeeded",
        "Jobs failed",
    ));
    foreach ($saus as $sau) {
        row_array(array(
            date_str($sau->create_time),
            $sau->cpu_time_delta,
            $sau->cpu_ec_delta,
            $sau->gpu_time_delta,
            $sau->gpu_ec_delta,
            $sau->njobs_success_delta,
            $sau->njobs_fail_delta,
        ));
    }
    end_table();
}

function show_user_acct_history_ec($user) {
    $saus = SUAccountingUser::enum(
        "user_id=$user->id", "order by id desc limit 20"
    );
    if (count($saus) == 0) {
        echo "No history yet";
        return;
    }
    start_table();
    row_heading_array(array(
        "When",
        "FLOPS",
    ));
    foreach ($saus as $sau) {
        row_array(array(
            date_str($sau->create_time),
            $sau->cpu_ec_delta + $sau->gpu_ec_delta
        ));
    }
    end_table();
}

// show accountings totals
//
function show_acct_history() {
    $as = SUAccounting::enum(
        "", "order by id desc limit 20"
    );
    if (count($as) == 0) {
        echo "No history yet";
        return;
    }
    start_table();
    $first = true;
    row_heading_array(array(
        "When",
        "CPU time",
        "CPU FLOPS",
        "GPU time",
        "GPU FLOPS",
        "Jobs success",
        "Jobs fail",
    ));
    foreach ($as as $a) {
        if ($first) {
            $first = false;
            row_array(array(
                "Totals",
                $a->cpu_time_total + $a->cpu_time_delta,
                $a->cpu_ec_total + $a->cpu_ec_delta,
                $a->gpu_time_total + $a->gpu_time_delta,
                $a->gpu_ec_total + $a->gpu_ec_delta,
                $a->njobs_success_total + $a->njobs_success_delta,
                $a->njobs_fail_total + $a->njobs_fail_delta,
            ));
        }
        row_array(array(
            date_str($a->create_time),
            $a->cpu_time_delta,
            $a->cpu_ec_delta,
            $a->gpu_time_delta,
            $a->gpu_ec_delta,
            $a->njobs_success_delta,
            $a->njobs_fail_delta,
        ));
    }
    end_table();
}
