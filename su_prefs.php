<?php

require_once("../inc/util.inc");
require_once("../inc/keywords.inc");

require_once("su_db.inc");
require_once("su.inc");

// Interactive page for editing keyword prefs
//
// The PHP generates rows for all keywords, and hides the appropriate ones.
// The Javascript hides and unhides rows as needed.
//
// element IDs for keyword K
//
// rowK: the <tr> row element
// textK: the plus or minus sign, or spaces
// radioK: the radio buttons

// for each keyword K:
// - generate list of K's children
//
function keyword_setup($uprefs) {
    global $job_keywords;
    foreach ($job_keywords as $id=>$k) {
        $job_keywords[$id]->children = array();
        $job_keywords[$id]->expand = 0;
    }
    foreach ($job_keywords as $id=>$k) {
        if (!$k->parent) continue;
        $job_keywords[$k->parent]->children[] = $id;
    }
    foreach ($uprefs as $id=>$n) {
        while (1) {
            $id = $job_keywords[$id]->parent;
            if (!$id) break;
            $job_keywords[$id]->expand = true;
        }
    }
}

// output a keyword and (recursively) its descendants.
// If its parent is expanded, show it, else hide
//
function generate_html_kw($id, $uprefs) {
    global $job_keywords;
    $kw = $job_keywords[$id];
    $u = $uprefs[$id];
    $yes_checked = ($u == KW_YES)?"checked":"";
    $no_checked = ($u == KW_NO)?"checked":"";
    $maybe_checked = ($u == KW_MAYBE)?"checked":"";

    echo sprintf('<tr id="%s" hidden>%s', "row$id", "\n");
    echo sprintf('   <td id="%s"></td>%s', "text$id", "\n");
    echo sprintf('   <td><input onclick="radio(%d, 1)" type="radio" name="%s" value="%d" %s></td>%s',
        $id, "radio$id", KW_YES, $yes_checked, "\n"
    );
    echo sprintf('   <td><input onclick="radio(%d, 0)" type="radio" name="%s" value="%d" %s></td>%s',
        $id, "radio$id", KW_MAYBE, $maybe_checked, "\n"
    );
    echo sprintf('   <td><input onclick="radio(%d, -1)" type="radio" name="%s" value="%d" %s></td>%s',
        $id, "radio$id", KW_NO, $no_checked, "\n"
    );
    echo "</tr>\n";

    foreach ($kw->children as $k) {
        generate_html_kw($k, $uprefs);
    }
}

function generate_html_category($category, $uprefs) {
    global $job_keywords;
    row_heading_array(array('', 'Priority', 'As needed', 'Never'), null, 'bg-default');
    foreach ($job_keywords as $id=>$k) {
        if ($k->category != $category) continue;
        if ($k->parent) continue;
        generate_html_kw($id, $uprefs);
    }
}

function generate_javascript($uprefs) {
    global $job_keywords;
    echo "<script>
        var ids = new Array;
        var names = new Array;
        var levels = new Array;
        var parent = new Array;
        var radio_value = new Array;
        var children = new Array;
";
    foreach ($job_keywords as $id=>$k) {
        $val = $uprefs[$id];
        echo "
            names[$id] = '$k->name';
            levels[$id] = $k->level;
            parent[$id] = $k->parent;
            radio_value[$id] = $val;
            children[$id] = new Array;
            ids.push($id);
        ";
    }
    echo sprintf("var nkws = %d;\n", count($job_keywords));
    echo <<<EOT
    var rows = new Array;
    var texts = new Array;
    var children = new Array;
    var expanded = new Array;
    for (i=0; i<nkws; i++) {
        children[ids[i]] = new Array;
    }
    for (i=0; i<nkws; i++) {
        var j = ids[i];
        var pid = parent[j];
        if (pid) {
            children[pid].push(j);
        }
        var rowid = "row"+j;
        var textid = "text"+j;
        rows[j] = document.getElementById(rowid);
        texts[j] = document.getElementById(textid);
        expanded[j] = false;
    }

    function all_siblings_maybe(id) {
        siblings = children[parent[id]];
        for (i=0; i<siblings.length; i++) {
            j = siblings[i];
            if (j == id) continue;
            if (radio_value[j]) {
                return false;
            }
        }
        return true;
    }

    var font_size = [120, 108, 92, 80];
    var indent = [0, 1.3, 2.6, 3.9];
    var button_indent = 1.3;

    // -1: show "contract" button
    // 0: no button
    // 1: show "expand" button
    //
    function set_expand(k, val) {
        console.log('set_expand ', k, val);
        var level = levels[k];
        var x = '<span style="font-size:'+font_size[level]+'%">';
        x += '<span style="padding-left:'+indent[level]+'em"/>';
        if (val < 0) {
            x += '<a onclick="expand_contract('+k+')" id="t'+k+'" href=#/>&boxminus;</a> ';
        } else if (val == 0) {
            x += '<span style="padding-left:'+button_indent+'em"/>';
        } else {
            x += '<a onclick="expand_contract('+k+')" id="t'+k+'" href=#/>&boxplus;</a> ';
        }
        x += names[k];
        var t = texts[k];
        t.innerHTML = x;
    }

    function radio(k, val) {
        console.log('radio ', k, val, parent[k]);
        radio_value[k] = val;
        if (parent[k]) {
            if (val) {
                // if yes or no, disable contraction of parent
                //
                set_expand(parent[k], 0);
            } else {
                // if "maybe", see if all siblings are maybe,
                // and if so enable contraction of parent
                if (all_siblings_maybe(k)) {
                    set_expand(parent[k], -1);
                }
            }
        }
    }

    // click on expand/contract link
    //
    function expand_contract(k) {
        var x = children[k];
        var a = rows[k];
        var t = texts[k];
        expanded[k] = !expanded[k];
        set_expand(k, expanded[k]?-1:1);
        var h = expanded[k]?false:true;
        console.log('expand_contract ', k, h);
        for (i=0; i<x.length; i++) {
            var b = rows[x[i]];
            b.hidden = h;
        }
        return false;
    }

    function init() {
        for (i=0; i<nkws; i++) {
            var j = ids[i];
            console.log(i, j);
            if (radio_value[j]) {
                while (1) {
                    j = parent[j];
                    if (!j) break;
                    expanded[j] = true;
                    for (k=0; k<children[j].length; k++) {
                        c = (children[j])[k];
                        rows[c].hidden = false;
                    }
                }
            }
        }
        for (i=0; i<nkws; i++) {
            var j = ids[i];
            console.log(i, j);
            var a = rows[j];
            if (expanded[j]) {
                a.hidden = false;
                set_expand(j, 0);
            } else if (!parent[j]) {
                a.hidden = false;
                set_expand(j, 1);
            } else {
                set_expand(j, 0);
            }
        }
    }

    init();

EOT;
    echo "</script>\n";
}

function prefs_edit_form($user) {
    global $job_keywords;
    $ukws = SUUserKeyword::enum("user_id = $user->id");

    // convert user prefs to a map id=>-1/0/2
    //
    $uprefs = array();
    foreach ($job_keywords as $id=>$kw) {
        $uprefs[$id] = 0;
    }
    foreach ($ukws as $ukw) {
        $uprefs[$ukw->keyword_id] = $ukw->yesno;
    }

    keyword_setup($uprefs);

    page_head("Edit preferences");
    form_start("su_prefs2.php");
    form_input_hidden('action', 'submit');
    start_table("table-striped");
    row_heading("Science areas", "bg-info");
    generate_html_category(KW_CATEGORY_SCIENCE, $uprefs);
    row_heading("Locations", "bg-info");
    generate_html_category(KW_CATEGORY_LOC, $uprefs);
    end_table();
    form_submit("OK");
    form_end();
    generate_javascript($uprefs);
    page_tail();
}

// return current user setting for the given keyword
//
function get_cur_val($kw_id, $ukws) {
    foreach ($ukws as $ukw) {
        if ($ukw->keyword_id != $kw_id) continue;
        return $ukw->yesno;
    }
    return KW_MAYBE;
}

function prefs_edit_action($user) {
    global $job_keywords;
    $ukws = SUUserKeyword::enum("user_id=$user->id");
    foreach ($job_keywords as $id=>$kw) {
        $name = "radio$id";
        $val = get_int($name, true);
        $cur_val = get_cur_val($id, $ukws);
        if ($val == $cur_val) continue;
        switch ($val) {
        case KW_NO:
        case KW_YES:
            if ($cur_val == KW_MAYBE) {
                SUUserKeyword::insert("(user_id, keyword_id, yesno) values ($user->id, $id, $val)");
            } else {
                SUUserKeyword::update("yesno=$val where user_id=$user->id and keyword_id=$id");
            }
            break;
        case KW_MAYBE:
            $ukw = new SUUserKeyword;
            $ukw->keyword_id = $id;
            $ukw->user_id = $user->id;
            $ukw->delete();
            break;
        }
    }
}

$user = get_logged_in_user();
$action = get_str('action', true);

if ($action == "submit") {
    prefs_edit_action($user);
}
prefs_edit_form($user);

?>
