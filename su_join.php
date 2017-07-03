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
require_once("../inc/su.inc");

function keyword_prefs_form() {
    $kwds = SUKeyword::enum("category=0 and level=0");

    $items = array();
    foreach ($kwds as $k) {
        $items[] = array("keywd_".$k->id, $k->word);
    }
    form_checkboxes(
        "Check the areas of science you most want to support",
        $items
    );
}

function global_prefs_form() {
    form_radio_buttons(
        "Computer usage",
        "usage",
        array(
            array('min', "Light - minimize power consumption"),
            array('med', "Medium"),
            array('max', "Maximum"),
        ),
        'med'
    );
}

function show_form() {
    global $recaptcha_public_key;

    page_head("Join ".PROJECT, null, null, null, boinc_recaptcha_get_head_extra());
    form_start("su_join.php", "post");
    form_input_hidden("action", "join");
    create_account_form(0, "su_download.php");
    keyword_prefs_form();
    global_prefs_form();
    if ($recaptcha_public_key) {
        form_general("", boinc_recaptcha_get_html($recaptcha_public_key));
    }
    form_submit("Join");
    form_end();
    page_tail();
}

// we need to create:
// - the user record, with chosen computing prefs
// - user/keyword records
//
function handle_submit() {
    $user = validate_post_make_user();
    if (!$user) {
        error_page("Couldn't create user record");
    }
    $usage = post_str("usage");
    $user->update("global_prefs='$usage'");
    $kwds = SUKeyword::enum("category=0 and level=0");
    foreach ($kwds as $k) {
        $x = "keywd_".$k->id;
        echo "$x ";
        if (post_str($x, true)) {
            SUUserKeyword::insert(
                sprintf("(user_id, keyword_id, type) values (%d, %d, %d)",
                    $user->id, $k->id, KW_YES
                )
            );
        }
    }
    echo "foobar";
    //Header("Location: su_download.php");
}

$action = post_str('action', true);
if ($action == "join") {
    handle_submit();
} else {
    show_form();
}

?>
