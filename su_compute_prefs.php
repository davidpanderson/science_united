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

function prefs_row($venue, $pref) {
    $green_checked = ($pref=='green')?"checked":"";
    $standard_checked = ($pref=='standard')?"checked":"";
    $max_checked = ($pref=='max')?"checked":"";
    row_array(array(
        $venue,
        "<input type=radio name=$venue value=green $green_checked>",
        "<input type=radio name=$venue value=standard $standard_checked>",
        "<input type=radio name=$venue value=max $max_checked>"
    ));
}

function show_prefs($user) {
    page_head("Computing preferences");
    $prefs = simplexml_load_string($user->global_prefs);
    start_table('table-striped');
    echo "<form method=post action=su_compute_prefs.php>
        <input type=hidden name=action value=update>
    ";
    table_header("", "Green", "Standard", "Max computing");
    $default = null;
    if ($prefs) {
        $default = (string)$prefs->preset;
    }

    // if prefs are missing or don't parse, reset them
    //
    if (!$default) {
        $default = "standard";
        $prefs = compute_prefs_xml($default);
        $user->update("global_prefs='$prefs'");
    }
    prefs_row("default", $default);
    $home = null;
    $work = null;
    $school = null;
    if ($prefs && $prefs->venue) {
        foreach ($prefs->venue as $venue) {
            $name = $venue['name'];
            $pref = $venue->preset;
            switch ($name) {
            case 'home': $home = $pref; break;
            case 'work': $work = $pref; break;
            case 'school': $school = $pref; break;
            }
        }
    }
    prefs_row("home", $home);
    prefs_row("work", $work);
    prefs_row("school", $school);
    end_table();
    echo '<input type="submit" class="btn btn-success" value="Update">
        </form>
';
    page_tail();
}

function update_prefs($user) {
    $default = post_str('default');
    $home = post_str('home', true);
    $work = post_str('work', true);
    $school = post_str('school', true);
    $x = "<global_preferences>
    <preset>$default</preset>
";
    if ($home) {
        $x .= "<venue name=\"home\"><preset>$home</preset></venue>\n";
    }
    if ($work) {
        $x .= "<venue name=\"work\"><preset>$work</preset></venue>\n";
    }
    if ($school) {
        $x .= "<venue name=\"school\"><preset>$school</preset></venue>\n";
    }
    $x .= "</global_preferences>\n";
    $user->update("global_prefs='$x'");
    page_head("Preferences updated");
    page_tail();
}

$user = get_logged_in_user();
$action = post_str("action", true);
if ($action == "update") {
    update_prefs($user);
} else {
    show_prefs($user);
}

?>
