<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2019 University of California
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
require_once("../inc/su_db.inc");
require_once("../inc/web_rpc_api.inc");

$user = get_logged_in_user();
$project_id = get_int("project_id");
$project = SUProject::lookup_id($project_id);
list($auth, $err, $msg) = create_account(
    $project->web_rpc_url_base,
    $user->email_addr,
    $user->passwd_hash,
    $user->name
);
echo "$project->web_rpc_url_base, $err $msg";

?>
