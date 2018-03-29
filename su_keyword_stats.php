<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2018 University of California
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

// show yes/no stats for keywords

require_once("../inc/util.inc");
require_once("../inc/su_db.inc");
require_once("../inc/keywords.inc");

$nusers = 0;

function keyword_setup() {
    global $job_keywords;
    foreach ($job_keywords as $id=>$k) {
        $job_keywords[$id]->children = array();
    }
    foreach ($job_keywords as $id=>$k) {
        if (!$k->parent) continue;
        $job_keywords[$k->parent]->children[] = $id;
    }
}

function show_keyword($id) {
    global $job_keywords, $nusers;
    $k = $job_keywords[$id];
    $spaces = "";
    for ($i=0; $i<$k->level; $i++) {
        $spaces .= "&nbsp;&nbsp;&nbsp;";
    }
    row_array(array(
        $spaces.$k->name,
        (100*$k->nyes)/$nusers."%",
        (100*$k->nno)/$nusers."%"
    ));
    foreach ($k->children as $i) {
        show_keyword($i);
    }
}

function show_keywords($category) {
    global $job_keywords;
    row_heading_array(array("Keyword", "Yes", "No"));
    foreach ($job_keywords as $id=>$k) {
        if ($k->category != $category) continue;
        if ($k->parent) continue;
        show_keyword($id);
    }
}

function main() {
    global $job_keywords, $nusers;

    keyword_setup();
    $t = time()-7*86400;

    // get list of IDs of active users
    //
    $user_ids = BoincDB::get()->enum_fields("su_accounting_user", "StdClass", "distinct(user_id)", "create_time > $t", "");
    $nusers = count($user_ids);

    // turn it into a map
    //
    $u = array();
    foreach ($user_ids as $i) {
        $u[$i->user_id] = true;
    }

    // get counts of yes/no for each keyword
    //
    foreach ($job_keywords as $id=>$k) {
        $k->nyes = 0;
        $k->nno = 0;
    }
    $user_kws = SUUserKeyword::enum();
    foreach ($user_kws as $ukw) {
        if (!array_key_exists($ukw->user_id, $u)) continue;
        if ($ukw->yesno > 0) {
            $job_keywords[$ukw->keyword_id]->nyes += 1;
        } else {
            $job_keywords[$ukw->keyword_id]->nno += 1;
        }
    }

    page_head("Keyword statistics");
    start_table();
    row_heading("Science Area");
    show_keywords(KW_CATEGORY_SCIENCE);
    row_heading("Location");
    show_keywords(KW_CATEGORY_LOC);
    end_table();
    page_tail();
}

main();

?>
