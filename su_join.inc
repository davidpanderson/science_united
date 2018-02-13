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
require_once("../inc/recaptchalib.php");

function su_join_form($next_url) {
    global $recaptcha_public_key;

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
            tra("Screen name")
        ),
        "new_name"
    );
    form_input_text(
        sprintf('<span title="%s">%s</span>',
            tra("Must be a valid address of the form 'name@domain'."),
            tra("Email address")
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
            tra("Password")
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
        "Science areas you most want to support",
        $items
    );
}

function global_prefs_form() {
    form_radio_buttons(
        "Use of your computer",
        "preset",
        array(
            array('green', "Green - limit power consumption"),
            array('standard', "Standard"),
            array('max', "Maximum"),
        ),
        'standard'
    );
}

function show_join_form() {
    global $recaptcha_public_key;

    form_start("su_join.php", "post");
    form_input_hidden("action", "join");
    su_join_form(0, "su_download.php");
    keyword_prefs_form();
    global_prefs_form();
    if ($recaptcha_public_key) {
        form_general("", boinc_recaptcha_get_html($recaptcha_public_key));
    }
    form_submit("Join");
    form_end();
}

?>
