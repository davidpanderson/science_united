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

// admin interface for add/remove/edit projects

require_once("../inc/util.inc");
require_once("../inc/su_db.inc");
require_once("../inc/su.inc");
require_once("../inc/keywords.inc");

function array_to_string($arr) {
    $x = "";
    foreach ($arr as $y) {
        $pkw = $y[0];
        $wd = $y[1];
        $frac = $pkw->work_fraction;
        $pct = $frac*100;
        if ($x) {
            $x .= "<br>";
        }
        $x .= "$wd ($pct%)";
    }
    return $x;
}

function project_status_string($status) {
    switch ($status) {
    case PROJECT_STATUS_HIDE: return "<font color=red>hidden</font>";
    case PROJECT_STATUS_ON_DEMAND: return "<font color=brown>on demand</font>";
    case PROJECT_STATUS_AUTO: return "<font color=green>normal</font>";
    }
    return "unknown: $status";
}

function su_show_project() {
    global $job_keywords;

    $id = get_int('id');
    $project = SUProject::lookup_id($id);
    if (!$project) {
        error_page("no such project");
    }
    page_head($project->name);
    start_table();
    row2("name", $project->name);
    row2("URL", $project->url);
    row2("Created", date_str($project->create_time));
    row2("Allocation", $project->allocation);
    row2("Status", project_status_string($project->status));
    $pks = SUProjectKeyword::enum("project_id=$project->id");
    $sci = array();
    $loc = array();
    if ($pks) {
        foreach ($pks as $pk) {
            $kwd = $job_keywords[$pk->keyword_id];
            $x = array($pk, $kwd->name);
            if ($kwd->category == KW_CATEGORY_SCIENCE) {
                $sci[] = $x;
            }
            if ($kwd->category == KW_CATEGORY_LOC) {
                $loc[] = $x;
            }
        }
    }
    row2("Science keywords", array_to_string($sci));
    row2("Location keywords", array_to_string($loc));
    end_table();
    echo '<a class="btn btn-success" href="su_projects_edit.php?action=edit_project_form&id='.$project->id.'">Edit project</a>
    ';
    page_tail();
}

function project_kw_string($p, $category) {
    global $job_keywords;

    $x = "";
    $pkws = SUProjectKeyword::enum("project_id=$p->id");
    $first = true;
    foreach ($pkws as $pkw) {
        $kw = $job_keywords[$pkw->keyword_id];
        if ($kw->category != $category) continue;
        if (!$first) $x .= ', ';
        $first = false;
        $x .= $kw->name;
    }
    return $x;
}

function show_projects() {
    $projects = SUproject::enum("");
    if ($projects) {
        start_table('table-striped');
        row_heading_array(array(
            'Name<br><small>click for details</small>',
            'URL',
            'Science keywords',
            'Location keywords',
            'Created',
            'Allocation',
            'Status'
        ));
        foreach ($projects as $p) {
            table_row(
                '<a href="su_projects_edit.php?action=show_project&id='.$p->id.'">'.$p->name.'</a>',
                $p->url,
                project_kw_string($p, SCIENCE),
                project_kw_string($p, LOCATION),
                date_str($p->create_time),
                $p->allocation,
                project_status_string($p->status)
            );
        }
        end_table();
    } else {
        echo 'No projects yet';
    }
    echo '<p><a class="btn btn-success" href="su_projects_edit.php?action=add_project_form">Add project</a>
    ';
    echo "<p></p>";
}

// Return array of keywords of given category
// Entries are of the form id=>word
//
function keyword_subset($category) {
    global $job_keywords;
    $x = array();
    foreach ($job_keywords as $id=>$k) {
        if ($k->category != $category) continue;
        $x[$id] = $k->name;
    }
    return $x;
}

// given keyword array of the form above,
// return a corresponding array of ones a project has
// (fraction, or -1 if not present)
//
function keyword_fracs($keywords, $category, $project_id) {
    $x = array();
    foreach ($keywords as $id=>$word) {
        $x[$id] = -1;
    }

    $pks = SUProjectKeyword::enum("project_id=$project_id");
    foreach ($pks as $pk) {
        $x[$pk->keyword_id] = $pk->work_fraction;
    }

    return $x;
}

// generate checkboxes for the given keywords.
// names are of the form kw_ID
//
function keyword_checkboxes($kws, $fracs=null) {
    start_table();
    $i = 0;
    foreach ($kws as $id=>$word) {
        $checked = $fracs?($fracs[$id]>=0?"checked":""):"";
        $frac = $fracs?($fracs[$id]>=0?$fracs[$id]*100:""):"";
        row2($word,
            sprintf('<input type="checkbox" name="kw_%s" %s> %% of work: <input name="kwf_%s" size=6 value="%s">',
                $id,
                $checked,
                $id,
                $frac
            )
        );
        $i++;
    }
    end_table();
}

function add_project_form() {
    page_head("Add project");
    form_start('su_projects_edit.php');
    form_input_hidden('action', 'add_project_action');
    form_input_text('Name', 'name');
    form_input_text('URL', 'url');
    form_input_textarea('URL signature', 'url_signature');
    form_input_text('Allocation', 'alloc', '', 'number');
    $sci_keywds = keyword_subset(SCIENCE);
    $loc_keywds = keyword_subset(LOCATION);
    if (count($sci_keywds)>0) {
        echo "<h3>Science keywords</h3>\n";
        keyword_checkboxes($sci_keywds);
    }
    if (count($loc_keywds)>0) {
        echo "<h3>Location keywords</h3>\n";
        keyword_checkboxes($loc_keywds);
    }
    form_submit('OK');
    form_end();
    page_tail();
}

function add_project_action() {
    $url = get_str('url');
    $url_signature = get_str('url_signature');
    $name = get_str('name');
    $alloc = get_str('alloc');
    $now = time();
    $project_id = SUProject::insert(
        "(url, url_signature, name, create_time, allocation) values ('$url', '$url_signature', '$name', $now, $alloc)"
    );
    if (!$project_id) {
        error_page("insert failed");
    }

    Header("Location: su_projects_edit.php");
}

function edit_project_form() {
    $id = get_int('id');
    $p = SUProject::lookup_id($id);
    page_head("Edit project");
    form_start('su_projects_edit.php');
    form_input_hidden('action', 'edit_project_action');
    form_input_hidden('id', $id);
    form_input_text('URL', 'url', $p->url, 'text', 'disabled');
    form_input_text('Name', 'name', $p->name);
    form_input_text('Allocation', 'alloc', $p->allocation, 'number');
    form_radio_buttons('Status', 'status',
        array(
            array('0', 'hidden'),
            array('1', 'on demand'),
            array('2', 'normal')
        ),
        $p->status
    );
    $sci_keywds = keyword_subset(SCIENCE);
    $sci_fracs = keyword_fracs($sci_keywds, SCIENCE, $p->id);
    $loc_keywds = keyword_subset(LOCATION);
    $loc_fracs = keyword_fracs($loc_keywds, LOCATION, $p->id);
    echo "<h3>Science area keywords</h3>";
    keyword_checkboxes($sci_keywds, $sci_fracs);
    echo "<h3>Location keywords</h3>";
    keyword_checkboxes($loc_keywds, $loc_fracs);
    form_submit('OK');
    form_end();
    page_tail();
}

// return array of kwID=>frac from GET args
//
function get_kw_ids() {
    global $job_keywords;
    $x = array();
    foreach ($job_keywords as $kwid=>$kw) {
        $name ="kw_$kwid";
        if (get_str($name, true)) {
            $x[$kwid] = get_int("kwf_$kwid")/100.;
        }
    }
    return $x;
}

// given a new list of keyword IDs for a given project,
// add or remove project/keyword associations
//
function update_keywords($p, $new_kw_ids) {
    // get current list of keywords associations
    //
    $old_kw_assocs = SUProjectKeyword::enum("project_id=$p->id");

    // get corresponding list of keyword IDs
    //
    $old_kw_ids = array_map(
        function($x){return $x->keyword_id;},
        $old_kw_assocs
    );

    // remove old ones not in new list
    //
    foreach ($old_kw_ids as $id) {
        if (!array_key_exists($id, $new_kw_ids)) {
            //echo "$id not in list - deleting\n";
            $pkw = new SUProjectKeyword;
            $pkw->project_id = $p->id;
            $pkw->keyword_id = $id;
            $ret = $pkw->delete();
            if (!$ret) {
                error_page("keyword delete failed");
            }
        }
    }

    // add new ones
    //
    foreach ($new_kw_ids as $id=>$frac) {
        if (!in_array($id, $old_kw_ids)) {
            $ret = SUProjectKeyword::insert(
                "(project_id, keyword_id, work_fraction) values ($p->id, $id, $frac)"
            );
            if (!$ret) {
                error_page("keyword insert failed");
            }
        }
    }
}

function edit_project_action() {
    $id = get_int('id');
    $name = get_str('name');
    $alloc = get_str('alloc');
    $status = get_int('status');
    $p = SUProject::lookup_id($id);
    if (!$p) {
        error_page("no such project");
    }
    if ($p->name != $name || $p->allocation != $alloc || $p->status != $status) {
        $p->update("name='$name', allocation=$alloc, status=$status");
    }

    // add or remove existing keywords
    //
    update_keywords($p, get_kw_ids());

    Header("Location: su_projects_edit.php");
}

admin_only();

$action = get_str('action', true);

switch($action) {
case null:
case 'show_projects':
    page_head('Projects');
    show_projects();
    echo '<p></p><a href="su_manage.php">Return to Admin page</a>';
    page_tail();
    break;
case 'show_project':
    su_show_project();
    break;
case 'add_project_form':
    add_project_form();
    break;
case 'add_project_action':
    add_project_action();
    break;
case 'edit_project_form':
    edit_project_form();
    break;
case 'edit_project_action':
    edit_project_action();
    break;
default:
    error_page("no such action $action");
}

?>
