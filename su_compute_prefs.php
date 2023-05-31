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

// Display and edit compute prefs.
// Use presets only.

require_once("../inc/util.inc");
require_once("../inc/su.inc");
require_once("../inc/su_compute_prefs.inc");

function show_prefs($user) {
    page_head(tra("Computing settings"));
    $x = simplexml_load_string($user->global_prefs);
    $preset = null;
    $custom = false;
    if ($x) {
        if (isset($x->preset)) {
            $preset = $x->preset;
        } else {
            $custom = true;
        }
    }
    //print_r($x);

    // if prefs are missing or don't parse, reset them
    //
    if (!$preset && !$custom) {
        $p = compute_prefs_xml("standard");
        $user->update("global_prefs='$p'");
        $prefs = simplexml_load_string($p);
    }

    $low_power_checked = ($preset=='low_power')?"checked":"";
    $standard_checked = ($preset=='standard')?"checked":"";
    $max_checked = ($preset=='max')?"checked":"";

    echo tra("You can control how many processors to use (most computers have 4 or 8 processors) and when to use them.");
    echo "
        <p>
        <ul>
        <li>
    ";
    echo tra("This may affect your electricity costs and your computer's fan speeds.");
    echo "<li>";
    echo tra("Settings affect all your computers.  Changes take effect the next time the computer syncs with Science United.");
    echo "<li>";
    echo tra("You can change settings for a particular computer using the BOINC Manager.  This also gives you more detailed options.");
    echo "</ul>";
    echo tra("Choose one of:");

    form_start("su_compute_prefs.php");
    form_input_hidden("action", "update");
    form_radio_buttons(
        tra("Low power %1 Use 25% of processors, and stop computing when computer is idle.%2",  "<br><small>", "</small>"
        ),
        "pref",
        array(array("low_power", "")),
        $preset=="low_power"
    );
    form_radio_buttons(
        tra("Standard %1 Use 50% of processors.%2",
            "<br><small>",
            "</small>"
        ),
        "pref",
        array(array("standard", "")),
        $preset=="standard"
    );
    form_radio_buttons(
        tra("Maximum computing %1 Use all processors.%2",
            "<br><small>",
            "</small>"
        ),
        "pref",
        array(array("max", "")),
        $preset=="max"
    );

    form_submit(tra("Update"));
    form_end();
    if ($custom) {
        echo tra("You are using custom preferences.  %1Show%2.",
            "<a href=prefs.php?subset=global>", "</a>"
        );
    } else {
        echo tra("Or %1customize your preferences%2.",
            "<a href=prefs.php?subset=global>", "</a>"
        );
    }
    page_tail();
}

function update_prefs($user) {
    $pref = get_str("pref");
    $x = compute_prefs_xml($pref);
    $user->update("global_prefs='$x'");
    page_head(tra("Computing settings updated"));
    echo tra("The new settings will take effect when your computer syncs with Science United.");
    echo '<p><p>
        <a href=su_home.php class="btn btn-success">
    ';
    echo tra("Continue to home page");
    echo '</a>
    ';
    page_tail();
}

$user = get_logged_in_user();
$action = get_str("action", true);
if ($action == "update") {
    update_prefs($user);
} else {
    show_prefs($user);
}

?>
