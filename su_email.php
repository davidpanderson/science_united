<?php

require_once("../inc/boinc_db.inc");
require_once("../inc/email.inc");
require_once("../inc/su_util.inc");
require_once("../inc/su_db.inc");

// script to send status emails

function do_user($user) {
    $hosts = BoincHost::enum("userid = $user->id and total_credit>=0");
    $ndays = $user->send_email;

    // show the last $ndays of statistics
    //
    $t0 = time() - $ndays*86400;
    $uas = SUAccountingUser::enum("user_id = $user->id and create_time > $t0");
    $delta = new_delta_set();
    foreach ($uas as $ua) {
        print_r($ua);
        add_delta_set($ua, $delta);
    }

    $x = "Dear $user->name:\n\nGreetings from Science United.\n\n";
    if (delta_set_nonzero($delta)) {
        $x .= sprintf('In the last %d days your computers have contributed %s hours of processing time, and have completed %d jobs.  Congratulations, and thanks!',
            $ndays,
            show_num(($delta->cpu_time+$delta->gpu_time)/3600.),
            $delta->njobs_success+$delta->njobs_fail
        );
    } else {
        $x .= sprintf('In the last %d days none of your computers has processed any jobs.  You may need to reinstall BOINC on them, or unsuspend BOINC.',
            $ndays
        );
    }
    $x .= "\n\n";
    $t0 = time() - 86400.*7;
    foreach ($hosts as $host) {
        $idle_days = (time() - $host->rpc_time)/86400;
        if ($host->rpc_time < $t0) {
            $x .= sprintf("Your computer %s has been idle for %s days.\n",
                $host->domain_name, show_num($idle_days)
            );
        }
    }
    $x .= "\nThanks for participating in Science United.\n\n";
    $x .= sprintf(
        "To unsubscribe or change the frequency of these emails, go here: %s.\n",
        "unsubscribe.php"
    );
    echo $x; exit;
    send_email($user, "Science United status", $x);
}

function main() {
    $now = time();
    $users = BoincUser::enum("send_email > 0 and seti_last_result_time < $now");
    foreach ($users as $user) {
        do_user($user);
    }
}

main();

?>
