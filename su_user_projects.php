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

// show projects user is attached to

require_once("../inc/util.inc");
require_once("../inc/su.inc");

function main() {
    page_head("Projects");
    $user = get_logged_in_user();
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
                $project->name,
                "tbd",
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
                $project->name,
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

main();

?>
