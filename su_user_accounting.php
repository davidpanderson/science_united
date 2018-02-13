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

require_once("../inc/util.inc");
require_once("../inc/su.inc");
require_once("../inc/su_graph.inc");

function show_data($user) {
    page_head("Your contribution history");
    show_accounting_history(
        SUAccountingUser::enum(
            "user_id=$user->id", "order by id desc limit 200"
        )
    );
    page_tail();
}

function show_graphs($user, $ndays) {
    page_head("Your contribution history");
    echo "<h3>Computing power</h3>\n";
    show_user_graph($user, "ec", $ndays);
    echo "<p> <h3>Computing time</h3>\n";
    show_user_graph($user, "time", $ndays);
    echo "<p> <h3>Completed jobs</h3>\n";
    show_user_graph($user, "jobs", $ndays);
    page_tail();
}

$user = get_logged_in_user();
if (get_int("graphs", true)) {
    show_graphs($user, 30);
} else {
    show_data($user);
}


?>
