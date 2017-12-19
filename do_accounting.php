#! /usr/bin/env php

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

// Make historical accounting records - run this every ~1 day
//
// For each of the following:
// - users
// - projects
// - total
// Make new records based on the previous one, where
// - new totals = previous totals + previous deltas
// - new deltas = 0

require_once("../inc/su_db.inc");

function log_write($x) {
    echo sprintf("%s: %s\n", date(DATE_RFC822), $x);
}

function do_total($now) {
    $x = SUAccounting::last();
    if ($x) {
        SUAccounting::insert("(create_time, cpu_ec_total, gpu_ec_total, cpu_time_total, gpu_time_total, njobs_success_total, njobs_fail_total) values ($now, $x->cpu_ec_total+$x->cpu_ec_delta, $x->gpu_ec_total+$x->gpu_ec_delta, $x->cpu_time_total+$x->cpu_time_delta, $x->gpu_time_total+$x->gpu_time_delta, $x->njobs_success_total+$x->njobs_success_delta, $x->njobs_fail_total+$x->njobs_fail_delta)");
    } else {
        SUAccounting::insert("(create_time) values ($now)");
    }
}

function do_project($p, $now) {
    $x = SUAccountingProject::last($p->id);
    if ($x) {
        SUAccountingProject::insert("(create_time, project_id, cpu_ec_total, gpu_ec_total, cpu_time_total, gpu_time_total, njobs_success_total, njobs_fail_total) values ($now, $p->id, $x->cpu_ec_total+$x->cpu_ec_delta, $x->gpu_ec_total+$x->gpu_ec_delta, $x->cpu_time_total+$x->cpu_time_delta, $x->gpu_time_total+$x->gpu_time_delta, $x->njobs_success_total+$x->njobs_success_delta, $x->njobs_fail_total+$x->njobs_fail_delta)");
    } else {
        SUAccountingProject::insert("(create_time, project_id) values ($now, $p->id)");
    }
}

function do_projects($now) {
    $projects = SUProject::enum();
    foreach ($projects as $p) {
        do_project($p, $now);
    }
}

function nonzero_delta($x) {
    if ($x->cpu_time_delta) return true;
    if ($x->cpu_ec_delta) return true;
    if ($x->gpu_time_delta) return true;
    if ($x->gpu_ec_delta) return true;
    if ($x->njobs_success_delta) return true;
    if ($x->njobs_fail_delta) return true;
    return false;
}

function do_user($u, $now) {
    $x = SUAccountingUser::last($u->id);
    if ($x && nonzero_delta($x)) {
        SUAccountingUser::insert("(create_time, user_id, cpu_ec_total, gpu_ec_total, cpu_time_total, gpu_time_total, njobs_success_total, njobs_fail_total) values ($now, $u->id, $x->cpu_ec_total+$x->cpu_ec_delta, $x->gpu_ec_total+$x->gpu_ec_delta, $x->cpu_time_total+$x->cpu_time_delta, $x->gpu_time_total+$x->gpu_time_delta, $x->njobs_success_total+$x->njobs_success_delta, $x->njobs_fail_total+$x->njobs_fail_delta)");
    }
}

function do_users($now) {
    $users = BoincUser::enum("");
    foreach($users as $u) {
        do_user($u, $now);
    }
}

function main() {
    log_write("starting");
    $now = time();
    log_write("doing totals");
    do_total($now);
    log_write("doing projects");
    do_projects($now);
    log_write("doing users");
    do_users($now);
    log_write("done");
}

main();
?>
