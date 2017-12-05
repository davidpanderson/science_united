#! /usr/bin/env php
<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2017 University of California
//
// BOINC is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// BOINC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

// Daemon to create project accounts.
// Does a pass:
// - every 10 min, to do retries
// - when trigger file is present
//

require_once("../inc/db_conn.inc");
require_once("../inc/web_rpc_api.inc");
require_once("../inc/common_defs.inc");

require_once("../inc/su_db.inc");

function do_pass() {
    $now = time();
    $accts = SUAccount::enum(
        sprintf("state=%d or (state=%d and retry_time<%d)",
            ACCT_INIT, ACCT_TRANSIENT_ERROR, $now
        )
    );
    foreach ($accts as $acct) {
        $user = BoincUser::lookup_id($acct->user_id);
        if (!$user) {
            echo "missing user $acct->user_id\n";
            continue;
        }
        $project = SUProject::lookup_id($acct->project_id);
        echo sprintf('%s: making account for user %d on %s\n";
            $user->id
            $project->name
        ');
        list($auth, $err, $msg) = create_account(
            $project->web_rpc_url_base,
            $user->email_addr,
            $user->passwd_hash,
            $user->name
        );
        echo "err $err msg $msg\n";
        if ($err == ERR_DB_NOT_UNIQUE) {
            $ret = $acct->update(sprintf("state=%d", ACCT_DIFFERENT_PASSWORD));
            if (!$ret) {
                echo "update 1 failed\n";
            }
            echo "   account exists, but different password\n";
        } else if ($err) {
            $retry_time = time() + 3600;
            $ret = $acct->update(
                sprintf("state=%d, retry_time=%d", ACCT_TRANSIENT_ERROR, $retry_time)
            );
            if (!$ret) {
                echo "update 2 failed\n";
            }
            echo "   error $err ($msg)\n";
        } else {
            $ret = $acct->update(
                sprintf("state=%d, authenticator='%s'", ACCT_SUCCESS, $auth)
            );
            if (!$ret) {
                echo "update 3 failed\n";
            }
            echo "   success\n";
        }
    }
}

function main() {
    $t = 0;
    while (1) {
        if (file_exists("make_accounts_trigger") || $t>600) {
            do_pass();
        } else {
            sleep(1);
            $t++;
        }
    }
}

echo date(DATE_RFC822), ": Starting\n";
main();

?>
