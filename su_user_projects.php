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

function show_projects($user) {
    page_head("Projects");
    $accounts = SUAccount::enum("user_id = $user->id");
    if (count($accounts) == 0) {
        echo "No accounts yet";
    } else {
        $first = true;
        foreach ($accounts as $a) {
            if ($a->state != ACCT_SUCCESS) continue;
            if ($first) {
                echo "<h2>Accounts</h2>\n";
                start_table();
                row_heading_array(array(
                    "Name", "since", "CPU time", "GPU time",
                    "# successful jobs", "# failed jobs"
                ));
                $first = false;
            }
            $project = SUProject::lookup_id($a->project_id);
            row_array(array(
                "<a href=su_user_projects.php?project_id=$project->id>$project->name</a>",
                date_str($a->create_time),
                $a->cpu_time,
                $a->gpu_time,
                $a->njobs_success,
                $a->njobs_fail
            ));
        }
        if (!$first) {
            end_table();
        }

        $first = true;
        foreach ($accounts as $a) {
            if ($a->state == ACCT_SUCCESS) continue;
            if ($first) {
                echo "<h2>Accounts in progress</h2>\n";
                start_table();
                row_heading_array(array("Name", "status", "retry"));
                $first = false;
            }
            $project = SUProject::lookup_id($a->project_id);
            $x = $a->state==ACCT_DIFFERENT_PASSWORD?" <a href=su_connect.php?id=$project->id>(resolve)</a>":"";
            row_array(array(
                "<a href=su_user_projects.php?project_id=$project->id>$project->name</a>",
                account_status_string($a->state).$x,
                time_str($a->retry_time)
            ));
        }
        if (!$first) {
            end_table();
        }
    }
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
    row2("Science keywords", "");
    row2("Location keywords", "");
    row2("CPU computing", $acct->cpu_ec);
    row2("CPU time", $acct->cpu_time);
    if ($acct->gpu_ec) {
        row2("GPU computing", $acct->gpu_ec);
        row2("GPU time", $acct->gpu_time);
    }
    row2("# jobs succeeded", $acct->njobs_success);
    row2("# jobs failed", $acct->njobs_fail);
    end_table();
    echo "<a href=su_create_retry.php?project_id=$project_id>retry</a>";
    page_tail();
}

$user = get_logged_in_user();
$project_id = get_int("project_id", true);
if ($project_id) {
    su_show_project($user, $project_id);
} else {
    show_projects($user);
}

?>
