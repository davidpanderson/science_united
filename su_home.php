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
        function() {
            start_table();
            $view = tra("View");
            row2(
                tra("Your computers"),
                '<a href=su_hosts.php class="btn btn-success">'.$view.'</a>',
                false, "50%"
            );
            row2(tra("Computing: graphs"), '<a href=su_user_accounting.php?graphs=1 class="btn btn-success">'.$view.'</a>');
            row2(tra("Computing: details"), '<a href=su_user_accounting.php class="btn btn-success">'.$view.'</a>');
            row2(tra("Science projects"), '<a href=su_user_projects.php class="btn btn-success">'.$view.'</a>');
            if (PROJECT == "BOINC Planet") {
                row2(tra("Claimed credit"), '<a href=bp_claim.php?display=1 class="btn btn-success">'.$view.'</a>');
            }
            end_table();
        }
    );
}

function su_community($user) {
    panel(tra("Community"), function() use($user) {
        start_table();
        show_community_private($user);
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
                '<a href=su_prefs.php class="btn btn-success">'.$edit.'</a>',
                false, "70%"
            );
            row2(
                tra("Computing")."</br><small>".tra("Choose how to use your computers")."</small>",
                '<a href=su_compute_prefs.php class="btn btn-success">'.$edit.'</a>'
            );
            row2(
                tra("Community")."</br><small>".tra("Settings for message boards and private messages")."</small>",
                '<a href=edit_forum_preferences_form.php class="btn btn-success">'.$edit.'</a>'
            );
            row2(
                tra("Account")."</br><small>".tra("Name, password, email address")."</small>",
                '<a href=su_account_settings.php class="btn btn-success">'.$edit.'</a>'
            );
            row2(
                tra("Email")."</br><small>".tra("When and whether we should email you")."</small>",
                '<a href=su_email_prefs.php class="btn btn-success">'.$edit.'</a>'
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
