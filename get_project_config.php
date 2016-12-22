<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2008 University of California
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

$dir = getcwd();
chdir("/mydisks/a/users/boincadm/projects/test2/html/user");
require_once("../inc/xml.inc");
chdir($dir);

xml_header();

echo "<project_config>
    <name>Science United</name>
    <account_creation_disabled/>
    <min_client_version>7.6.33</min_client_version>
    </project_config>
";

?>
