<?php

// make a list of user IDs of users with Windows computers

require_once("su_db.inc");

function main() {
    $users = BoincUser::enum("");
    foreach ($users as $user) {
        $hosts = BoincHost::enum("userid = $user->id");
        $found = false;
        foreach ($hosts as $host) {
            if ($host->expavg_credit < .1) continue;
            if (strstr($host->os_name, "Windows")) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "$user->id\n";
        }
    }
}

main();

?>
