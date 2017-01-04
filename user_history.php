<?php

require_once("../inc/util.inc");
require_once("../inc/su.inc");

$user = get_logged_in_user();
page_head("Accounting history");
show_user_acct_history($user);
page_tail();

?>
