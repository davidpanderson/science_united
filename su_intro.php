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
    page_head("Getting started");
    echo sprintf('
        Welcome to %s.
        <p>
        By now you should have downloaded and run BOINC.
        If so, you\'re done!
        Your computer will do run science jobs in the background,
        supporting research in the areas you selected.
        <p>
        Scientists need all the computing power they can get.
        To help further, you can:
        <ul>
        <li> Tell your family, friends, and co-workers about %s,
        and encourage them to volunteer.
        <li>
        Run %s on all your computing devices:
            <ul>
            <li> Desktops and laptops (Windows, Linux, Mac OS X):
                On that device, visit this web site (%s) and log in.
                Then select Download from the Project menu,
                and install BOINC as you did on this computer.
            <li> Android phones and tablets: install the BOINC app
                from the Google Play Store or (for Fire) the Amazon App Store.
                Choose Use Account Manager, select %s, and log in.
            </ul>
        </ul>
        <p>
        You can <a href=team.php>Join or create a Team</a>.
        <p>
        Computing prefs
        <p>
        Science prefs
        ',
        PROJECT, PROJECT, PROJECT, URL_BASE, PROJECT
    );
    page_tail();
}

main();

?>
