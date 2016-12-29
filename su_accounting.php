<?php

// run this every c. 1 day
// compute total and per-project REC in this accounting period
// update project credits

require_once("../inc/su_db.inc");

function do_total($now) {
    $x = SUAccounting::last();
    if ($x) {
        SUAccounting::insert("(create_time, cpu_ec_total, gpu_ec_total, cpu_time_total, gpu_time_total, njobs_success_total, njobs_fail_total) values ($now, $x->cpu_ec_total, $x->gpu_ec_total, $x->cpu_time_total, $x->gpu_time_total, $x->njobs_success_total, $x->njobs_fail_total)");
    } else {
        SUAccounting::insert("(create_time) values ($now)");
    }
}

function do_project($p, $now) {
    $x = SUAccountingProject::last($p->id);
    if ($x) {
        SUAccountingProject::insert("(create_time, project_id, cpu_ec_total, gpu_ec_total, cpu_time_total, gpu_time_total, njobs_success_total, njobs_fail_total) values ($now, $p->id, $x->cpu_ec_total, $x->gpu_ec_total, $x->cpu_time_total, $x->gpu_time_total, $x->njobs_success_total, $x->njobs_fail_total)");
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

function do_user($u, $now) {
    $x = SUAccountingUser::last($u->id);
    if ($x) {
        SUAccountingUser::insert("(create_time, user_id, cpu_ec_total, gpu_ec_total, cpu_time_total, gpu_time_total, njobs_success_total, njobs_fail_total) values ($now, $u->id, $x->cpu_ec_total, $x->gpu_ec_total, $x->cpu_time_total, $x->gpu_time_total, $x->njobs_success_total, $x->njobs_fail_total)");
    } else {
        SUAccountingUser::insert("(create_time, user_id) values ($now, $u->id)");
    }
}

function do_users($now) {
    $users = BoincUser::enum("");
    foreach($users as $u) {
        do_user($u, $now);
    }
    // TODO: make this more efficient.
}

function main() {
    $now = time();
    do_total($now);
    do_projects($now);
    do_users($now);
}

main();
?>
