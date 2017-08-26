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

// management interface home page

require_once("../inc/util.inc");
require_once("../inc/su.inc");

function main() {
    page_head("Science United Administration");
    echo '
        <a class="btn btn-primary" href="su_projects_edit.php">Projects</a>
        <a class="btn btn-primary" href="https://boinc.berkeley.edu/keywords.php?header=html">Keywords</a>
        <h3>Accounting</h3>
        <p> <a class="btn btn-primary" href="su_projects_acct.php">Projects</a>
        <p> <a class="btn btn-primary" href="su_accounting.php">Totals</a>
    ';
    page_tail();
}

admin_only();

main();

?>
