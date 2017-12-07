<?php
require_once("../inc/util.inc");
require_once("../inc/su_db.inc");
require_once("../inc/web_rpc_api.inc");

$user = get_logged_in_user();
$project_id = get_int("project_id");
$project = SUProject::lookup_id($project_id);
list($auth, $err, $msg) = create_account(
    $project->web_rpc_url_base,
    $user->email_addr,
    $user->passwd_hash,
    $user->name
);
echo "$project->web_rpc_url_base, $err $msg";

?>
