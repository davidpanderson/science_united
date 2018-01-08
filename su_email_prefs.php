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

// email prefs:
// - no emails at all
// - period of status emails
// - community emails

require_once("../inc/util.inc");

function su_email_form($user, $update= false) {
    BoincForumPrefs::lookup($user);
    page_head("e-mail preferences");
    if ($update) {
        echo "Preferences updated.";
    }
    form_start("su_email_prefs.php");
    form_input_hidden("action", "submit");
    form_checkboxes("Don't send me any emails",
        array(
            array("none", "", false)
        )
    );
    form_radio_buttons("Send status emails", "status_period",
        array(
            array(0, "Never"),
            array(1, "Daily"),
            array(7, "Weekly"),
            array(30, "Monthly"),
        ),
        $user->send_email
    );
    form_radio_buttons("Send community emails", "community",
        array(
            array(0, "Never"),
            array(1, "Immediately"),
            array(2, "Daily summary"),
        ),
        $user->prefs->pm_notification
    );
    form_submit("Save");
    form_end();
    page_tail();
}

function su_email_action($user) {
    $no_status = false;
    $no_comm = false;
    if (get_str("none", true)) {
        $status_period = 0;
        $pm_notification = 0;
    } else {
        $status_period = get_int("status_period");
        $pm_notification = get_int("community");
    }
    if ($status_period != $user->send_email) {
        $user->update("send_email=$status_period");
        $user->send_email = $status_period;
    }
    BoincForumPrefs::lookup($user);
    if ($pm_notification != $user->prefs->pm_notification) {
        $user->prefs->update("pm_notification=$pm_notification");
        $user->prefs->pm_notification = $pm_notification;
    }
}

$user = get_logged_in_user();
$action = get_str('action', true);
if ($action) {
    su_email_action($user);
    su_email_form($user, true);
} else {
    su_email_form($user);
}
?>
