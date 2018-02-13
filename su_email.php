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

require_once("../inc/boinc_db.inc");
require_once("../inc/email.inc");
require_once("../inc/su_util.inc");
require_once("../inc/su_db.inc");

// send status emails
//


function log_write($x) {
    echo date(DATE_RFC822), ": $x\n";
}

function do_user($user) {
    $ndays = $user->send_email;
    if ($ndays < 2) {
        $ndays = 2;
    }

    // show the last $ndays of statistics
    //
    $t0 = time() - $ndays*86400;
    $uas = SUAccountingUser::enum("user_id = $user->id and create_time > $t0");
    $delta = new_delta_set();
    foreach ($uas as $ua) {
        add_record($ua, $delta);
    }

    $x = "Dear $user->name:\n\nGreetings from Science United.\n\n";
    if (delta_set_nonzero($delta)) {
        $x .= sprintf('In the last %d days your computers have contributed %s hours of processing time, and have completed %d jobs.  Congratulations!',
            $ndays,
            show_num(($delta->cpu_time+$delta->gpu_time)/3600.),
            $delta->njobs_success+$delta->njobs_fail
        );
    } else {
        $x .= sprintf("We haven't heard from your computers in the last %d days.  You may need to reinstall BOINC on them, or unsuspend BOINC.",
            $ndays
        );
    }
    $x .= "\n\nFor details, visit https://scienceunited.org/\n\n";
    $t0 = time() - 86400.*7;
    $hosts = BoincHost::enum("userid = $user->id and total_credit>=0");
    foreach ($hosts as $host) {
        $idle_days = (time() - $host->rpc_time)/86400;
        if ($host->rpc_time < $t0) {
            $x .= sprintf(
                "Your computer %s has been idle for %d days; check that BOINC is running there.\n",
                $host->domain_name, (int)$idle_days
            );
        }
    }
    $x .= "\nThanks for participating in Science United.\n\n";
    $x .= sprintf(
        "To unsubscribe or change the frequency of these emails, go here:\n%s\n",
        "https://scienceunited.org/su_email_prefs.php"
    );
    send_email($user, "Science United status", $x);
    log_write("sent email to $user->email_addr");
}

function main() {
    log_write("starting");
    $now = time();
    $users = BoincUser::enum("send_email > 0 and seti_last_result_time < $now");
    foreach ($users as $user) {
        if ($user->id != 22203) continue;
        do_user($user);
    }
    log_write("done");
}

main();

?>
