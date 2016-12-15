<?php

// show/enter/edit science prefs

function sci_prefs_show() {
}

function sci_prefs_edit_form() {
}

function sci_prefs_edit_action() {
}

$action = get_str('action', true);

switch ($action) {
case 'edit_form':
    sci_prefs_edit_form();
    break;
case 'edit_action':
    sci_prefs_edit_action();
    break;
default:
    sci_prefs_show();
}

?>
