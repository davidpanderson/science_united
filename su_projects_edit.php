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
require_once("../inc/su_project_infos.inc");
require_once("../inc/keywords.inc");

function show_projects() {
    $projects = SUproject::enum("", 'order by name');
    if ($projects) {
        start_table('table-striped');
        row_heading_array(array(
            'Name<br><small>click for details</small>',
            'URL',
            'Science keywords',
            'Location keywords',
            'Created',
            'Share',
            'Status'
        ));
        foreach ($projects as $p) {
            table_row(
                '<a href="su_show_project.php?id='.$p->id.'">'.$p->name.'</a>',
                $p->url,
                project_kw_string($p->id, SCIENCE),
                project_kw_string($p->id, LOCATION),
                date_str($p->create_time),
                $p->share,
                project_status_string($p->status)
            );
        }
        end_table();
    } else {
        echo 'No projects yet';
    }
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
    global $project_infos;
    $x = array();
    foreach ($keywords as $id=>$word) {
        $x[$id] = -1;
    }

    $pks = $project_infos[$project_id]->kws;
    foreach ($pks as $pk) {
        $x[$pk->keyword_id] = $pk->fraction;
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

function edit_project_form() {
    $id = get_int('id');
    $p = SUProject::lookup_id($id);
    page_head("Edit project $p->name");
    form_start('su_projects_edit.php');
    form_input_hidden('action', 'edit_project_action');
    form_input_hidden('id', $id);
    form_input_text('Share', 'share', $p->share, 'number');
    form_radio_buttons('Status', 'status',
        array(
            array('0', 'hidden'),
            array('2', 'normal')
        ),
        $p->status
    );
    form_submit('OK');
    form_end();
    page_tail();
}

function edit_project_action() {
    $id = get_int('id');
    $share = get_str('share');
    $status = get_int('status');
    $p = SUProject::lookup_id($id);
    if (!$p) {
        error_page("no such project");
    }
    if ($p->share != $share || $p->status != $status) {
        $p->update("share=$share, status=$status");
    }

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
