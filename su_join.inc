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

function keyword_prefs_form() {
    global $job_keywords;

    $items = array();
    foreach ($job_keywords as $id=>$k) {
        if ($k->category != KW_CATEGORY_SCIENCE) continue;
        if ($k->level > 0) continue;
        $items[] = array("keywd_".$id, $k->name);
    }
    form_checkboxes(
        "Check the areas of science you most want to support",
        $items
    );
}

function global_prefs_form() {
    form_radio_buttons(
        "Computer usage",
        "preset",
        array(
            array('green', "Green - limit power consumption"),
            array('standard', "Standard"),
            array('max', "Maximum computing"),
        ),
        'standard'
    );
}

function show_join_form() {
    global $recaptcha_public_key;

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
}

?>
