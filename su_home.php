<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2018 University of California
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
    panel(tra("Contribution"),
        function() use($user) {
            start_table();
            $view = tra("View");
            row2(
                tra("Your computers"),
                button_text('su_hosts.php', $view),
                false, "50%"
            );
            row2(
                tra("Computing: graphs"),
                button_text("su_graph2.php?userid=$user->id&what=ec&ndays=365", $view)
            );
            row2(
                tra("Computing: details"),
                button_text('su_user_accounting.php', $view)
            );
            row2(
                tra("Science projects"),
                button_text('su_user_projects.php', $view)
            );
            row2(
                tra("Certificate"),
                button_text('su_cert.php', $view)
            );
            end_table();
        }
    );
}

function su_community($user) {
    panel(tra("Community"), function() use($user) {
        start_table();
        show_community_private($user);
        row2('Leaderboards', '<a href=su_lb.php>View</a>');
        end_table();
    });
}

function su_settings($user) {
    panel(tra("Settings"),
        function() {
            start_table();
            $edit = tra("Edit");
            row2(
                tra("Science areas and locations")."</br><small>".tra("Choose the types of research you want to support")."</small>",
                button_text('su_prefs.php', $edit),
                false, "70%"
            );
            row2(
                tra("Computing")."</br><small>".tra("Choose how to use your computers")."</small>",
                button_text('su_compute_prefs.php', $edit)
            );
            row2(
                tra("Community")."</br><small>".tra("Settings for message boards and private messages")."</small>",
                button_text('edit_forum_preferences_form.php', $edit)
            );
            row2(
                tra("Account")."</br><small>".tra("Name, password, email address")."</small>",
                button_text('su_account_settings.php', $edit)
            );
            row2(
                tra("Email")."</br><small>".tra("When and whether we should email you")."</small>",
                button_text('su_email_prefs.php', $edit)
            );
            end_table();
        }
    );
}

function main($user) {
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
