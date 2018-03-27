#! /usr/bin/env php

<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2018 University of California
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

// Make historical accounting records,
// and update allocation balances
// Run this every ~1 day
//
// For each of the following:
// - users
// - projects
// - total
// Make new records based on the previous one, where
// - new totals = previous totals + previous deltas
// - new deltas = 0

require_once("../inc/boinc_db.inc");
require_once("../inc/su_db.inc");
require_once("../inc/su_util.inc");

function log_write($x) {
    echo sprintf("%s: %s\n", date(DATE_RFC822), $x);
}

// increment balances in proportion to shares
//
function do_allocation() {
    $x = SUAccounting::last();
    $flops = ec_to_gflops($x->cpu_ec_delta + $x->gpu_ec_delta);
    $projects = SUProject::enum("status=".PROJECT_STATUS_AUTO);
    $total_share = 0;
    foreach ($projects as $p) {
        $total_share += $p->share;
    }
    log_write("flops: $flops; total_share: $total_share");
    foreach ($projects as $p) {
        $x = $flops*$p->share/$total_share;
        log_write("adding $x to balance of $p->name");
        $p->update("balance = balance + $x");
    }
}

function nactive_users($ndays) {
    $t = time() - $ndays*86400;
    $db = BoincDb::get();
    return $db->get_int(
        "select count(distinct(user_id)) as total from su_accounting_user where create_time > $t",
        "total"
    );
}

function nactive_hosts($ndays) {
    $t = time() - $ndays*86400;
    return BoincHost::count("rpc_time > $t");
}

function nactive_hosts_gpu($ndays) {
    $t = time() - $ndays*86400;
    return BoincHost::count("rpc_time > $t and p_ngpus>0");
}

function do_total($now) {
    $nh = nactive_hosts(7);
    $nhg = nactive_hosts_gpu(7);
    $nu = nactive_users(7);
    $x = SUAccounting::last();
    if ($x) {
        SUAccounting::insert("(create_time, nactive_hosts, nactive_hosts_gpu, nactive_users, cpu_ec_total, gpu_ec_total, cpu_time_total, gpu_time_total, njobs_success_total, njobs_fail_total) values ($now, $nh, $nhg, $nu, $x->cpu_ec_total+$x->cpu_ec_delta, $x->gpu_ec_total+$x->gpu_ec_delta, $x->cpu_time_total+$x->cpu_time_delta, $x->gpu_time_total+$x->gpu_time_delta, $x->njobs_success_total+$x->njobs_success_delta, $x->njobs_fail_total+$x->njobs_fail_delta)");
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

    // do this first, before we create a new accounting record
    //
    log_write("doing allocation");
    do_allocation($now);

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
