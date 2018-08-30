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
require_once("../inc/user.inc");

function su_user_info_private($user) {
    $url_tokens = url_tokens($user->authenticator);
    row2(tra("Screen name"), $user->name." <a href=edit_user_info_form.php?$url_tokens><img height=20 src=pictures/edit.png></a>");
    row2(tra("Email address"), $user->email_addr. " <a href=edit_email_form.php><image height=20 src=pictures/edit.png></a>");
    row2(tra("%1 member since", PROJECT), date_str($user->create_time));
    row2(tra("User ID")."<br/><p class=\"small\">".tra("Used in community functions")."</p>", $user->id);
}

function main($user) {
    page_head(tra("Account settings"));
    start_table();
    su_user_info_private($user);
    end_table();
    page_tail();
}

main(get_logged_in_user());

?>
