<?php

// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2020 University of California
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

// get the project host IDs for a user

require_once("../inc/xml.inc");
require_once("../inc/su_db.inc");
require_once("../inc/su_project_infos.inc");

function main() {
    global $project_infos;

    xml_header();
    $user = BoincUser::lookup_auth(get_str("auth"));
    if (!$user) {
        xml_error("no such user");
    }
    $hosts = BoincHost::enum("userid=$user->id");
    echo "<project_hosts>\n";
    foreach ($hosts as $host) {
        $hps = SUHostProject::enum("host_id=$host->id");
        foreach ($hps as $hp) {
            if (!$hp->project_host_id) {
                continue;
            }
            if (!array_key_exists($hp->project_id, $project_infos)) {
                continue;
            }
            $p = $project_infos[$hp->project_id];
            echo "   <project_host>
      <url>$p->url</url>
      <project_host_id>$hp->project_host_id</project_host_id>
      <su_host_id>$host->id</su_host_id>
   </project_host>
";
        }
    }
    echo "</project_hosts>\n";
}

main();

?>
