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

// show project(s) user is attached to

require_once("../inc/util.inc");
require_once("../inc/su.inc");
require_once("../inc/su_schedule.inc");

function project_row($p, $ukws, $a) {
    if ($a) {
        switch ($a->state) {
        case ACCT_INIT:
            $x = "New";
            break;
        case ACCT_SUCCESS:
            $x = "Established";
            break;
        case ACCT_TRANSIENT_ERROR:
            $x = "In progress";
            break;
        }
        $checked = $a->opt_out?"checked":"";
        $d = date_str($a->create_time);
        $c = $a->cpu_time/3600.;
        $g = $a->gpu_time/3600.;
        $njs = $a->njobs_success;
        $njf = $a->njobs_fail;
        $excluded = $a->opt_out?"; excluded":"";
    } else {
        $checked = "";
        $d = "---";
        $c = 0;
        $g = 0;
        $njs = 0;
        $njf = 0;
        $x = "---";
        $excluded = "";
    }
    row_array(array(
        sprintf('<a href=su_user_projects.php?project_id=%d>%s</a>',
            $p->id,
            $p->name
        ),
        $d,
        show_num($c),
        show_num($g),
        $njs,
        $njf,
        $x,
        (keywd_score($p->kws, $ukws)<0)?"no":"yes".$excluded
    ));
}

// show all the projects, with the ones user has contributed to at top.
//
function show_projects($user) {
    page_head("Science projects");
    $accounts = SUAccount::enum("user_id = $user->id", "order by cpu_time desc");
    $project_infos = unserialize(file_get_contents("projects.ser"));
    foreach ($project_infos as $id=>$p) {
        $p->id = $id;
        $p->done = false;
    }
    $first = true;
    start_table();
    row_heading_array(array(
        "Name<br><small>Click for details</small>", "since", "CPU hours", "GPU hours",
        "# successful jobs", "# failed jobs",
        "Account status",
        "Allowed by prefs?",
    ));

    $ukws = SUUserKeyword::enum("user_id=$user->id");

    // show projects w/ accounts
    //
    foreach ($accounts as $a) {
        if ($a->opt_out) continue;
        $p = $project_infos[$a->project_id];
        project_row($p, $ukws, $a);
        $p->done = true;
    }
    foreach ($accounts as $a) {
        if (!$a->opt_out) continue;
        $p = $project_infos[$a->project_id];
        project_row($p, $ukws, $a);
        $p->done = true;
    }

    // show other projects
    foreach ($project_infos as $id=>$p) {
        if ($p->done) continue;
        project_row($p, $ukws, null);
    }
    end_table();
    page_tail();
}

function su_show_project($user, $project_id) {
    $project = SUProject::lookup_id($project_id);
    if (!$project) die("no such project");
    $acct = SUAccount::lookup("user_id=$user->id and project_id=$project_id");
    if (!$acct) die("no account");
    page_head($project->name);
    start_table();
    row2("Web site", "<a href=$project->url>$project->url</a>");
    //row2("First contribution", date_str($acct->create_time));
    row2("Science keywords", project_kw_string($project->id, SCIENCE));
    row2("Location keywords", project_kw_string($project->id, LOCATION));
    row2("Account status", account_status_string($acct->state));
    row2("CPU computing", $acct->cpu_ec);
    row2("CPU time", $acct->cpu_time);
    if ($acct->gpu_ec) {
        row2("GPU computing", $acct->gpu_ec);
        row2("GPU time", $acct->gpu_time);
    }
    row2("# jobs succeeded", $acct->njobs_success);
    row2("# jobs failed", $acct->njobs_fail);
    if ($acct->opt_out) {
        $x = "Yes"."&nbsp".button_text("su_user_projects.php?action=include&project_id=$project_id", "Include");
    } else {
        $x = "No"."&nbsp".button_text("su_user_projects.php?action=exclude&project_id=$project_id", "Exclude");
    }
    row2(
        "Excluded?<br><small>Use if this project causes problems on your computer</small>",
        $x
    );
    row2("", "<a href=su_user_projects.php>Return to project list</a>");
    end_table();
    //echo "<a href=su_create_retry.php?project_id=$project_id>retry</a>";
    page_tail();
}

function do_opt_out($user, $project_id, $exclude) {
    $a = SUAccount::lookup("user_id=$user->id and project_id=$project_id");
    $x = $exclude?1:0;
    if ($a) {
        $ret = $a->update("opt_out = $x");
        if (!$ret) {
            error_page("SUAccount::update() failed");
        }
    } else {
        // opting out of a project w/ no account.
        // create one just for this purpose.
        //
        $ret = SUAccount::insert(
            sprintf("(project_id, user_id, create_time, state, opt_out) values (%d, %d, %f, %d, 1)",
                $p->id, $user->id, time(), ACCT_INIT, 1
            )
        );
        if (!$ret) {
            error_page("SUAccount::insert() failed");
        }
    }
    su_show_project($user, $project_id);
}

$user = get_logged_in_user();
$project_id = get_int("project_id", true);
$action = get_str("action", true);
if ($action == "exclude") {
    do_opt_out($user, $project_id, true);
} else if ($action == "include") {
    do_opt_out($user, $project_id, false);
} else if ($project_id) {
    su_show_project($user, $project_id);
} else {
    show_projects($user);
}

?>
