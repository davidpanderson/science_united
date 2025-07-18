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

require_once("../inc/util.inc");
require_once("../inc/account.inc");
require_once("../inc/keywords.inc");
require_once("../inc/recaptchalib.inc");

function su_join_form($next_url) {
    form_input_hidden('next_url', $next_url);

    // Using invitation codes to restrict access?
    //
    if (defined('INVITE_CODES')) {
        form_input_text(
            sprintf('<span title="%s">%s</span>',
                tra("An invitation code is required to create an account."),
                tra("Invitation code")
            ),
            "invite_code"
        );
    }

    form_input_text(
        sprintf('<span title="%s">%s</span>',
            tra("Identifies you on this web site. Use your real name or a nickname."),
            tra("Choose screen name")
        ),
        "new_name"
    );
    form_input_text(
        sprintf('<span title="%s">%s</span>',
            tra("An address where you can receive emails."),
            tra("Your email address")
        ),
        "new_email_addr"
    );
    $min_passwd_length = parse_element(get_config(), "<min_passwd_length>");
    if (!$min_passwd_length) {
        $min_passwd_length = 6;
    }

    form_input_text(
        sprintf('<span title="%s">%s</span>',
            tra("Must be at least %1 characters", $min_passwd_length),
            tra("Choose password")
        ),
        "passwd", "", "password", 'id="passwd"',
        passwd_visible_checkbox("passwd")
    );
}

function keyword_prefs_form() {
    global $job_keywords;

    $items = array();
    foreach ($job_keywords as $id=>$k) {
        if ($k->category != KW_CATEGORY_SCIENCE) continue;
        if ($k->level > 0) continue;
        $items[] = array("keywd_".$id, $k->name, false);
    }
    form_checkboxes(
        "Science areas you most want to support
            <br><small>You can refine your preferences later</small>
        ",
        $items
    );
}

function global_prefs_form() {
    form_radio_buttons(
        "Use of your computer",
        "preset",
        array(
            array('low_power', "Low power"),
            array('standard', "Standard"),
            array('max', "Maximum computing"),
        ),
        'standard'
    );
}

function show_join_form() {
    form_start("su_join.php", "post");
    form_input_hidden("action", "join");
    form_general("", "Already using BOINC? <a href=su_boinc_users.php>Read this first.</a>");
    form_general("", "Read our <a href=su_privacy.php>privacy policy.</a>");
    su_join_form(0, "download.php");
    if (PROJECT == 'Science United') {
        keyword_prefs_form();
    }
    global_prefs_form();
    if (recaptcha_public_key()) {
        form_general("", boinc_recaptcha_get_html(recaptcha_public_key()));
    }
    form_submit("Join");
    form_end();
}


