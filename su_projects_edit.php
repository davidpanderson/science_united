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

require_once("../inc/util.inc");
require_once("../inc/su_db.inc");
require_once("../inc/su.inc");

function array_to_string($arr) {
    $x = false;
    foreach ($arr as $wd) {
        if ($x) {
            $x .= ", $wd";
        } else {
            $x = $wd;
        }
    }
    return $x;
}

function su_show_project() {
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
    $pks = SUProjectKeyword::enum("project_id=$project->id");
    $sci = array();
    $loc = array();
    if ($pks) {
        foreach ($pks as $pk) {
            $kwd = SUKeyword::lookup_id($pk->keyword_id);
            if ($kwd->category == SCIENCE) {
                $sci[] = $kwd->word;
            }
            if ($kwd->category == LOCATION) {
                $loc[] = $kwd->word;
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
    $x = "";
    $pkws = SUProjectKeyword::enum("project_id=$p->id");
    $first = true;
    foreach ($pkws as $pkw) {
        $kw = SUKeyword::lookup_id($pkw->keyword_id);
        if ($kw->category != $category) continue;
        if (!$first) $x .= ', ';
        $first = false;
        $x .= $kw->word;
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
            'Allocation'
        ));
        foreach ($projects as $p) {
            table_row(
                '<a href="su_projects_edit.php?action=show_project&id='.$p->id.'">'.$p->name.'</a>',
                $p->url,
                project_kw_string($p, SCIENCE),
                project_kw_string($p, LOCATION),
                date_str($p->create_time),
                $p->allocation
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

// return list of all keywords of given category
// List items are array (id, word)
//
function keyword_subset($keywords, $category) {
    $x = array();
    foreach ($keywords as $k) {
        if ($k->category != $category) continue;
        $x[] = array($k->id, $k->word);
    }
    return $x;
}

// given keyword list (of the form above),
// return boolean array of ones a project has
//
function keyword_flags($keywords, $category, $project_id) {
    $pks = SUProjectKeyword::enum("project_id=$project_id");
    $pwords = array();
    foreach ($pks as $pk) {
        // could do this more efficiently with a join
        //
        $kw = SUKeyword::lookup_id($pk->keyword_id);
        $pwords[] = $kw->id;
    }

    $flags = array();
    foreach ($keywords as $k) {
        $flags[] = in_array($k[0], $pwords);
    }
    return $flags;
}

// generate checkboxes for the given keywords.
// names are of the form kw_ID
//
function keyword_checkboxes($kws, $flags) {
    start_table();
    $i = 0;
    foreach ($kws as $kw) {
        $checked = $flags?($flags[$i]?"checked":""):"";
        row2($kw[1],
            sprintf('<input type="checkbox" name="kw_%s" %s>',
                $kw[0],
                $checked
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
    $keywds = SUKeyword::enum("");
    $sci_keywds = keyword_subset($keywds, SCIENCE);
    $loc_keywds = keyword_subset($keywds, LOCATION);
    if (count($sci_keywds)>0) {
        echo "<h3>Science keywords</h3>\n";
        keyword_checkboxes(
            $sci_keywds,
            null
        );
    }
    if (count($loc_keywds)>0) {
        echo "<h3>Location keywords</h3>\n";
        keyword_checkboxes(
            $loc_keywds,
            null
        );
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
    $keywds = SUKeyword::enum();
    $sci_keywds = keyword_subset($keywds, SCIENCE);
    $sci_flags = keyword_flags($sci_keywds, SCIENCE, $p->id);
    $loc_keywds = keyword_subset($keywds, LOCATION);
    $loc_flags = keyword_flags($loc_keywds, LOCATION, $p->id);
    keyword_checkboxes(
        $sci_keywds,
        $sci_flags
    );
    keyword_checkboxes(
        $loc_keywds,
        $loc_flags
    );
    form_submit('OK');
    form_end();
    page_tail();
}

// return list of kw IDs from GET args
//
function get_kw_ids() {
    $kws = SUKeyword::enum();
    $x = array();
    foreach ($kws as $kw) {
        $name ="kw_$kw->id";
        if (get_str($name, true)) {
            $x[] = $kw->id;
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

    // get corresponding list of keyword objects
    //
    $old_kws = array_map(
        function($x){return SUKeyword::lookup_id($x->keyword_id);},
        $old_kw_assocs
    );

    // and list of IDs
    //
    $old_kw_ids = array_map(function($x) {return $x->id;}, $old_kws);

    // remove old ones not in new list
    //
    foreach ($old_kw_ids as $id) {
        if (!in_array($id, $new_kw_ids)) {
            echo "$id not in list - deleting\n";
            $ret = SUProjectKeyword::delete(
                "project_id=$p->id and keyword_id=$id"
            );
            if (!$ret) {
                error_page("keyword delete failed");
            }
        }
    }

    // add new ones
    //
    foreach ($new_kw_ids as $id) {
        if (!in_array($id, $old_kw_ids)) {
            $ret = SUProjectKeyword::insert(
                "(project_id, keyword_id) values ($p->id, $id)"
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
    $p = SUProject::lookup_id($id);
    if (!$p) {
        error_page("no such project");
    }
    if ($p->name != $name || $p->allocation != $alloc) {
        $p->update("name='$name', allocation=$alloc");
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
