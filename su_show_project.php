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

// show detail page for a project

require_once("../inc/util.inc");
require_once("../inc/su_db.inc");
require_once("../inc/su.inc");
require_once("../inc/su_project_infos.inc");
require_once("../inc/keywords.inc");

function su_show_project($project) {
    global $job_keywords;
    global $project_infos;

    page_head($project->name);
    start_table();
    row2("name", $project->name);
    row2("URL", $project->url);
    row2("Created", date_str($project->create_time));
    row2("Share", $project->share);
    row2("Balance", $project->projected_balance);
    row2("Status", project_status_string($project->status));
    $pks = $project_infos[$project->id]->kws;
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
    row2("Science keywords", project_keyword_array_to_string($sci));
    row2("Location keywords", project_keyword_array_to_string($loc));
    row2("Platforms", get_platforms_string($project->id));
    end_table();
    echo '<a class="btn btn-success" href="su_projects_edit.php?action=edit_project_form&id='.$project->id.'">Edit project</a>
    ';
    page_tail();
}

$id = get_int('id');
$project = SUProject::lookup_id($id);
if (!$project) {
    error_page("no such project");
}
su_show_project($project);

?>
