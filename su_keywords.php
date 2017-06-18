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

require_once("../inc/su.inc");
require_once("../inc/util.inc");

function show_category($cat, $cat_name) {
    $kws = SUKeyword::enum("category=$cat");
    start_table('table-striped');
    row_heading_array(array(
        "Keyword<br><small>click to edit</small>",
        "Major?"
    ));
    foreach($kws as $kw) {
        row_array(array(
            sprintf(
                '<a href="su_keywords.php?action=edit_form&keyword_id=%d">%s</a>',
                $kw->id, $kw->word
            ),
            $kw->priority==KW_MAJOR?"yes":"no"
        ));
    }
    end_table();
    echo sprintf('<p>Add keyword: <input name=new_kw_%s>', $cat_name);
}

function show_all() {
    page_head("Keywords");
    echo '<form action="su_keywords.php">
        <input type="hidden" name="action" value="edit_all_action">
    ';
    echo "<h3>Science</h3>\n";
    show_category(SCIENCE, 'sci');
    echo "<h3>Location</h3>\n";
    show_category(LOCATION, 'loc');
    echo '
        <p>
        <input type="submit" class="btn btn-success" value="OK">
        <p>
        <a href=su_manage.php>Return to Admin page</a>
    ';
    page_tail();
}

function edit_all_action() {
    print_r($_GET);
    $new_kw_sci = get_str('new_kw_sci', true);
    if ($new_kw_sci) {
        SUKeyword::insert(
            sprintf(
                "(word, category, priority) values ('%s', %d, %d)",
                $new_kw_sci, SCIENCE, KW_MINOR
            )
        );
    }
    $new_kw_loc = get_str('new_kw_loc', true);
    if ($new_kw_loc) {
        SUKeyword::insert(
            sprintf(
                "(word, category, priority) values ('%s', %d, %d)",
                $new_kw_loc, LOCATION, KW_MINOR
            )
        );
    }
    header("Location: su_keywords.php");
}

function edit_form() {
    $keyword_id = get_int('keyword_id');
    $kw = SUKeyword::lookup_id($keyword_id);
    page_head("Edit keyword");
    echo sprintf('
        <form action="su_keywords.php">
        <input type="hidden" name="action" value="edit_action">
        <input type="hidden" name="keyword_id" value="%d">',
        $keyword_id
    );
    start_table();
    row2("keyword",
        sprintf('<input type="text" name="word" value="%s">', $kw->word)
    );
    row2("Major?",
        sprintf('<input type="checkbox" name="major" %s>',
            $kw->priority==KW_MAJOR?"checked":""
        )
    );
    row2("",
        '<input type="submit" class="btn btn-success" value="OK">'
    );
    end_table();
    echo "</form>
        <p>
    ";
    echo sprintf(
        '<a class="btn btn-danger" href=su_keywords.php?action=delete_confirm&keyword_id=%d>Delete keyword</a>',
        $keyword_id
    );
    echo '
        <p>
        <a href="su_keywords.php">Return to keyword list</a>
    ';
    page_tail();

}

function edit_action() {
    $keyword_id = get_int('keyword_id');
    $kw = SUKeyword::lookup_id($keyword_id);
    $word = get_str('word');
    $priority = get_str('major', true)?KW_MAJOR:KW_MINOR;
    $kw->update("word='$word', priority=$priority");
    header("Location: su_keywords.php");
}

function delete_confirm() {
    $keyword_id = get_int('keyword_id');
    $kw = SUKeyword::lookup_id($keyword_id);
    page_head("Confirm deletion of keyword $kw->word");
    echo '
        Are you sure you want to delete this keyword?
        Doing so may confuse or annoy volunteers who used it.
        <p></p>
    ';
    echo sprintf(
        '<a class="btn btn-danger" href="su_keywords.php?action=delete&keyword_id=%d">Delete</a>',
        $keyword_id
    );
    echo '<p></p><a class="btn btn-success" href="su_keywords.php">Cancel</a>';
    page_tail();
}

function delete() {
    $keyword_id = get_int('keyword_id');
    SUProjectKeyword::delete("keyword_id=$keyword_id");
    SUUserKeyword::delete("keyword_id=$keyword_id");
    $kw = SUKeyword::lookup_id($keyword_id);
    $kw->delete();
    header("Location: su_keywords.php");
}

admin_only();

$action = get_str('action', true);
switch($action) {
case null:
    show_all();
    break;
case 'edit_all_action':
    edit_all_action();
    break;
case "edit_action":
    edit_action();
    break;
case "edit_form":
    edit_form();
    break;
case "delete_confirm":
    delete_confirm();
    break;
case "delete":
    delete();
}

?>
