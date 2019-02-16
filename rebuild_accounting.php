#!/usr/bin/env php

<?php
// to fix bad accounting entries due to bugs:
// enumerate at the records (totals, project, host)
// to identify bad records; delete them
// Then run this script; it rebuilds totals from deltas.
//
// Of course this erases valid data for those days.  That's OK.

require_once("../inc/su_util.inc");
require_once("../inc/su_db.inc");
require_once("../inc/util.inc");

function rebuild($recs) {
    $sum = new_delta_set();
    foreach ($recs as $rec) {
        add_record($rec, $sum);
        //echo date_str($rec->create_time)." id $rec->id gpu sum $sum->gpu_ec rec $rec->gpu_ec_total\n";
        $q = total_update_string($sum);
        //echo "$rec->id: $q\n";
        $rec->update($q);
    }
}

function rebuild_totals() {
    $recs = SUAccounting::enum("", "order by id");
    rebuild($recs);
}

function rebuild_project($id) {
    $recs = SUAccountingProject::enum("project_id = $id", "order by id");
    echo count($recs)." records\n";
    rebuild($recs);
}

function rebuild_projects() {
    $projs = SUProject::enum();
    foreach ($projs as $p) {
        echo "Project $p->id $p->name\n";
        rebuild_project($p->id);
    }
}

function rebuild_user($id) {
    $recs = SUAccountingUser::enum("user_id = $id", "order by id");
    echo count($recs)." records\n";
    rebuild($recs);
}

function rebuild_users() {
    $users = BoincUser::enum("");
    foreach ($users as $u) {
        echo "User $u->id $u->name\n";
        rebuild_user($u->id);
    }
}

function main() {
    //rebuild_totals();
    //rebuild_projects();
    rebuild_users();
}

main();
?>
