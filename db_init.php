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
// - a set of keywords
// - a set of projects
// - a set of users
// - random accounts
// - accounting data

require_once("su_db.inc");

function make_keyword($word, $category, $level) {
    global $keywords;
    $id = SUKeyword::insert("(word, category, level) values ('$word', $category, $level)");
}

function make_keywords() {
    make_keyword("Biomedicine", SCIENCE, 0);
    make_keyword("Basic Science", SCIENCE, 0);
    make_keyword("Earth Science", SCIENCE, 0);
    make_keyword("Astronomy", SCIENCE, 0);
    make_keyword("Math and Computer Science", SCIENCE, 0);

    make_keyword("SETI", SCIENCE, 1);
    make_keyword("Physics", SCIENCE, 1);
    make_keyword("Chemistry", SCIENCE, 1);
    make_keyword("Nanoscience", SCIENCE, 1);

    make_keyword("Europe", LOCATION, 0);
    make_keyword("Asia", LOCATION, 0);
    make_keyword("United States", LOCATION, 0);

    make_keyword("UC Berkeley", LOCATION, 1);
    make_keyword("CERN", LOCATION, 1);
    make_keyword("U. Texas", LOCATION, 1);
    make_keyword("Purdue", LOCATION, 1);
}

function make_project($name, $keywords) {
    $now = time();
    $id = SUProject::insert("(create_time, name) values ($now, '$name')");

    foreach ($keywords as $k) {
        if (drand() < .4) {
            SUProjectKeyword::insert("(project_id, keyword_id) values ($id, $k->id)");
        }
    }
}

function make_projects() {
    $keywords = SUKeyword::enum();
    make_project("Herd", $keywords);
    make_project("nanoHUB", $keywords);
    make_project("CERN", $keywords);
    make_project("SETI@home", $keywords);
    make_project("Rosetta@home", $keywords);
    make_project("World Community Grid", $keywords);
}

function make_user($name) {
    $email = $name."@gmail.com";
    $id = BoincUser::insert("(name, email_addr) values ('$name', '$email')");
}

function make_users() {
    make_user("David");
    make_user("Carol");
    make_user("Luke");
    make_user("Bob");
    make_user("Steve");
    make_user("Mary");
    make_user("Cynthia");
}

function clean() {
    foreach (SUKeyword::enum() as $k) {
        $k->delete();
    }
    foreach (SUProject::enum() as $p) {
        $p->delete();
    }
    foreach (BoincUser::enum("") as $u) {
        $u->delete();
    }
    foreach (SUProjectKeyword::enum() as $x) {
        $x->delete();
    }
    foreach (SUUserKeyword::enum() as $x) {
        $x->delete();
    }
    foreach (SUAccount::enum() as $x) {
        $x->delete();
    }
}

function make_accounts() {
    $users = BoincUser::enum("");
    $projects = SUProject::enum();
    foreach ($users as $u) {
        foreach ($projects as $p) {
            echo "user $u->name, proj $p->name\n";
            if (drand()<.5) {
                continue;
            }
            SUAccount::insert("(user_id, project_id) values ($u->id, $p->id)");
        }
    }
}

function make_acct() {
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

function make_accounting() {
    $now = time();
    $ndays = 100;
    $users = BoincUser::enum("");
    $projects = SUProject::enum();
    $user_accounts = array();
    $acct_user = array();
    $acct_project = array();
    foreach ($users as $u) {
        $user_accounts[$u->id] = SUAccount::enum("user_id=$u->id");
        $acct_user[$u->id] = make_acct();
    }
    foreach ($projects as $p) {
        $acct_project[$p->id] = make_acct();
    }
    $acct = make_acct();

    // loop over days
    //
    for ($i=0; $i<$ndays; $i++) {
        $t = $now + ($i-$ndays)*86400;

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
            $d = make_acct();
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
    }
}

//clean();
//make_keywords();
//make_projects();
//make_users();
//make_accounts();
make_accounting();


?>
