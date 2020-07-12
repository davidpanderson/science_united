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
require_once("../inc/su_util.inc");

function top() {
    start_table();
    row2("", '<a class="btn btn-primary" href="su_projects_edit.php">Project info</a>');
    row2("", '<a class="btn btn-primary" href="su_keyword_stats.php">Keyword statistics</a>');
    row2("Accounting",
        '<a class="btn btn-primary" href="su_projects_acct.php">Projects</a>
        <a class="btn btn-primary" href="su_accounting.php">Daily history</a>'
    );
    end_table();
}

function left() {
    echo '
        <img src="su_graph.php?type=total&what=ec&ndays=400&xsize=600&ysize=400">
        <br>&nbsp;<br>
        <img src="su_graph.php?type=total&what=users&ndays=400&xsize=600&ysize=400">
        <br>&nbsp;<br>
        <img src="su_graph.php?type=total&what=jobs&ndays=400&xsize=600&ysize=400">
        <br>&nbsp;<br>
        <img src="su_graph.php?type=projects&gpu=0&xsize=600&ysize=400">
        <br>&nbsp;<br>
        <img src="su_graph.php?type=projects&gpu=1&xsize=600&ysize=400">
    ';
}

function right() {
    echo "<h3>Last accounting period</h3>\n";
    $a = SUAccounting::last(1);
    show_accounting_deltas($a, true);
    echo "<h3>Accounting totals</h3>\n";
    $a = SUAccounting::last();
    show_accounting_totals($a);
}

function main() {
    page_head("Science United: Management");
    grid('top', 'left', 'right');
    page_tail();
}

admin_only();

main();

?>
