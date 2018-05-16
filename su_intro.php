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

// intro page.  users see this after initial download,
// or when select "getting started"

require_once("../inc/util.inc");

function main() {
    page_head(tra("Getting started"));
    echo tra("Welcome to %1", PROJECT);
    echo sprintf("
        <p>
        %s
        <p>
        <ul>
        <li> %s
        <li> %s
            <ul>
            <li> %s
            <li> %s
            </ul>
        </ul>
        <p>
        %s
        <p>
        %s
        <p>
        %s
    ",
    tra("By now you should have downloaded and run BOINC.  If so, you're done!  Your computer will do run science jobs in the background, supporting research in the areas you selected."),
    tra("Scientists need all the computing power they can get.  To help further, you can:"),
    tra("Tell your family, friends, and co-workers about %1, and encourage them to volunteer.", PROJECT),
    tra("Run %1 on all your computing devices:", PROJECT),
    tra("Desktops and laptops (Windows, Linux, Mac OS X): On that device, visit this web site (%1) and log in.  Then select Download from the Project menu, and install BOINC as you did on this computer.", URL_BASE),
    tra("Android phones and tablets: install the BOINC app from the Google Play Store or (for Fire) the Amazon App Store.  Choose Use Account Manager, select %1, and log in.", PROJECT),
    tra("You can %1 Join or create a Team%2.", "<a href=team.php>", "</a>"),
    tra("Computing prefs"),
    tra("Science prefs")
    );
    page_tail();
}

main();

?>
