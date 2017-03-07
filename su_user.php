<?php

// user home page

require_once("../inc/util.inc");
require_once("../inc/su.inc");

function main() {
    $user = get_logged_in_user();
    page_head("Account");
    show_user_acct_totals($user);
    show_user_acct_history_ec($user);
    echo '
        <p>
        <p><a href="user_histories.php">History</a>
        <p><a href="user_devices.php">Devices</a>
        <p><a href="user_projects.php">Projects</a>
    ';
    prefs_show($user);
    page_tail();
}

main();

?>
