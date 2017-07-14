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
// - a set of projects
// - a set of users
// - random accounts
// - accounting data
//
// run this from test2/html/ops

require_once("../inc/su_db.inc");
require_once("../inc/user_util.inc");
require_once("../inc/keywords.inc");

function make_project($name, $url, $keywords, $web_rpc_url_base=null) {
    global $job_keywords;
    $now = time();
    $cmd = "~/boinc/lib/crypt_prog -sign_string $url ~/science_united/code_sign_private";
    $out = array();
    $retval = 0;
    exec($cmd, $out, $retval);
    if ($retval) {
        die("$cmd failed\n");
    }
    if (!$web_rpc_url_base) {
        $web_rpc_url_base = $url;
    }
    $url_signature = implode("\n", $out);
    $id = SUProject::insert("(create_time, name, url, web_rpc_url_base, url_signature, allocation) values ($now, '$name', '$url', '$web_rpc_url_base', '$url_signature', 10)");

    foreach ($keywords as $k) {
        $kw_id = $k[0];
        $frac = $k[1];
        while (true) {
            // insert all ancestors too
            //
            SUProjectKeyword::insert("(project_id, keyword_id, work_fraction) values ($id, $kw_id, $frac)");
            $kw = $job_keywords[$kw_id];
            if ($kw->level > 0) {
                $kw_id = $kw->parent;
            } else {
                break;
            }
        }
    }
}

function make_projects() {
    make_project("LHC@home",
        "https://lhcathome.cern.ch/lhcathome/",
        array(
            array(KW_PARTICLE_PHYSICS, 1),
            array(KW_CERN, 1),
        )
    );
    make_project("SETI@home",
        "http://setiathome.berkeley.edu/",
        array(
            array(KW_SETI, 1),
            array(KW_UCB, 1),
        ),
        "https://setiathome.berkeley.edu/"
    );
    make_project("Rosetta@home",
        "http://boinc.bakerlab.org/rosetta/",
        array(
            array(KW_PROTEINS, 1),
            array(KW_UW, 1),
        )
    );
    make_project("BOINC Test Project",
        "http://boinc.berkeley.edu/test/",
        array(
            array(KW_MATH_CS, 1),
            array(KW_UCB, 1),
        )
    );
}
if (0) {
    // WCG is problematic because it doesn't use email addr for user ID
    make_project("World Community Grid",
        "http://www.worldcommunitygrid.org/",
        array(
            array(KW_BIOMED, .5),
            array(KW_EARTH_SCI, .7),
            array(KW_US, .5),
        ),
        "https://www.worldcommunitygrid.org/"
    );
    make_project("Herd",
        "",
        array(
            array(KW_BIOMED, .5),
            array(KW_PHYSICS, .7),
            array(KW_US, 1),
        )
    );
    make_project("nanoHUB",
        "",
        array(
            array(KW_NANOSCIENCE, 1),
            array(KW_US, 1),
        )
    );
}

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
    $user = make_user($email, "David Anderson", md5("foobar".$email));
    BoincForumPrefs::insert("(userid, special_user) values ($user->id, '01')");
}

function clean() {
    foreach (SUProject::enum() as $p) {
        $p->delete();
    }
    foreach (BoincUser::enum("") as $u) {
        $u->delete();
    }
    foreach (BoincForumPrefs::enum("") as $u) {
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

clean();
make_projects();
make_users();
make_accounts();
make_accounting();

?>
