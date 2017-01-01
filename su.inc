<?php

require_once("../inc/su_db.inc");
require_once("../inc/su.inc");

////////////// UI FOR KEYWORDS ////////////////

// show select for keywords

function keyword_select ($keywords, $category) {
    foreach ($keywords as $k) {
        if ($k->category != $category) continue;
        echo '"<option value="'.$k->id.'">'.$k->name.'
        ';
    }
}

function ukw_lookup($ukws, $id) {
    foreach ($ukws as $uwk) {
        if ($uwk->keyword_id == $id) {
            return $uwk;
        }
    }
    return null;
}

function show_keywords($ukws, $kws, $category, $type) {
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
    row2('Yes', show_keywords($ukws, $kws, SCIENCE, KW_YES));
    row2('No', show_keywords($ukws, $kws, SCIENCE, KW_NO));
    row2('Maybe', show_keywords($ukws, $kws, SCIENCE, KW_MAYBE));

    row_heading('Locations');
    row2('Yes', show_keywords($ukws, $kws, LOCATION, KW_YES));
    row2('No', show_keywords($ukws, $kws, LOCATION, KW_NO));
    row2('Maybe', show_keywords($ukws, $kws, LOCATION, KW_MAYBE));
    end_table();

    echo '<p><a class="btn btn-success" href="su_prefs.php?action=prefs_edit_form">Edit preferences</a>
    ';
}

///////////// UI FOR VOLUNTEERS /////////////

function show_user_computers($user) {
    $hosts = BoincHost::enum("userid=$user->id");
    start_table('table-striped');
    row_heading_array(array('Name', 'average throughput'));
    foreach($hosts as $host) {
        row_array(array($host->domain_name, $host->expavg_credit));
    }
    end_table();
}

function show_user_projects($user) {
    $accounts = SUAccount::enum("user_id=$user->id");
    start_table('table-striped');
    end_table();
}

// show accounting totals
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
        "user_id=$user->id", "order by id desc limit 20"
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
            $sau->create_time,
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
