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

// info for existing BOINC users

require_once("../inc/su.inc");

function main() {
    page_head("Info for current BOINC users");
    echo "
        <p>
        Science United (SU) attaches your computer
        to projects based on your science preferences.
        For example, if you select cancer research,
        your computer will compute for existing BOINC projects
        doing cancer research;
        if a new one comes along it will probably compute for that too.
        <a href=su_about.php>Read why this helps volunteer computing</a>.
    ";
}

main();
?>
