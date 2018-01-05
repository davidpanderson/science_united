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

require_once("../inc/util.inc");
require_once("../inc/host.inc");
require_once("../inc/su_schedule.inc");
require_once("../inc/su.inc");

// show list of user's hosts, minimal info, link to details
//
function su_show_user_hosts($user) {
    $hosts = BoincHost::enum("userid=$user->id and total_credit=0 order by domain_name, rpc_time desc");
    page_head("Your computers");
    if (count($hosts) == 0) {
        echo "No computers yet";
        page_tail();
        return;
    }
    start_table();
    row_heading_array(array("Name<br><small>Click for details</small>", "Operating system", "Last contact", "Remove<br><small>... if this is a duplicate or the computer is no longer running BOINC</small>"));
    $now = time();
    foreach($hosts as $h) {
        $link = sprintf('<a class="btn btn-success" "href=su_hosts.php?action=del&id=%d>Remove</a>',
            $h->id
        );
        row_array(array(
            sprintf("<a href=%s>%s</a>",
                "su_hosts.php?action=summary&host_id=$h->id", $h->domain_name
            ),
            $h->os_name,
            time_diff_str($h->rpc_time, $now),
            $link
        ));
    }
    end_table();
    page_tail();
}

function su_hide_host() {
    $id = get_int("id");
    $user = get_logged_in_user();
    $h = BoincHost::lookup_id($id);
    if (!$h || $h->userid != $user->id) {
        error_page("no such host");
    }
    $h->update("total_credit=-1");      // otherwise unused field
}

function su_host_project_select($user, $host) {
    page_head("Project selection details for $host->domain_name");
    $projects = rank_projects($user, $host, null, false);
    start_table("table-striped");
    $x = array(
        "Project", "Keyword score", "Platform score", "Balance", "Score"
    );
    foreach ($host->resources as $r) {
        $x[] = "Can use $r";
    }
    row_heading_array($x);
    foreach($projects as $p) {
        $x = array(
            "<a href=su_show_project.php?id=$p->id>$p->url</a>",
            $p->keyword_score,
            $p->platform_score,
            $p->projected_balance,
            $p->score
        );
        foreach ($host->resources as $r) {
            $x[] = can_use($p, $host, $r) ? "yes" : "no";
        }
        row_array($x);
    }
    end_table();

    echo "Given the above info, this computer would do work for the followingprojects:
    ";
    $projects = select_projects_resource($host, $projects);
    start_table("table-striped");
    $x = array("Project", "Score");
    foreach ($host->resources as $r) {
        $x[] = "Use $r?";
    }
    row_heading_array($x);
    foreach($projects as $p) {
        $x = array(
            "<a href=su_show_project.php?id=$p->id>$p->url</a>",
            $p->score,
        );
        foreach ($host->resources as $r) {
            $x[] = $p->use[$r] ? "yes" : "no";
        }
        row_array($x);
    }
    end_table();
    page_tail();
}

function su_host_detail($host) {
    page_head("Computer hardware and software");
    start_table();
    show_host_hw_sw($host);
    end_table();
    page_tail();
}

function su_host_project_accounting($host) {
    page_head("Projects for which this computer has done work");
    $ps = SUHostProject::enum("host_id=$host->id");
    start_table();
    row_heading_array(array(
        "Name",
        "CPU FLOPS", "CPU time",
        "GPU FLOPS", "GPU time",
        "# jobs success", "#jobs fail"
    ));
    foreach ($ps as $p) {
        $project = SUProject::lookup_id($p->project_id);
        row_array(array(
            $project->name,
            $p->cpu_time, $p->cpu_ec,
            $p->gpu_time, $p->gpu_ec,
            $p->njobs_success, $p->njobs_fail
        ));
    }
    end_table();
    page_tail();
}

function su_host_summary($host) {
    page_head("Computer details");
    start_table("table-striped");
    row2("Name", $host->domain_name);
    row2("Hardware/software details", "<a href=su_hosts.php?action=detail&host_id=$host->id>View</a>");
    row2("Last heard from", time_str($host->rpc_time));
    row2("How projects are chosen for this computer", "<a href=su_hosts.php?action=project_select&host_id=$host->id>View</a>");
    row2("Projects for which this computer has done work", "<a href=su_hosts.php?action=project_accounting&host_id=$host->id>View</a>");
    show_host_detail($host);
    end_table();
    page_tail();
}

$user = get_logged_in_user();
$action = get_str("action", true);
if ($action == "del") {
    su_hide_host();
    su_show_user_hosts($user);
} else if ($action == "summary") {
    $host = BoincHost::lookup_id(get_int("host_id"));
    su_host_summary($host);
} else if ($action == "detail") {
    $host = BoincHost::lookup_id(get_int("host_id"));
    su_host_detail($host);
} else if ($action == "project_select") {
    $host = BoincHost::lookup_id(get_int("host_id"));
    $host = populate_score_info($host, null);
    su_host_project_select($user, $host);
} else if ($action == "project_accounting") {
    $host = BoincHost::lookup_id(get_int("host_id"));
    su_host_project_accounting($host);
} else {
    su_show_user_hosts($user);
}
