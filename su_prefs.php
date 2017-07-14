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

// user page for editing keyword prefs

require_once("../inc/util.inc");
require_once("../inc/keywords.inc");

require_once("su_db.inc");
require_once("su.inc");

// show/enter/edit prefs

// show keywords w/ radio buttons
//
function show_keywords_checkbox($ukws, $category, $level) {
    global $job_keywords;
    $uprefs = array();
    foreach ($ukws as $ukw) {
        $uprefs[$ukw->keyword_id] = $ukw->yesno;
    }
    row_heading_array(array('', 'Yes', 'Maybe', 'No'), null, 'bg-default');
    foreach ($job_keywords as $kwid=>$kw) {
        $yes = $maybe = $no = '';
        if ($kw->category != $category) continue;
        if ($kw->level != $level) continue;
        if (array_key_exists($kwid, $uprefs)) {
            switch ($uprefs[$kwid]) {
            case KW_YES:
                $yes = 'checked';
                break;
            case KW_NO: 
                $no = 'checked';
                break;
            }
        } else {
            $maybe = 'checked';
        }
        $name = "kw_$kwid";
        table_row(
            $kw->name,
            sprintf('<input type="radio" name="%s" value="%d" %s>',
                $name, KW_YES, $yes
            ),
            sprintf('<input type="radio" name="%s" value="%d" %s>',
                $name, KW_MAYBE, $maybe
            ),
            sprintf('<input type="radio" name="%s" value="%d" %s>',
                $name, KW_NO, $no
            )
        );
    }
}

// return the max level of any keyword the user has set in the given category
//
function max_level($ukws, $category) {
    global $job_keywords;
    $max = 0;
    foreach ($ukws as $ukw) {
        $kw = $job_keywords[$ukw->keyword_id];
        if ($kw->category != $category) {
            continue;
        }
        if ($kw->level > $max) {
            $max = $kw->level;
        }
    }
    return $max;
}

function prefs_edit_form($user, $area_level, $loc_level) {
    page_head("Edit preferences");
    form_start('su_prefs.php');
    form_input_hidden('action', 'prefs_edit_action');
    $ukws = SUUserKeyword::enum("user_id = $user->id");

    start_table('table-striped');

    $x = max_level($ukws, KW_CATEGORY_SCIENCE);
    if ($x > $area_level) {
        $area_level = $x;
    }

    $y = "Science area";
    if ($area_level == 0) {
        $z = $loc_level?"&loc_level=$loc_level":"";
        $y .= "<br><small><a href=su_prefs.php?area_level=1$z>More detail</a></small>";
    }
    row_heading($y, "bg-info");
    if ($area_level == 0) {
        show_keywords_checkbox($ukws, KW_CATEGORY_SCIENCE, 0);
    } else {
        row_heading("General", "bg-default");
        show_keywords_checkbox($ukws, KW_CATEGORY_SCIENCE, 0);
        row_heading("Specific", "bg-default");
        show_keywords_checkbox($ukws, KW_CATEGORY_SCIENCE, 1);
    }

    $x = max_level($ukws, KW_CATEGORY_LOC);
    if ($x > $loc_level) {
        $loc_level = $x;
    }

    $y = "Location";
    if ($loc_level == 0) {
        $z = $area_level?"&area_level=$area_level":"";
        $y .= "<br><small><a href=su_prefs.php?loc_level=1$z>More detail</a></small>";
    }
    row_heading($y, "bg-info");
    if ($loc_level == 0) {
        show_keywords_checkbox($ukws, KW_CATEGORY_LOC, 0);
    } else {
        row_heading("General", "bg-default");
        show_keywords_checkbox($ukws, KW_CATEGORY_LOC, 0);
        row_heading("Specific", "bg-default");
        show_keywords_checkbox($ukws, KW_CATEGORY_LOC, 1);
    }

    end_table();
    echo '<button type="submit" class="btn btn-success">OK</button>
    ';
    form_end();
    page_tail();
}

// return current user setting for the given keyword
//
function get_cur_val($kw_id, $ukws) {
    foreach ($ukws as $ukw) {
        if ($ukw->keyword_id != $kw_id) continue;
        return $ukw->yesno;
    }
    return KW_MAYBE;
}

function prefs_edit_action($user) {
    global $job_keywords;
    $ukws = SUUserKeyword::enum("user_id=$user->id");
    foreach ($job_keywords as $kwid=>$kw) {
        $name = "kw_$kwid";
        $val = get_int($name, true);
        $cur_val = get_cur_val($kwid, $ukws);
        if ($val == $cur_val) continue;
        switch ($val) {
        case KW_NO:
        case KW_YES:
            if ($cur_val == KW_MAYBE) {
                SUUserKeyword::insert("(user_id, keyword_id, yesno) values ($user->id, $kwid, $val)");
            } else {
                SUUserKeyword::update("yesno=$val where user_id=$user->id and keyword_id=$kwid");
            }
            break;
        case KW_MAYBE:
            $ukw = new SUUserKeyword;
            $ukw->keyword_id = $kwid;
            $ukw->user_id = $user->id;
            $ukw->delete();
            break;
        }
    }
    Header("Location: su_prefs.php");
}

$user = get_logged_in_user();
$action = get_str('action', true);
$area_level = get_int('area_level', true);
$loc_level = get_int('loc_level', true);

switch ($action) {
case 'prefs_edit_form':
default:
    prefs_edit_form($user, $area_level, $loc_level);
    break;
case 'prefs_edit_action':
    prefs_edit_action($user);
    break;
}

?>
