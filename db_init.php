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

// script to populate the SU database
// - a set of users
// - random accounts
// - accounting data
//
// run this from test2/html/ops
// RUN project_init.php FIRST
//
// db_init.php --update
// add accounting records for period until now

require_once("../inc/su_db.inc");
require_once("../ops/project_init.php");
require_once("../inc/user_util.inc");
require_once("../inc/keywords.inc");

function su_make_user($name) {
    $email = strtolower($name."@gmail.com");
    $passwd_hash = md5("foobar".$email);
    make_user($email, $name, $passwd_hash);
}

function make_users() {
    su_make_user("Bob");
    su_make_user("Steve");
    su_make_user("Mary");
    su_make_user("Cynthia");

    $email = "davea@ssl.berkeley.edu";
    //$user = make_user($email, "David Anderson", md5("foobar".$email));
    $user = BoincUser::lookup("email_addr='$email'");
    BoincForumPrefs::insert("(userid, special_user) values ($user->id, '01')");
}

function clean() {
    foreach (BoincUser::enum("") as $u) {
        if ($u->email_addr == "davea@ssl.berkeley.edu") {
            continue;
        }
        $u->delete();
    }
    foreach (BoincForumPrefs::enum("") as $p) {
        $p->delete();
    }
    SUUserKeyword::delete_all();
    SUAccounting::delete_all();
    SUAccountingUser::delete_all();
}

function random_element($list) {
    return $list[rand(0, count($list)-1)];
}

// attach each user to about half the projects.
// Then make sure each project has a user, and each user has a project
//
function make_accounts() {
    $users = BoincUser::enum("");
    $projects = SUProject::enum();
    foreach ($users as $u) {
        $x = false;
        foreach ($projects as $p) {
            if (drand() > .5) {
                $x = true;
                $p->has_user = true;
                SUAccount::insert("(user_id, project_id, create_time) values ($u->id, $p->id, time())");
            }
        }
        if (!$x) {
            $p = random_element($projects);
            SUAccount::insert("(user_id, project_id, create_time) values ($u->id, $p->id, time())");
            $p->has_user = true;
        }
    }
    foreach ($projects as $p) {
        if (!isset($p->has_user)) {
            $u = random_element($users);
            SUAccount::insert("(user_id, project_id, create_time) values ($u->id, $p->id, time())");
        }
    }
}

function zero_acct() {
    $y = new StdClass;
    $y->cpu_ec_delta = 0;
    $y->cpu_time_delta = 0;
    $y->gpu_ec_delta = 0;
    $y->gpu_time_delta = 0;
    $y->cpu_ec_total = 0;
    $y->cpu_time_total = 0;
    $y->gpu_ec_total = 0;
    $y->gpu_time_total = 0;
    $y->njobs_success_delta = 0;
    $y->njobs_success_total = 0;
    $y->njobs_fail_delta = 0;
    $y->njobs_fail_total = 0;
    return $y;
}

function add_acct($x, $f, &$y) {
    $y->cpu_ec_delta += $f*$x->cpu_ec_delta;
    $y->cpu_time_delta += $f*$x->cpu_time_delta;
    $y->gpu_ec_delta += $f*$x->gpu_ec_delta;
    $y->gpu_time_delta += $f*$x->gpu_time_delta;
    $y->cpu_ec_total += $f*$x->cpu_ec_delta;
    $y->cpu_time_total += $f*$x->cpu_time_delta;
    $y->gpu_ec_total += $f*$x->gpu_ec_delta;
    $y->gpu_time_total += $f*$x->gpu_time_delta;
    $y->njobs_success_delta += (int)($f*$x->njobs_success_delta);
    $y->njobs_success_total += (int)($f*$x->njobs_success_delta);
    $y->njobs_fail_delta += (int)($f*$x->njobs_fail_delta);
    $y->njobs_fail_total += (int)($f*$x->njobs_fail_delta);
}

function clear_deltas($acc) {
    $acc->cpu_ec_delta = 0;
    $acc->cpu_time_delta = 0;
    $acc->gpu_ec_delta = 0;
    $acc->gpu_time_delta = 0;
    $acc->njobs_success_delta = 0;
    $acc->njobs_fail_delta = 0;
    return $acc;
}

function insert_string($a, $t, $vars="", $vals="") {
    return "(create_time, cpu_ec_delta, cpu_ec_total, gpu_ec_delta, gpu_ec_total, cpu_time_delta, cpu_time_total, gpu_time_delta, gpu_time_total, njobs_success_delta, njobs_success_total, njobs_fail_delta, njobs_fail_total $vars) values ($t, $a->cpu_ec_delta, $a->cpu_ec_total, $a->gpu_ec_delta, $a->gpu_ec_total, $a->cpu_time_delta, $a->cpu_time_total, $a->gpu_time_delta, $a->gpu_time_total, $a->njobs_success_delta, $a->njobs_success_total, $a->njobs_fail_delta, $a->njobs_fail_total $vals)";
}

// make 1 day of accounting records
//
function make_accounting(
    $t,
    $acct,              // total accounting record
    $acct_project,      // project accounting records
    $acct_user,         // user accounting records
    $user_accounts      // map from user ID to list of SUAccount records
) {
    $acct = clear_deltas($acct);
    foreach ($acct_project as $id=>$a) {
        $acct_project[$id] = clear_deltas($acct_project[$id]);
    }
    foreach ($acct_user as $id=>$a) {
        $acct_user[$id] = clear_deltas($acct_user[$id]);
    }

    // loop over users
    //
    foreach ($user_accounts as $uid=>$accts) {
        // decide how much user computed today
        //
        $x = drand()+1;
        $d = zero_acct();
        $d->cpu_ec_delta = 1000.*$x;
        $d->cpu_time_delta = 86400.*$x;
        $d->njobs_success_delta = (int)($x*10)+1;
        $d->njobs_fail_delta = drand()>.5?1:0;
        if ($uid % 2) {
            $d->gpu_ec_delta = 10000*$x;
            $d->gpu_time_delta = 86400*$x;
        } else {
            $d->gpu_ec_delta = 0;
            $d->gpu_time_delta = 0;
        }
        add_acct($d, 1, $acct);
        add_acct($d, 1, $acct_user[$uid]);

        // divide evenly among attached projects
        // TODO: divide unevenly
        //
        $f = 1./count($accts);
        foreach ($accts as $a) {
            add_acct($d, $f, $acct_project[$a->project_id]);
        }
    }
    SUAccounting::insert(insert_string($acct, $t));
    foreach ($acct_user as $id=>$au) {
        SUAccountingUser::insert(insert_string($au, $t, ",user_id", ",$id"));
    }
    foreach ($acct_project as $id=>$ap) {
        SUAccountingProject::insert(insert_string($ap, $t, ",project_id", ",$id"));
    }
    echo "Creating account records for ".date(DATE_RFC2822, $t)."\n";
}

// create the last 100 days of accounting records
//
function init_accounting() {
    $now = time();
    $ndays = 100;
    $users = BoincUser::enum("");
    $projects = SUProject::enum();
    $user_accounts = array();
    $acct_user = array();
    $acct_project = array();
    foreach ($users as $u) {
        $user_accounts[$u->id] = SUAccount::enum("user_id=$u->id");
        $acct_user[$u->id] = zero_acct();
    }
    foreach ($projects as $p) {
        $acct_project[$p->id] = zero_acct();
    }
    $acct = zero_acct();

    // loop over days
    //
    for ($i=0; $i<$ndays; $i++) {
        $t = $now + ($i-$ndays)*86400;
        make_accounting($t, $acct, $acct_project, $acct_user, $user_accounts);
    }
}

function update_accounting() {
    $users = BoincUser::enum("");
    $projects = SUProject::enum();
    $user_accounts = array();
    $acct_user = array();
    $acct_project = array();
    foreach ($users as $u) {
        $user_accounts[$u->id] = SUAccount::enum("user_id=$u->id");
        $au = SUAccountingUser::last($u->id);
        if (!$au) $au = zero_acct();
        $acct_user[$u->id] = $au;
    }
    foreach ($projects as $p) {
        $ap = SUAccountingProject::last($p->id);
        if (!$ap) $ap = zero_acct();
        $acct_project[$p->id] = $au;
    }
    $acct = SUAccounting::last();

    $t = $acct->create_time;
    $now = time();
    while ($t < $now) {
        make_accounting($t, $acct, $acct_project, $acct_user, $user_accounts);
        $t += 86400;
    }
}

if ($argc > 1) {
    if ($argv[1] == "--update") {
        update_accounting();
    } else if ($argv[1] == "--clean") {
        clean();
    } else {
        die("unknown option $argv[1]);
    }
} else {
    clean();
    make_users();
    make_accounts();
    init_accounting();
}

?>
