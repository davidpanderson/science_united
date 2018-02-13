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
        case ACCT_DIFFERENT_PASSWORD:
            $x = "Password mismatch<br><a href=su_connect.php?id=$project->id>resolve</a>";
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
    } else {
        $checked = "";
        $d = "---";
        $c = 0;
        $g = 0;
        $njs = 0;
        $njf = 0;
        $x = "---";
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
        (keywd_score($p->kws, $ukws)<0)?"no":"yes",
        sprintf('<input type="checkbox" name="optout_%d" %s>',
            $p->id,
            $checked
        )
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
    form_start("su_user_projects.php");
    form_input_hidden("action", "opt_out");
    start_table();
    row_heading_array(array(
        "Name", "since", "CPU hours", "GPU hours",
        "# successful jobs", "# failed jobs",
        "Account status",
        "Allowed by prefs?",
        "Opt out?<br><small>Use Update button at bottom of page</small>"
    ));

    $ukws = SUUserKeyword::enum("user_id=$user->id");

    // show projects w/ accounts
    //
    foreach ($accounts as $a) {
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
    form_submit("Update opt-out selections");
    form_end();
    page_tail();
}

function su_show_project($user, $project_id) {
    $project = SUProject::lookup_id($project_id);
    if (!$project) die("no such project");
    $acct = SUAccount::lookup("user_id=$user->id and project_id=$project_id");
    if (!$acct) die("no account");
    page_head($project->name);
    start_table();
    row2("First contribution", date_str($acct->create_time));
    row2("Account status", account_status_string($acct->state));
    row2("Science keywords", project_kw_string($project->id, SCIENCE));
    row2("Location keywords", project_kw_string($project->id, LOCATION));
    row2("CPU computing", $acct->cpu_ec);
    row2("CPU time", $acct->cpu_time);
    if ($acct->gpu_ec) {
        row2("GPU computing", $acct->gpu_ec);
        row2("GPU time", $acct->gpu_time);
    }
    row2("# jobs succeeded", $acct->njobs_success);
    row2("# jobs failed", $acct->njobs_fail);
    if ($acct->opt_out) {
        row2("Opt out", "Yes");
    }
    end_table();
    //echo "<a href=su_create_retry.php?project_id=$project_id>retry</a>";
    page_tail();
}

function do_opt_out($user) {
    $projects = unserialize(file_get_contents("projects.ser"));
    $accounts = SUAccount::enum("user_id = $user->id");
    foreach ($projects as $id=>$p) {
        $p->done = false;
    }
    foreach ($accounts as $a) {
        $pid = $a->project_id;
        if (get_str("optout_$pid", true)) {
            if (!$a->opt_out) {
                $a->update("opt_out = 1");
            }
        } else {
            if ($a->opt_out) {
                $a->update("opt_out = 0");
            }
        }
        $projects[$pid]->done = true;
    }
    foreach ($projects as $p) {
        if ($p->done) continue;
        if (get_str("optout_$pid", true)) {
            // opting out of a project w/ no account.
            // create one just for this purpose.
            $ret = SUAccount::insert(
                sprintf("(project_id, user_id, create_time, state, opt_out) values (%d, %d, %f, %d)",
                    $p->id, $user->id, time(), ACCT_INIT, 1
                )
            );
        }
    }
}

$user = get_logged_in_user();
$project_id = get_int("project_id", true);
$action = get_str("action", true);
if ($project_id) {
    su_show_project($user, $project_id);
} else if ($action == "opt_out") {
    do_opt_out($user);
    show_projects($user);
} else {
    show_projects($user);
}

?>
