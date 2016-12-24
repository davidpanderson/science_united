<?php

// user home page

require_once("../inc/util.inc");
require_once("../inc/su.inc");

function main() {
    $user = get_logged_in_user();
    page_head("Account");
    show_computers($user);
    show_projects($user);
    prefs_show($user);
    page_tail();
}

main();

?>
