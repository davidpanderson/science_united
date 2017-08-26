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
require_once("../inc/host.inc");

function su_show_user_hosts() {
    $user = get_logged_in_user();
    $hosts = BoincHost::enum("userid=$user->id order by domain_name, rpc_time desc");
    page_head("Your computers");
    start_table();
    row_heading_array(array("Name", "CPU", "GPU", "Operating system", "last contact", "Remove duplicate"));
    $now = time();
    $last_name = "";
    foreach($hosts as $h) {
        $link = "";
        if ($h->domain_name == $last_name) {
            $link = "<a href=su_hosts.php?action=del&id=$h->id>Remove</a>";
        }
        $last_name = $h->domain_name;
        row_array(array(
            $h->domain_name,
            $h->p_vendor,
            gpu_desc($h->serialnum, false),
            $h->os_name,
            time_diff_str($h->rpc_time, $now),
            $link
        ));
    }
    end_table();
}

function su_delete_host() {
    $id = get_int("id");
    $user = get_logged_in_user();
    $h = BoincHost::lookup_id($id);
    if (!$h || $h->userid != $user->id) {
        error_page("no such host");
    }
    $h->delete();
}

$action = get_str("action", true);
if ($action == "del") {
    su_delete_host();
}

su_show_user_hosts();
