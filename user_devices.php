<?php
$user = get_logged_in_user();
page_head("Your computers");
show_user_hosts($user);
page_tail();
?>
