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

function su_contribution($user) {
    panel("Contribution",
        function() {
            start_table();
            row2("Your computers", '<a href=su_hosts.php class="btn btn-success">View</a>', false, "50%");
            row2("Projects you've supported", '<a href=su_user_projects.php class="btn btn-success">View</a>');
            row2("History", '<a href=su_user_accounting.php class="btn btn-success">View</a>');
            end_table();
        }
    );
}

function su_community($user) {
    panel("Community", function() use($user) {
        start_table();
        show_community_private($user);
        end_table();
    });
}

function su_settings($user) {
    panel("Settings",
        function() {
            start_table();
            row2(
                "Science area and location</br><small>Choose the types of research you want to support</small>",
                '<a href=su_prefs.php class="btn btn-success">Edit</a>',
                false, "70%"
            );
            row2(
                "Computing</br><small>Choose how to use your computers</small>",
                '<a href=su_compute_prefs.php class="btn btn-success">Edit</a>'
            );
            row2(
                "Community</br><small>Settings for message boards and private messages</small>",
                '<a href=edit_forum_preferences_form.php class="btn btn-success">Edit</a>'
            );
            row2(
                "Account</br><small>Name, password, email address</small>",
                '<a href=su_account_settings.php class="btn btn-success">Edit</a>'
            );
            row2(
                "Email",
                '<a href=su_email_prefs.php class="btn btn-success">Edit</a>'
            );
            end_table();
        }
    );
}

function main($user) {
    show_problem_accounts($user);
    grid(
        null,
        function() use ($user) {
            su_contribution($user);
        },
        function() use ($user) {
            su_community($user);
            su_settings($user);
        }
    );
}

// show the home page of logged-in user

$user = get_logged_in_user();
BoincForumPrefs::lookup($user);

page_head(tra("Your home page"));

main($user);

page_tail();

?>
