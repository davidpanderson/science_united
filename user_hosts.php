<?php
require_once("../inc/util.inc");

function show_user_hosts($user) {
    start_table('table-striped');
    row_heading_array(array(
        "name<br><small>click for details</small>",
        "last RPC",
    ));
    $hosts = BoincHost::enum("userid=$user->id");
    foreach ($hosts as $host) {
        row_array(array(
            '<a href="user_host.php?host_id='.$host->id.'">'.$host->domain_name.'</a>',
            date_str($host->rpc_time)
        ));
    }
    end_table();
}

$user = get_logged_in_user();
page_head("Your computers");
show_user_hosts($user);
page_tail();
?>
