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

DEPRECATED

require_once("../inc/util.inc");

function show_user_hosts($user) {
    start_table('table-striped');
    row_heading_array(array(
        tra("Name")."<br><small>".tra("click for details")."</small>",
        tra("Last RPC"),
    ));
    $hosts = BoincHost::enum("userid=$user->id");
    foreach ($hosts as $host) {
        row_array(array(
            '<a href="user_host.php?host_id='.$host->id.'">'.$host->domain_name.'</a>',
            date_str($host->rpc_time)
        ));
    }
    end_table();
}

$user = get_logged_in_user();
page_head(tra("Your computers"));
show_user_hosts($user);
page_tail();
?>
