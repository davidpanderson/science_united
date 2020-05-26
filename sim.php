#! /usr/bin/env php
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

// Trace-based simulation of Science United;
// predict project throughputs as a function of shares.

// - use as much of the actual code as possible (emulate)
// - but we can't modify the DB in the process.
//   Also, for efficiency, read the DB only at the start
//
// Maintain the following in-memory structures:
// - set of users
//      keyword prefs
// - set of hosts
//      corresponding user
//      list of processing resources
//          project using each one
// - set of projects
//      keywords
//      app version descriptors (plaform, GPU type, is_vbox)
//      accounting record
// - global accounting record
// - global allocation record
//

// The simulation works as follows:
// for each day
//      simulate RPCs from each of the hosts (random times)
//          estimate how much EC each attached project got
//          update project accounting records
//          select new projects
//      simulate end-of-day accounting

// TODO
//      model projects with bursty workloads,
//      and measure the turnaround time of their bursts

// What we don't model
//      in-progress jobs: they just add a buffer-sized phase delay

require_once("../inc/boinc_db.inc");
require_once("../inc/su_schedule.inc");

$users = array();
$hosts = null;

function init() {
    global $users, $hosts;
    $us = BoincUser::enum("");
    foreach ($us as $u) {
        $u->keywords = SUUserKeyword::enum("user_id=$u->id");
        $users[$u->id] = $u;
    }
    echo "read users\n";

    $hosts = BoincHost::enum("");
    foreach ($hosts as $h) {
        host_serialnum_to_gpus_vbox($h);
    }
    echo "read hosts\n";
}

function sim_rpc() {
}

function simulate_day() {
    global $hosts;
    foreach ($hosts as $h) {
        sim_rpc($h);
    }
}

function simulate($ndays) {
    for ($i=0; $i<$ndays; $i++) {
        simulate_day();
    }
}

init();
//simulate(30);

?>
