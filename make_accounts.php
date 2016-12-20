<?php

// create project accounts

function main() {
    $accts = SUAccount::enum(
        sprintf("state=%d or (state=%d and retry>%d)", INIT, TRANSIENT, $now));
    );
    foreach ($accts as $acct) {
        $user = BoincUser::lookup_id($acct->user_id);
        $project = SUProject::lookup_id($acct->project_id);
        if (!$user) {
            echo "missing user $acct->user_id\n";
            continue;
        }
        $ret = create_account($project, $user);
        if ($ret == PASSWD) {
            $acct->update(sprintf("state=%d", DIFFERENT_PASSWD));
        } else if ($ret) {
            $retry = time() + 3600;
            $acct->update(
                sprintf("state=%d, retry=%d", TRANSIENT_ERROR, $retry)
            );
        }
    }
}

main();

?>
