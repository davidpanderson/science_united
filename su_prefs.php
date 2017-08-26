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
//
// show tree of keywords
// a list of kws to expand is passed in URL
// a keyword is expanded if either
//  - its ID is in expand list
//  - one of its ancestors has a pref
//
// an expanded keyword has a "contract" button if
// none of its ancestors has a pref
//
// a contracted keyword has an "expand" button if it has children

require_once("../inc/util.inc");
require_once("../inc/keywords.inc");

require_once("su_db.inc");
require_once("su.inc");

// show/enter/edit prefs

// mark keywords according to whether descendant has user pref
//
function flag_keywords(&$ukws) {
    global $job_keywords;
    foreach ($job_keywords as $kw) {
        $kw->has_user_pref = false;
    }
    foreach ($ukws as $ukw) {
        $kw = $job_keywords[$ukw->keyword_id];
        while (true) {
            if ($kw->parent) {
                $kw = $job_keywords[$kw->parent];
                $kw->has_user_pref = true;
            } else {
                break;
            }
        }
    }
}

function expand_url($add_id, $remove_id) {
    global $job_keywords;
    $x = "";
    foreach ($job_keywords as $id=>$jk) {
        if ($jk->expand && $id!=$remove_id) {
            $x .= "$id ";
        }
    }
    if ($add_id) {
        $x .= "$add_id ";
    }
    return "su_prefs.php?expand=$x";
}

function show_keyword($kwid, $uprefs) {
    global $job_keywords;
    $yes = $maybe = $no = '';
    $kw = $job_keywords[$kwid];
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
    $expand = false;
    $indent = "";
    $size = 1.2;
    for ($i=0; $i<$kw->level; $i++) {
        $indent .= '<span style="padding-left:2.6em"/>';
        $size *= .9;
    }
    if ($kw->expand || $kw->has_user_pref) {
        $expand = true;
        if ($kw->expand) {
            $x = sprintf('%s<a href="%s">&boxminus;</a> %s', $indent, expand_url(0, $kwid), $kw->name);
        } else {
            $x = '<span style="margin-left:1.3em">'.$indent.$kw->name."</span>";
        }
    } else {
        if ($kw->has_descendant) {
            $x = sprintf('%s<a href="%s">&boxplus;</a> %s', $indent, expand_url($kwid, 0), $kw->name);
        } else {
            $x = '<span style="margin-left:1.3em">'.$indent.$kw->name."</span>";
        }
    }
    table_row(
        sprintf('<span style="font-size:%d%%">%s</span>', (int)($size*100), $x),
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
    if ($expand) {
        foreach ($job_keywords as $id=>$kw) {
            if ($kw->parent == $kwid) {
                show_keyword($id, $uprefs);
            }
        }
    }
}

// show keywords w/ radio buttons
//
function show_keywords_checkbox($ukws, $category) {
    global $job_keywords;
    $uprefs = array();
    foreach ($ukws as $ukw) {
        $uprefs[$ukw->keyword_id] = $ukw->yesno;
    }
    row_heading_array(array('', 'Priority', 'As needed', 'Never'), null, 'bg-default');

    // show top-level keywords; we'll recursively decide which others
    //
    foreach ($job_keywords as $kwid=>$kw) {
        if ($kw->category != $category) continue;
        if ($kw->level) continue;
        show_keyword($kwid, $uprefs);
    }
}

function get_kw_properties() {
    global $job_keywords;
    foreach ($job_keywords as $id=>$kw) {
        $kw->has_descendant = false;
        $kw->expand = false;
    }
    foreach ($job_keywords as $id=>$kw) {
        while ($kw->level > 0) {
            $kw = $job_keywords[$kw->parent];
            $kw->has_descendant = true;
        }
    }
    $expand = get_str("expand", true);
    if ($expand) {
        $expand = explode(" ", $expand);
        foreach ($expand as $x) {
            $x = (int) $x;
            $job_keywords[$x]->expand = true;
        }
    }
}

function prefs_edit_form($user) {
    page_head("Edit preferences");
    form_start('su_prefs.php');
    form_input_hidden('action', 'prefs_edit_action');
    $ukws = SUUserKeyword::enum("user_id = $user->id");
    flag_keywords($ukws);

    get_kw_properties();
    start_table('table-striped');

    row_heading("Science area", "bg-info");
    show_keywords_checkbox($ukws, KW_CATEGORY_SCIENCE);

    row_heading("Location", "bg-info");
    show_keywords_checkbox($ukws, KW_CATEGORY_LOC);

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

switch ($action) {
case 'expand':
    break;
case 'prefs_edit_form':
default:
    prefs_edit_form($user);
    break;
case 'prefs_edit_action':
    prefs_edit_action($user);
    break;
}

?>
