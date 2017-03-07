<?php

require_once("../inc/db_conn.inc");
require_once("../inc/web_rpc_api.inc");
require_once("../inc/common_defs.inc");

require_once("../inc/su_db.inc");

// create project accounts

function main() {
    $now = time();
    $accts = SUAccount::enum(
        sprintf("state=%d or (state=%d and retry_time>%d)", INIT, TRANSIENT_ERROR, $now)
    );
    foreach ($accts as $acct) {
        $user = BoincUser::lookup_id($acct->user_id);
        $project = SUProject::lookup_id($acct->project_id);
        if (!$user) {
            echo "missing user $acct->user_id\n";
            continue;
        }
        echo "making account for user $user->id on $project->name\n";
        list($auth, $err, $msg) = create_account(
            $project->url,
            $user->email_addr,
            $user->passwd_hash,
            $user->name
        );
        if ($err == ERR_DB_NOT_UNIQUE) {
            $acct->update(sprintf("state=%d", DIFFERENT_PASSWD));
            echo "   account exists, but different password\n";
        } else if ($err) {
            $retry_time = time() + 3600;
            $acct->update(
                sprintf("state=%d, retry_time=%d", TRANSIENT_ERROR, $retry_time)
            );
            echo "   error $err ($msg)\n";
        } else {
            $acct->update(
                sprintf("state=%d, authenticator='%s'", SUCCESS, $auth)
            );
            echo "   success\n";
        }
    }
}

main();

?>
