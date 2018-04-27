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

function su_send_email($user, $x) {
    $x .= "\nThanks for participating in Science United.\n\n";
    $x .= sprintf(
        "To unsubscribe or change the frequency of these emails, go here:\n%s\n",
        "https://scienceunited.org/su_email_prefs.php"
    );
    send_email($user, "Science United status", $x);
    log_write("sent email to $user->email_addr");
}

function do_user($user) {
    $x = "Dear $user->name:\n\nGreetings from Science United.\n\n";

    $hosts = BoincHost::enum("userid = $user->id and total_credit>=0");
    if (count($hosts) == 0) {
        $x .= "We haven't heard from your computer.  Make sure that BOINC is installed and running.  To install BOINC, go here:\nhttps://scienceunited.org/download.php\n";
        su_send_email($user, $x);
        return;
    }

    // $ndays is the user's requested email frequency.
    // report progress in that period
    //
    $ndays = $user->send_email;

    // add 1 day because of accounting period
    //
    $t0 = time() - ($ndays+1)*86400;
    $uas = SUAccountingUser::enum("user_id = $user->id and create_time > $t0");
    $delta = new_delta_set();
    foreach ($uas as $ua) {
        add_record($ua, $delta);
    }

    if ($ndays == 1) {
        $ndays_str = "day";
    } else {
        $ndays_str = "$ndays days";
    }

    if (delta_set_nonzero($delta)) {
        $x .= sprintf(
            'In the last %s your computers have contributed %s hours of processing time, and have completed %d jobs.  Congratulations!',
            $ndays_str,
            show_num(($delta->cpu_time+$delta->gpu_time)/3600.),
            $delta->njobs_success+$delta->njobs_fail
        );
    } else {
        $x .= sprintf(
            "Your computers haven't reported work in the last %s.  You may need to reinstall BOINC on them, or unsuspend BOINC.",
            $ndays_str
        );
    }
    $x .= "\n\nFor details, visit https://scienceunited.org/\n\n";
    $t0 = time() - 86400.*7;
    foreach ($hosts as $host) {
        $idle_days = (time() - $host->rpc_time)/86400;
        if ($host->rpc_time < $t0) {
            $x .= sprintf(
                "Your computer %s has been idle for %d days; check that BOINC is running there.\n",
                $host->domain_name, (int)$idle_days
            );
        }
    }
    su_send_email($user, $x);
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
