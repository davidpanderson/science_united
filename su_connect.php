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

// tell user about password mismatch, let them fix it

require_once("../inc/util.inc");
require_once("../inc/su_db.inc");
require_once("../inc/web_rpc_api.inc");

function show_form($user, $project) {
    page_head("Connect to $project->name");
    echo sprintf('
        You have an account on %s,
        but its password is different from your %s password
        so we can\'t connect your computer to it.
        <p>
        To let us connect to your %s account,
        enter your %s password below, and click OK.
        </ul>',
        $project->name, PROJECT,
        $project->name,
        $project->name
    );
    form_start("su_connect.php", "post");
    form_input_hidden("action", "get_auth");
    form_input_hidden("id", $project->id);
    form_input_text("$project->name password:", "passwd", "", "password");
    form_submit("OK");
    form_end();

    page_tail();
}

function get_auth($user, $project) {
    $passwd = post_str("passwd");
    $passwd_hash = md5($passwd.$user->email_addr);
    list($auth, $errnum, $errstr) = lookup_account(
        $project->web_rpc_url_base, $user->email_addr, $passwd_hash
    );
    if ($auth) {
        $a = SUAccount::lookup("user_id=$user->id and project_id=$project->id");
        if (!$a) {
            error_page("Can't find account.");
        }
        $a->update(
            sprintf("state=%d, authenticator='%s'",
                ACCT_SUCCESS, BoincDb::escape_string($auth)
            )
        );
        page_head("Success");
        echo sprintf("%s is now connected to your %s account",
            PROJECT, $project->name
        );
        page_tail();
    } else {
        page_head("Account lookup failed");
        echo "$project->name reports an error: ($errnum) $errstr.";
        page_tail();
    }
}

function show_list($user) {
    page_head("Password mismatches");
    $accts = SUAccount::enum(
        sprintf("user_id=%d and state=%d and opt_out=0",
            $user->id, ACCT_DIFFERENT_PASSWORD
        )
    );
    if (count($accts) == 0) {
        echo "You have no accounts with mismatched passwords.";
    } else {
        echo "<p>".sprintf(
            "We found accounts on the following BOINC projects
            with the same email address (%s) as your %s account,
            but with different passwords.
            %s can't attach your computer to these accounts.",
            $user->email_addr, PROJECT, PROJECT
        );
        echo "<p>";
        echo sprintf(
            "You can resolve this by entering your project account password.
            %s will use this to connect to the account,
            but will not save it beyond that.
            Click on a project name to do this:",
            PROJECT
        );
        foreach($accts as $acct) {
            $project = SUProject::lookup_id($acct->project_id);
            if (!$project) {
                echo "Missing project $acct->project_id. <p>\n";
                continue;
            }
            echo "<p><a href=su_connect.php?id=$acct->project_id>$project->name</a><p>\n";
        }
        echo sprintf(
            "Alternatively, you can visit the project web site
            and change your password to your %s password.
            %s will automatically retry the project later.",
            PROJECT, PROJECT
        );
    }
    page_tail();
}

$user = get_logged_in_user();

$action = post_str("action", true);
if (!$action) $action = get_str("action", true);
if ($action == "get_auth") {
    $id = post_int("id");
    $project = SUProject::lookup_id($id);
    get_auth($user, $project);
} else if ($action == "list") {
    show_list($user);
} else {
    $id = get_int("id");
    $project = SUProject::lookup_id($id);
    show_form($user, $project);
}

?>
