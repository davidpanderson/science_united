<?php
// This file is part of BOINC.
// https://boinc.berkeley.edu
// Copyright (C) 2024 University of California
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

// show info about a given user

require_once("../inc/util.inc");
require_once("../inc/boinc_db.inc");
require_once("../inc/user.inc");
require_once("../inc/forum.inc");
require_once("../project/project.inc");
require_once("../inc/su.inc");
require_once("../inc/su_graph.inc");

function main($id) {
    $user = BoincUser::lookup_id($id);
    if (!$user) {
        error_page("No such user");
    }
    if (strstr($user->authenticator, "deleted")) {
        error_page("No such user");
    }
    BoincForumPrefs::lookup($user);
    $community_links =  get_community_links_object($user);

    $logged_in_user = get_logged_in_user(false);

    page_head($user->name);
    start_table();
    echo "<tr><td valign=top>";

    // left column

    start_table("table-striped");
    show_user_summary_public($user);
    if ($user->donated) {
        $a = get_work_info($id);
        if ($a) {
            row2('Total TFLOPs',
                number_format(ec_to_tflops($a->ec_total), 2)
            );
            row2('Recent TFLOPs per day',
                number_format(ec_to_tflops($a->ec_avg), 2)
            );
            row2('Total processor time (days)',
                number_format($a->time_total/86400, 2)
            );
            row2('Recent processor time (days per day)',
                number_format($a->time_avg/86400, 2)
            );
            row2('Computers',
                sprintf('<a href=su_hosts.php?user_id=%d>View</a>', $id)
            );
        }
    }
    end_table();
    show_user_graph($user, 'ec', 30);

    echo "</td><td valign=top>";

    // right column

    start_table("table-striped");
    show_badges_row(true, $user);
    if (!DISABLE_PROFILES) {
        show_profile_link($user);
    }
    community_links($community_links, $logged_in_user);
    end_table();
    echo "</td></tr>";
    end_table();
    page_tail(true);
}

$userid = get_int('userid');
main($userid);

?>
