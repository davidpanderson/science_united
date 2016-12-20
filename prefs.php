<?php

$dir = getcwd();
chdir('/mydisks/a/users/boincadm/projects/test2/html/user');
require_once("../inc/util.inc");
chdir($dir);

require_once("su_db.inc");

// show/enter/edit prefs

function ukw_lookup($ukws, $id) {
    foreach ($ukws as $uwk) {
        if ($uwk->keyword_id == $id) {
            return $uwk;
        }
    }
    return null;
}

function show_keywords($ukws, $kws, $category, $type) {
    $first = true;
    $x = '';
    foreach ($kws as $kw) {
        if ($kw->category != $category) continue;
        $ukw = ukw_lookup($ukws, $kw->id);
        if (($ukw && ($ukw->type == $type)) || (!$ukw && $type==KW_MAYBE)) {
            if (!$first) $x .= ", ";
            $first = false;
            $x .= $kw->word;
        }
    }
    if ($first) {
        $x .= "---";
    }
    return $x;
}

function prefs_show($user) {
    page_head("Preferences");
    $ukws = SUUserKeyword::enum("user_id=$user->id");
    $kws = SUKeyword::enum();
    start_table();
    row_heading('Types of science');
    row2('Yes', show_keywords($ukws, $kws, SCIENCE, KW_YES));
    row2('No', show_keywords($ukws, $kws, SCIENCE, KW_NO));
    row2('Maybe', show_keywords($ukws, $kws, SCIENCE, KW_MAYBE));

    row_heading('Locations');
    row2('Yes', show_keywords($ukws, $kws, LOCATION, KW_YES));
    row2('No', show_keywords($ukws, $kws, LOCATION, KW_NO));
    row2('Maybe', show_keywords($ukws, $kws, LOCATION, KW_MAYBE));
    end_table();

    echo '<p><a class="btn btn-success" href="prefs.php?action=prefs_edit_form">Edit</a>
    ';
    page_tail();
}

// show keywords w/ radio buttons
//
function show_keywords_checkbox($kws, $ukws, $category) {
    $uprefs = array();
    foreach ($ukws as $uwk) {
        $uprefs[$uwk->keyword_id] = $uwk->type;
    }
    row_heading_array(array('', 'Yes', 'Maybe', 'No'), null, 'bg-default');
    foreach ($kws as $kw) {
        $yes = $maybe = $no = '';
        if ($kw->category != $category) continue;
        if (array_key_exists($kw->id, $uprefs)) {
            switch ($uprefs[$kw->id]) {
            case KW_YES:
                $yes = 'checked';
                break;
            case KW_NO: 
                $no = 'checked';
                break;
            }
        } else {
            $maybe = 'checked';
        }
        $name = "kw_$kw->id";
        table_row(
            $kw->word,
            sprintf('<input type="radio" name="%s" value="%d" %s>',
                $name, KW_YES, $yes
            ),
            sprintf('<input type="radio" name="%s" value="%d" %s>',
                $name, KW_MAYBE, $maybe
            ),
            sprintf('<input type="radio" name="%s" value="%d" %s>',
                $name, KW_NO, $no
            )
        );
    }
}

function prefs_edit_form($user) {
    page_head("Edit preferences");
    form_start('prefs.php');
    form_input_hidden('action', 'prefs_edit_action');
    $kws = SUKeyword::enum();
    $ukws = SUUserKeyword::enum("user_id = $user->id");
    start_table('table-striped');
    row_heading('Type of science');
    show_keywords_checkbox($kws, $ukws, SCIENCE);
    row_heading('Location');
    show_keywords_checkbox($kws, $ukws, LOCATION);
    end_table();
    echo '<button type="submit" class="btn btn-success">OK</button>
    ';
    form_end();
    page_tail();
}

function get_cur_val($kw_id, $ukws) {
    foreach ($ukws as $ukw) {
        if ($ukw->keyword_id != $kw_id) continue;
        return $ukw->type;
    }
    return KW_MAYBE;
}

function prefs_edit_action($user) {
    $kws = SUKeyword::enum();
    $ukws = SUUserKeyword::enum("user_id=$user->id");
    foreach ($kws as $kw) {
        $name = "kw_$kw->id";
        $val = get_int($name);
        $cur_val = get_cur_val($kw->id, $ukws);
        if ($val == $cur_val) continue;
        switch ($val) {
        case KW_NO:
        case KW_YES:
            if ($cur_val == KW_MAYBE) {
                SUUserKeyword::insert("(user_id, keyword_id, type) values ($user->id, $kw->id, $val)");
            } else {
                SUUserKeyword::update("type=$val where user_id=$user->id and keyword_id=$kw->id");
            }
            break;
        case KW_MAYBE:
            SUUserKeyword::delete("keyword_id=$kw-id and user_id=$user->id");
            break;
        }
    }
    Header("Location: prefs.php");
}

$user = get_logged_in_user();
$action = get_str('action', true);

switch ($action) {
case 'prefs_edit_form':
    prefs_edit_form($user);
    break;
case 'prefs_edit_action':
    prefs_edit_action($user);
    break;
default:
    prefs_show($user);
}

?>
