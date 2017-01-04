<?php

// user home page

require_once("../inc/util.inc");
require_once("../inc/su.inc");

function main() {
    $user = get_logged_in_user();
    page_head("Account");
    echo '
        <p>
        <p><a href="user_history.php">Accounting history</a>
        <p><a href="user_hosts.php">Computer</a>
        <p><a href="user_projects.php">Projects</a>
    ';
    prefs_show($user);
    page_tail();
}

main();

?>
