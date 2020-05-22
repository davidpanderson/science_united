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

function account_state_str($state) {
    switch ($state) {
    case ACCT_INIT:
        return tra("New");
    case ACCT_SUCCESS:
        return tra("Established");
    case ACCT_TRANSIENT_ERROR:
        return tra("In progress");
    }
    return tra("Unknown");
}
function project_row($p, $ukws, $a) {
    if ($a) {
        $x = account_state_str($a->state);
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
    page_head(tra("Science projects"));
    $accounts = SUAccount::enum("user_id = $user->id", "order by cpu_time desc");
    $project_infos = unserialize(file_get_contents("projects.ser"));
    foreach ($project_infos as $id=>$p) {
        $p->id = $id;
        $p->done = false;
    }
    $first = true;
    start_table();
    row_heading_array(array(
        tra("Name")."<br><small>".tra("Click for details")."</small>",
        tra("since"),
        tra("CPU hours"),
        tra("GPU hours"),
        tra("# successful jobs"),
        tra("# failed jobs"),
        tra("Account status"),
        tra("Allowed by prefs?"),
    ));

    $ukws = SUUserKeyword::enum("user_id=$user->id");

    // show projects w/ accounts
    //
    foreach ($accounts as $a) {
        if (!array_key_exists($a->project_id, $project_infos)) continue;
        if ($a->opt_out) continue;
        $p = $project_infos[$a->project_id];
        project_row($p, $ukws, $a);
        $p->done = true;
    }
    foreach ($accounts as $a) {
        if (!array_key_exists($a->project_id, $project_infos)) continue;
        if (!$a->opt_out) continue;
        $p = $project_infos[$a->project_id];
        project_row($p, $ukws, $a);
        $p->done = true;
    }

    // show other projects
    //
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
    page_head($project->name);
    start_table();
    row2(tra("Web site"), "<a href=$project->url>$project->url</a>");
    row2(tra("Science keywords"), project_kw_string($project->id, SCIENCE));
    row2(tra("Location keywords"), project_kw_string($project->id, LOCATION));
    if ($acct) {
        //row2(tra("First contribution"), date_str($acct->create_time));
        row2(tra("Account status"), account_status_string($acct->state));
        row2(tra("CPU computing"), $acct->cpu_ec);
        row2(tra("CPU time"), $acct->cpu_time);
        if ($acct->gpu_ec) {
            row2(tra("GPU computing"), $acct->gpu_ec);
            row2(tra("GPU time"), $acct->gpu_time);
        }
        row2(tra("# jobs succeeded"), $acct->njobs_success);
        row2(tra("# jobs failed"), $acct->njobs_fail);
        if ($acct->opt_out) {
            $x = tra("Yes")."&nbsp".button_text("su_user_projects.php?action=include&project_id=$project_id", tra("Include"));
        } else {
            $x = tra("No")."&nbsp".button_text("su_user_projects.php?action=exclude&project_id=$project_id", tra("Exclude"));
        }
        row2(
            tra("Excluded?")."<br><small>".tra("Use if this project causes problems on your computer")."</small>",
            $x
        );
    } else {
        row2(tra("Account status"), tra("None"));
    }
    row2("", "<a href=su_user_projects.php>".tra("Return to project list")."</a>");
    end_table();
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
