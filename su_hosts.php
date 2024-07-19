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
function show_user_hosts_page($user, $show_all, $is_me) {
    $hosts = BoincHost::enum(
        "userid=$user->id order by domain_name, rpc_time desc"
    );
    if ($is_me) {
        page_head(tra("Your computers"));
    } else {
        page_head(sprintf('%s\'s computers', $user->name));
    }
    if (count($hosts) == 0) {
        echo tra("No computers yet");
        page_tail();
        return;
    }
    start_table("table-striped");
    row_heading_array([
        tra("Computer name"),
        tra("Operating system"),
        tra("Hardware/software info"),
        tra("Science United info"),
        tra("Last contact")
    ]);
    $now = time();
    $have_hidden = False;
    foreach($hosts as $h) {
        if ($h->total_credit) {
            $have_hidden = true;
            if (!$show_all) continue;
        }
        row_array([
            $h->domain_name,
            $h->os_name,
            sprintf(
                '<a href=su_hosts.php?action=hw_sw_info&user_id=%d&host_id=%d>view</a>',
                $user->id, $h->id
            ),
            sprintf(
                '<a href=su_hosts.php?action=su_info&user_id=%d&host_id=%d>view</a>',
                $user->id, $h->id
            ),
            time_diff_str($h->rpc_time, $now)
        ]);
    }
    end_table();
    if ($is_me && $have_hidden) {
        if ($show_all) {
            echo "<p>Showing all computers.
                <a href=su_hosts.php>Don't show hidden computers</a>.
            ";
        } else {
            echo "<p>Some of your computers are hidden.
                <a href=su_hosts.php?show_all=1>Show all computers</a>.
            ";
        }
    }
    page_tail();
}

function hide_host($user, $hidden) {
    $id = get_int("host_id");
    $h = BoincHost::lookup_id($id);
    if (!$h || $h->userid != $user->id) {
        error_page("no such host");
    }
    if ($hidden) {
        $h->update("total_credit=-1");      // otherwise unused field
    } else {
        $h->update("total_credit=0");
    }
}

function host_project_select($user, $host) {
    echo tra('To decide which projects this computer should work for, we compute a "score" for each project.  The components of the score are:');
    echo sprintf("
        <ul>
        <li><b>%s</b>: %s
        <li><b>%s</b>: %s
        <li><b>%s</b>: %s
        </ul>",
        tra("Keyword score"),
        tra("How well your keyword preferences match the project's keywords."),
        tra("Platform score"),
        tra("-1 if the project has no programs that will run on the computer; 0 if it does; +0.1 if one of them uses a GPU or VirtualBox."),
        tra("Allocation score"),
        tra("How much work is owed to the project; this changes from one day to the next.")
    );
    echo tra("We then choose the highest-scoring projects (at least 2) that together can use all the computer's processors."
    );
    echo "
        <p>
    ";
    $projects = rank_projects($user, $host, null, false);
    start_table("table-striped");
    $x = array(
        tra("Project"),
        tra("Keyword score"),
        tra("Platform score"),
        tra("Allocation score"),
        tra("Score")
    );
    foreach ($host->resources as $r) {
        $x[] = tra("Can use %1?", human_resource_name($r));
    }
    $x[] = tra("Opted out?");
    row_heading_array($x);
    foreach($projects as $p) {
        $x = array(
            "<a href=su_show_project.php?id=$p->id>$p->name</a>",
            $p->keyword_score,
            $p->platform_score,
            number_format($p->allocation_score, 3),
            number_format($p->score, 3)
        );
        foreach ($host->resources as $r) {
            $x[] = can_use($p, $host, $r) ? "yes" : "no";
        }
        $x[] = $p->opt_out?tra("Yes"):"";
        row_array($x);
    }
    end_table();

    echo tra("Given the above info, this computer would do work for these projects:");
    echo "
    <p>
    ";
    $projects = select_projects_resource($host, $projects);
    start_table("table-striped");
    $x = array(tra("Project"), tra("Score"));
    foreach ($host->resources as $r) {
        $x[] = "Use $r?";
    }
    row_heading_array($x);
    foreach($projects as $p) {
        $x = array(
            "<a href=su_show_project.php?id=$p->id>$p->name</a>",
            $p->score,
        );
        foreach ($host->resources as $r) {
            $x[] = $p->use[$r] ? tra("Yes") : tra("No");
        }
        row_array($x);
    }
    end_table();
}

function hw_sw_info_page($host, $user, $is_me) {
    page_head(tra("Computer hardware and software"));
    start_table("table-striped");
    if (!$is_me) {
        row2('Owner',
            sprintf('<a href=su_show_user.php?userid=%d>%s</a>',
                $user->id, $user->name
            )
        );
    }
    show_host_hw_sw($host);
    end_table();
    page_tail();
}

function host_project_accounting($host) {
    $ps = SUHostProject::enum("host_id=$host->id");
    start_table("table-striped");
    row_heading_array(array(
        tra("Name"),
        tra("CPU days"),
        tra("GPU days"),
        tra("# jobs success"),
        tra("#jobs fail")
    ));
    foreach ($ps as $p) {
        $project = SUProject::lookup_id($p->project_id);
        if ($p->cpu_time || $p->gpu_time || $p->njobs_success || $p->njobs_fail) {
            row_array(array(
                $project->name,
                show_days($p->cpu_time),
                show_days($p->gpu_time),
                $p->njobs_success, $p->njobs_fail
            ));
        }
    }
    end_table();
}

// show SU-specific info about this host
//
function su_info_page($host, $is_me) {
    global $user;
    page_head(tra("Science United computer info"));
    start_table("table-striped");
    $view = tra("View");
    row2(tra("Name"), $host->domain_name);
    show_host_detail($host);
    if ($is_me) {
        row2(
            "Location<br><small>determines which set of computing preferences is used for this computer",
            location_form($host)
        );
        if ($host->total_credit) {
            row2(
                'This computer is currently hidden',
                sprintf(
                    '<a class="btn btn-success" href="su_hosts.php?action=hide&host_id=%d&hidden=0">%s</a>',
                    $host->id, 'Un-hide'
                )
            );
        } else {
            row2(
                'Hide this computer<br><small>If this is a duplicate or the computer is no longer running BOINC.</small>',
                sprintf(
                    '<a class="btn" %s href="su_hosts.php?action=hide&host_id=%d&hidden=1">%s</a>',
                    button_style(),
                    $host->id, 'Hide'
                )
            );
        }
    }
    end_table();

    echo sprintf('<h3>%s</h3>',
    tra("Projects for which this computer has done work")
    );
    host_project_accounting($host);

    echo sprintf('<h3>%s</h3>',
        tra("How projects are chosen for %1", $host->domain_name)
    );
    $host = populate_score_info($host, null);
    host_project_select($user, $host);

    page_tail();
}

$id = get_int('user_id', true);
if ($id) {
    $user = BoincUser::lookup_id($id);
    if (!$user) error_page('no user');
    if (!$user->donated) error_page('no access');
    $is_me = false;
} else {
    $user = get_logged_in_user();
    $is_me = true;
}
$action = get_str("action", true);
switch ($action) {
case "hide":
    if (!$is_me) break;
    hide_host($user, get_int('hidden'));
    show_user_hosts_page($user, false, true);
    break;
case "su_info":
    $host = BoincHost::lookup_id(get_int("host_id"));
    if ($host->userid != $user->id) error_page('no access');
    su_info_page($host, $is_me);
    break;
case "hw_sw_info":
    $host = BoincHost::lookup_id(get_int("host_id"));
    if ($host->userid != $user->id) error_page('no access');
    hw_sw_info_page($host, $user, $is_me);
    break;
default:
    $show_all = $is_me?get_int('show_all', true):false;
    show_user_hosts_page($user, $show_all, $is_me);
}
