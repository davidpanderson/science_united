#!/usr/bin/env php

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

// update allocation data, namely
//  su_project.avg_ec
//      Average EC/second over the last week
//  su_allocate
//      nprojects
//      avg_ec_total
//      share_total

require_once("../inc/su_db.inc");
require_once("../inc/log.inc");

define('AVERAGING_PERIOD', 7);

// set project.avg_ec (average EC/sec over last week)
//
function do_project($p) {
    $p->avg_ec = 1;

    // get accounting records over averaging period
    //
    $t = time() - AVERAGING_PERIOD*86400;
    $aps = SUAccountingProject::enum("project_id = $p->id and create_time>=$t", "order by create_time");
    $n = count($aps);
    if ($n >= 2) {
        // get average EC over that period
        //
        $ap1 = $aps[0];
        $ap2 = $aps[$n-1];
        $dt = $ap2->create_time - $ap1->create_time;
        if ($dt > 0) {
            $ec = $ap2->cpu_ec_total - $ap1->cpu_ec_total;
            $ec += $ap2->gpu_ec_total - $ap1->gpu_ec_total;
            $p->avg_ec = $ec/$dt;
        }
    }
    $p->update("avg_ec=$p->avg_ec, avg_ec_adjusted=$p->avg_ec");
    echo "$p->name: avg EC is $p->avg_ec\n";
}

function main() {
    log_write("start");
    $projects = SUProject::enum();
    $avg_ec_total = 0;
    $share_total = 0;
    $nprojects = 0;
    foreach ($projects as $p) {
        if ($p->status != PROJECT_STATUS_AUTO) {
            continue;
        }
        do_project($p);
        $nprojects++;
        $avg_ec_total += $p->avg_ec;
        $share_total += $p->share;
    }
    SUAllocate::update("nprojects=$nprojects, avg_ec_total=$avg_ec_total, share_total=$share_total");
    log_write("nprojects $nprojects avg_ec_total $avg_ec_total share_total $share_total");
    log_write("end");
}

main();

?>
