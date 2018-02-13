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

// show project accounting

require_once("../inc/util.inc");
require_once("../inc/su_db.inc");
require_once("../inc/su_util.inc");

function show_projects_acct() {
    $projects = SUProject::enum("");
    start_table('table-striped');
    row_heading_array(array(
        "Name<br>click for details",
        "CPU hours",
        "CPU GFLOP/hours",
        "GPU hours",
        "GPU GFLOP/hours",
        "# jobs success",
        "# jobs fail",
        "Balance"
    ));
    foreach ($projects as $p) {
        $ap = SUAccountingProject::last($p->id);
        if (!$ap) continue;
        row_array(array(
            '<a href="su_projects_acct.php?project_id='.$p->id.'">'.$p->name.'</a>',
            show_num($ap->cpu_time_total/3600),
            show_num(ec_to_gflop_hours($ap->cpu_ec_total)),
            show_num($ap->gpu_time_total/3600),
            show_num(ec_to_gflop_hours($ap->gpu_ec_total)),
            $ap->njobs_success_total,
            $ap->njobs_fail_total,
            $p->balance
        ));
    }
    end_table();
}

function show_project_acct($p) {
    $aps = SUAccountingProject::enum("project_id=$p->id", "order by id desc");
    start_table('table-striped');
    $first = true;
    row_heading_array(array(
        "When",
        "CPU time",
        "CPU FLOPS",
        "GPU time",
        "GPU FLOPS",
        "# jobs success",
        "# jobs fail",
    ));
    foreach ($aps as $ap) {
        if ($first) {
            $first = false;
            row_array(array(
                "Totals",
                $ap->cpu_time_total,
                $ap->cpu_ec_total,
                $ap->gpu_time_total,
                $ap->gpu_ec_total,
                $ap->njobs_success_total,
                $ap->njobs_fail_total,
            ));
        }
        row_array(array(
            date_str($ap->create_time),
            $ap->cpu_time_delta,
            $ap->cpu_ec_delta,
            $ap->gpu_time_delta,
            $ap->gpu_ec_delta,
            $ap->njobs_success_delta,
            $ap->njobs_fail_delta,
        ));
    }
    end_table();
}

$project_id = get_int('project_id', true);

if ($project_id) {
    $project = SUProject::lookup_id($project_id);
    if (!$project) {
        error_page('no such project');
    }
    page_head("$project->name accounting");
    show_project_acct($project);
    echo '<p><a href="su_manage.php">Return to admin page</a>';
    page_tail();
} else {
    page_head("Project accounting");
    show_projects_acct();
    echo '<p><a href="su_manage.php">Return to admin page</a>';
    page_tail();
}

?>
