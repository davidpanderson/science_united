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

// show project accounting, either for all projects for for a particular one

require_once("../inc/util.inc");
require_once("../inc/su.inc");

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
    $max_cpu_time_total = 0;
    $max_gpu_time_total = 0;
    $max_cpu_ec_total = 0;
    $max_gpu_ec_total = 0;
    $max_njobs_success_total = 0;
    $max_njobs_fail_total = 0;
    $max_balance = 0;
    foreach ($projects as $p) {
        $ap = SUAccountingProject::last($p->id);
        $p->acct = $ap;
        if (!$ap) continue;
        if ($ap->cpu_time_total > $max_cpu_time_total) {
            $max_cpu_time_total = $ap->cpu_time_total;
        }
        if ($ap->gpu_time_total > $max_gpu_time_total) {
            $max_gpu_time_total = $ap->gpu_time_total;
        }
        if ($ap->cpu_ec_total > $max_cpu_ec_total) {
            $max_cpu_ec_total = $ap->cpu_ec_total;
        }
        if ($ap->gpu_ec_total > $max_gpu_ec_total) {
            $max_gpu_ec_total = $ap->gpu_ec_total;
        }
        if ($ap->njobs_success_total > $max_njobs_success_total) {
            $max_njobs_success_total = $ap->njobs_success_total;
        }
        if ($ap->njobs_fail_total > $max_njobs_fail_total) {
            $max_njobs_fail_total = $ap->njobs_fail_total;
        }
        if ($p->balance > $max_balance) {
            $max_balance = $p->balance;
        }
    }
    foreach ($projects as $p) {
        $ap = $p->acct;
        if (!$ap) continue;
        row_array(array(
            '<a href="su_projects_acct.php?project_id='.$p->id.'">'.$p->name.'</a>',
            show_num_bar("#00ff00", 100, $ap->cpu_time_total/3600, $max_cpu_time_total/3600),
            show_num_bar("#00ff00", 100, ec_to_gflop_hours($ap->cpu_ec_total), ec_to_gflop_hours($max_cpu_ec_total)),
            show_num_bar("#00ff00", 100, $ap->gpu_time_total/3600, $max_gpu_time_total/3600),
            show_num_bar("#00ff00", 100, ec_to_gflop_hours($ap->gpu_ec_total), ec_to_gflop_hours($max_gpu_ec_total)),
            show_num_bar("#00ff00", 100, $ap->njobs_success_total, $max_njobs_success_total),
            show_num_bar("#ff0000", 100, $ap->njobs_fail_total, $max_njobs_fail_total),
            show_num_bar("#00ff00", 100, $p->balance, $max_balance, -1)
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
