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

function su_show_user_hosts($user) {
    $hosts = BoincHost::enum("userid=$user->id order by domain_name, rpc_time desc");
    page_head("Your computers");
    start_table();
    row_heading_array(array("Name<br><small>Click for details</small>", "CPU", "GPU", "Operating system", "last contact", "Remove duplicate"));
    $now = time();
    $last_name = "";
    foreach($hosts as $h) {
        $link = "";
        if ($h->domain_name == $last_name) {
            $link = "<a href=su_hosts.php?action=del&id=$h->id>Remove</a>";
        }
        $last_name = $h->domain_name;
        row_array(array(
            sprintf("<a href=%s>%s</a>",
                "su_hosts.php?action=detail&host_id=$h->id", $h->domain_name
            ),
            $h->p_vendor,
            gpu_desc($h->serialnum, false),
            $h->os_name,
            time_diff_str($h->rpc_time, $now),
            $link
        ));
    }
    end_table();
}

function su_delete_host() {
    $id = get_int("id");
    $user = get_logged_in_user();
    $h = BoincHost::lookup_id($id);
    if (!$h || $h->userid != $user->id) {
        error_page("no such host");
    }
    $h->delete();
}

function su_host_detail($user, $host) {
    page_head("Project score details for $host->domain_name");
    $projects = rank_projects($user, $host, null);
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
            "<a href=>$p->url</a>,
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
    page_tail();
}

$user = get_logged_in_user();
$action = get_str("action", true);
if ($action == "del") {
    su_delete_host();
    su_show_user_hosts($user);
} else if ($action == "detail") {
    $id = get_int("host_id");
    $host = BoincHost::lookup_id($id);
    $host = populate_host($host, null);
    su_host_detail($user, $host);
} else {
    su_show_user_hosts($user);
}
