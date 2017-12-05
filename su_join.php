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

// join page for new users

require_once("../inc/user_util.inc");
require_once("../inc/account.inc");
require_once("../inc/recaptchalib.php");

require_once("../inc/keywords.inc");
require_once("../inc/su_join.inc");
require_once("../inc/su_schedule.inc");
require_once("../inc/su_compute_prefs.inc");

// we need to create:
// - the user record, with chosen computing prefs
// - user/keyword records
//
function handle_submit() {
    global $job_keywords;
    $user = validate_post_make_user();
    if (!$user) {
        error_page("Couldn't create user record");
    }
    $preset = post_str("preset");
    $prefs = compute_prefs_xml($preset);
    $user->update("global_prefs='$prefs'");
    foreach ($job_keywords as $id=>$k) {
        if ($k->category != KW_CATEGORY_SCIENCE) continue;
        if ($k->level > 0) continue;
        $x = "keywd_".$id;
        if (post_str($x, true)) {
            SUUserKeyword::insert(
                sprintf("(user_id, keyword_id, yesno) values (%d, %d, %d)",
                    $user->id, $id, KW_YES
                )
            );
        }
    }

    // initiate project account creation
    //
    $projects = choose_projects_join($user);
    foreach ($projects as $p) {
        $ret = SUAccount::insert(
            sprintf("(project_id, user_id, create_time, state) values (%d, %d, %f, %d)",
                $p->id, $user->id, time(), ACCT_INIT
            )
        );
    }
    Header("Location: download.php");
    send_cookie('auth', $user->authenticator, false);
}

$action = post_str('action', true);
if ($action == "join") {
    handle_submit();
} else {
    page_head(
        sprintf("%s %s", tra("Join"), PROJECT),
        null, null, null, boinc_recaptcha_get_head_extra()
    );
    show_join_form();
    page_tail();
}

?>
