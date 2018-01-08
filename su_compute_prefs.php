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
    page_head("Computing settings");
    $x = simplexml_load_string($user->global_prefs);
    $pref = (string)$x->preset;

    // if prefs are missing or don't parse, reset them
    //
    if (!$pref) {
        $p = compute_prefs_xml("standard");
        $user->update("global_prefs='$p'");
        $prefs = simplexml_load_string($p);
    }

    $green_checked = ($pref=='green')?"checked":"";
    $standard_checked = ($pref=='standard')?"checked":"";
    $max_checked = ($pref=='max')?"checked":"";

    echo "
        You can control how many processors to use
        (most computers have 4 or 8 processors) and when to use them.
        <p>
        <ul>
        <li>This may affect your electricity costs
        and your computer's fan speeds.
        <li> Settings affect all your computers.
        Changes take effect the next time the computer
        synchs with Science United.
        <li> You can change settings for a particular computer
        using the BOINC Manager.
        This also gives you more detailed options.
        </ul>
        Choose one of:
    ";

    form_start("su_compute_prefs.php");
    form_input_hidden("action", "update");
    form_radio_buttons(
        "Green<br><small>Stop computing when computer is idle.  Use 25% of processors.</small>",
        "pref",
        array(array("green", "")),
        $pref=="green"
    );
    form_radio_buttons(
        "Standard<br><small>Use 50% of processors.</small>",
        "pref",
        array(array("standard", "")),
        $pref=="standard"
    );
    form_radio_buttons(
        "Max computing<br><small>Use all processors.</small>",
        "pref",
        array(array("max", "")),
        $pref=="max"
    );

    form_submit("Update");
    form_end();
    page_tail();
}

function update_prefs($user) {
    $pref = get_str("pref");
    $x = compute_prefs_xml($pref);
    $user->update("global_prefs='$x'");
    page_head("Computing settings updated");
    echo '
        The new settings take effect the next time your computer
        synchs with Science United.
        <p><p>
        <a href=su_home.php class="btn btn-success">Continue to home page</a>
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
