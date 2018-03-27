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
        <p>
        BOINC accounts, and SU accounts,
        are accessed by email address and password.
        If SU wants to attach your computer to a project,
        it will try to create an account on that project
        with your SU email and password.
        <p>
        If a project account already exists with the
        same email address and password as your SU account,
        there's no problem; SU will attach your computer to that project.
        <p>
        However, if a project account exists with the same email address
        but a different password,
        then SU can't access that account.
        SU will notify you of each such case,
        and will let you resolve it by
        entering the password of the project account,
        allowing SU to access it.
        (Note: SU doesn't save these passwords.)

        <p>
        Thus, if you already use BOINC and you use the same email address
        for most or all of your project accounts,
        we recommend that you use the same email address for your SU account.
        If in addition you use the same password for your BOINC accounts,
        we recommend that you use it for your SU account as well.
        Otherwise you'll have to resolve the password
        mismatches as described above.
    ";
}

main();
?>
