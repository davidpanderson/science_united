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

require_once("../inc/util.inc");
require_once("../inc/account.inc");
require_once("../inc/recaptchalib.php");
require_once("../inc/su.inc");

function keyword_prefs_form() {
    $kwds = SUKeyword::enum("category=0 and level=0");

    $items = array();
    foreach ($kwds as $k) {
        $items[] = array($k->id, $k->word);
    }
    form_checkboxes(
        "Check the areas of science you most want to support",
        $items
    );
}

function comp_prefs_form() {
    form_radio_buttons(
        "Computer usage",
        "usage",
        array(
            array(0, "Light - minimize power consumption"),
            array(1, "Medium"),
            array(2, "Maximum"),
        )
    );
}

function show_form() {
    global $recaptcha_public_key;

    page_head("Join ".PROJECT, null, null, null, boinc_recaptcha_get_head_extra());
    form_start("su_join.php?action=go");
    create_account_form(0, "su_download.php");
    keyword_prefs_form();
    comp_prefs_form();
    if ($recaptcha_public_key) {
        form_general("", boinc_recaptcha_get_html($recaptcha_public_key));
    }
    form_submit("Join");
    form_end();
    page_tail();
}

show_form();

?>
