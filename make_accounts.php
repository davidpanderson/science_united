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
//   (this lets us respond quickly to AM RPC actions)
//

require_once("../inc/db_conn.inc");
require_once("../inc/web_rpc_api.inc");
require_once("../inc/common_defs.inc");

require_once("../inc/su_db.inc");

function log_write($x) {
    echo date(DATE_RFC822), ": $x\n";
    flush();
}

// return true if made any DB changes
//
function do_pass() {
    $now = time();
    log_write("doing DB enum");
    $accts = SUAccount::enum(
        sprintf("state=%d or (state=%d and retry_time<%d)",
            ACCT_INIT, ACCT_TRANSIENT_ERROR, $now
        )
    );
    log_write("got ".count($accts)." results");
    $did_something = false;
    foreach ($accts as $acct) {
        $user = BoincUser::lookup_id($acct->user_id);
        if (!$user) {
            log_write("missing user $acct->user_id");
            continue;
        }
        $project = SUProject::lookup_id($acct->project_id);
        if (!$project) {
            log_write("missing project $acct->project_id");
            $acct->delete();
        }
        if ($project->status == PROJECT_STATUS_HIDE) {
            echo "Project $project->name is deprecated.\n";
            $acct->delete();
            continue;
        }

        log_write(sprintf('trying to make account for user %d on %s',
            $user->id,
            $project->name
        ));
        $email_addr = "su_".substr(random_string(), 0, 8)."@nonexistent.domain";
        $passwd_hash = random_string();
        $name = "Science United user ".substr(random_string(), 0, 8);
        log_write("   email addr '$email_addr'");
        log_write("   name '$name'");
        list($auth, $err, $msg) = create_account(
            $project->web_rpc_url_base,
            $email_addr,
            $passwd_hash,
            $name
        );
        if ($err == ERR_DB_NOT_UNIQUE) {
            log_write("   account exists, but different password");
            $ret = $acct->update(sprintf("state=%d", ACCT_TRANSIENT_ERROR));
            if (!$ret) {
                log_write("su_account update 1 failed");
            }
        } else if ($err) {
            log_write("create_account failed: error $err ($msg)");
            $retry_time = time() + 3600;
            $ret = $acct->update(
                sprintf("state=%d, retry_time=%d", ACCT_TRANSIENT_ERROR, $retry_time)
            );
            if (!$ret) {
                log_write("su_account update 2 failed");
            }
        } else {
            $ret = $acct->update(
                sprintf("state=%d, authenticator='%s', email_addr='%s', passwd_hash='%s', name='%s'",
                    ACCT_SUCCESS, $auth, $email_addr, $passwd_hash, $name
                )
            );
            if (!$ret) {
                log_write("acct update 3 failed");
                echo "su_account update 3 failed\n";
            }
            log_write("create_account success");
            $did_something = true;
        }
    }
    return $did_something;
}

function one_shot($command) {
    log_write("Starting $command");
    while (1) {
        if (!do_pass()) {
            break;
        }
    }
    log_write("Done");
}

function daemon() {
    $t = 0;
    log_write("Starting");
    $ppid = posix_getppid();
    while (1) {
        if (file_exists("../user/make_accounts_trigger")) {
            log_write("doing trigger pass");
            // AM RPC asked us to create accounts
            //
            unlink("../user/make_accounts_trigger");
            do_pass();
        } else if ($t>600) {
            log_write("doing time pass");
            do_pass();
            $t = 0;
        } else {
            sleep(1);
            $t++;
        }
        if (!file_exists("/proc/$ppid")) {
            log_write("Parent shell exited; exiting");
            break;
        }
    }
}

$command = "??";
if ($argc > 1) {
    $command = $argv[1];
}

one_shot($command);
//main();

?>
