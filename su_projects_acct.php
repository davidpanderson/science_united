<?php

require_once("../inc/util.inc");
require_once("../inc/su_db.inc");

function show_projects_acct() {
    $projects = SUProject::enum("");
    start_table('table-striped');
    row_heading_array(array(
        "Name<br>click for details",
        "CPU time",
        "CPU FLOPS",
        "GPU time",
        "GPU FLOPS",
        "# jobs success",
        "# jobs fail",
    ));
    foreach ($projects as $p) {
        $ap = SUAccountingProject::last($p->id);
        row_array(array(
            '<a href="su_projects_acct.php?project_id='.$p->id.'">'.$p->name.'</a>',
            $ap->cpu_time_total,
            $ap->cpu_ec_total,
            $ap->gpu_time_total,
            $ap->gpu_ec_total,
            $ap->njobs_success_total,
            $ap->njobs_fail_total,
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
    page_tail();
} else {
    page_head("Project accounting");
    show_projects_acct();
    page_tail();
}

?>
