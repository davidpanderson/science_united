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
require_once("../inc/user.inc");
require_once("../inc/forum.inc");
require_once("../inc/su_user.inc");

function su_user_stats($user) {
}

function su_preference_links() {
    panel("Computing",
        function() {
            echo "<p><a href=su_prefs.php>Science preferences</a>";
            echo "<p><a href=su_compute_prefs.php>Computing preferences</a>";
            echo "<p><a href=su_hosts.php>Your computers</a>";
            echo "<p><a href=su_user_projects.php>Projects</a>";
            echo "<p><a href=su_user_accounting.php>Accounting</a>";
        }
    );
}

function su_show_account_private($user) {
    show_problem_accounts($user);
    grid(
        false,
        function() use ($user) {
            su_preference_links();
            su_user_stats($user);
        },
        function() use ($user) {
            panel("Account info", function() use($user) {
                start_table();
                show_user_info_private($user);
                end_table();
            });
            panel("Community", function() use($user) {
                start_table();
                show_community_private($user);
                table_row("", "<a href=edit_form_preferences_form.php>Community preferences</a>");
                end_table();
            });
        }
    );
}

// show the home page of logged-in user

$user = get_logged_in_user();
BoincForumPrefs::lookup($user);

page_head(tra("Your account"));

su_show_account_private($user);

page_tail();

?>
