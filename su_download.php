DEPRECATED - assumes SU runs on BOINC server
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
require_once("../inc/account.inc");
require_once("../inc/versions.inc");
require_once("../inc/download_util.inc");

function main($user) {
    page_head(tra("Install software"));
    echo tra("To participate in %1 you must install BOINC and VirtualBox on your computer.", PROJECT);
    echo "<p>";
    $client_info = $_SERVER['HTTP_USER_AGENT'];

    $token = make_login_token($user);

    $concierge = new StdClass;
    $concierge->project_id = PROJECT_ID;
    $concierge->token = $token;
    download_link(
        $client_info, client_info_to_platform($client_info),
        true, true, $concierge
    );
    page_tail();
}

main(get_logged_in_user());

?>
