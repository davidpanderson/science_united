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

// this version must run on isaac

require_once("../inc/util.inc");

chdir("/mydisks/a/users/boincadm/boinc-site");
require_once("versions.inc");
require_once("download_util.inc");

function main($user) {
    page_head("Install software");
    echo sprintf("To participate in %s you must install software called BOINC and VirtualBox on your computer.<p>", PROJECT);
    $client_info = $_SERVER['HTTP_USER_AGENT'];

    $concierge = new StdClass;
    $concierge->master_url = "https://boinc.berkeley.edu/test2/";
    $concierge->token = $user->authenticator;
    $concierge->user_name = $user->name;
    download_link(
        $client_info, client_info_to_platform($client_info),
        true, true, $concierge
    );
    page_tail();
}

$user = get_logged_in_user();
main($user);

?>
