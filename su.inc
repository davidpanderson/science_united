<?php

require_once("../inc/su_db.inc");
require_once("../inc/su.inc");

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

function show_computers($user) {
    $hosts = BoincHost::enum("userid=$user->id");
    start_table('table-striped');
    row_heading_array(array('Name', 'average throughput'));
    foreach($hosts as $host) {
        row_array(array($host->domain_name, $host->expavg_credit));
    }
    end_table();
}

function show_projects($user) {
    $accounts = SUAccount::enum("user_id=$user->id");
    start_table('table-striped');
    end_table();
}
