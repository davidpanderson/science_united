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


// for each kw K
// nprefs(K) = how many descendants of K have nonzero pref
// expanded(K): true if user chose to expand K
//      persists if ancestors are contracted;
//      doesn't persist if nprefs becomes nonzero
//
// actions:
// click on expand or contract: set or clear expanded(K)
// radio: recompute nprefs for all ancestors
//
// render:
//  for each terminal node K
//      if nprefs(parent)
//          unhide
//      else if all ancesters are either nprefs<>0 or expanded
//          unhide
//      else
//          hide
//
//  for each nonterminal node K
//      if nprefs(K)
//          expanded=true
//          unhide
//          button=none
//      else if nprefs(parent)
//          unhide
//          set button according to expanded
//      else if all ancestors are expanded
//          set button according to expanded
//      else
//          hide


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
";
    foreach ($job_keywords as $id=>$k) {
        $val = $uprefs[$id];
        echo "
            names[$id] = '$k->name';
            levels[$id] = $k->level;
            parent[$id] = $k->parent;
            radio_value[$id] = $val;
            ids.push($id);
        ";
    }
    echo sprintf("var nkws = %d;\n", count($job_keywords));
    echo <<<EOT
    var rows = new Array;
    var texts = new Array;
    var expanded = new Array;
    var terminal = new Array;
    var nprefs = new Array;

    // initialize stuff

    for (i=0; i<nkws; i++) {
        terminal[ids[i]] = true;
        nprefs[ids[i]] = 0;
    }
    for (i=0; i<nkws; i++) {
        var j = ids[i];
        var rowid = "row"+j;
        var textid = "text"+j;
        rows[j] = document.getElementById(rowid);
        texts[j] = document.getElementById(textid);
        if (parent[j]) {
            terminal[parent[j]] = false;
        }
        expanded[j] = false;

        if (radio_value[j]) {
            k = j;
            while (1) {
                k = parent[k];
                if (!k) break;
                nprefs[k]++;
            }
        }
    }

    var font_size = [120, 108, 92, 80];
    var indent = [0, 1.3, 2.6, 3.9];
    var button_indent = 1.3;

    // -1: show "contract" button
    // 0: no button
    // 1: show "expand" button
    //
    function set_expand(k, val) {
        //console.log('set_expand ', k, val);
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
        texts[k].innerHTML = x;
    }

    function radio(k, val) {
        //console.log('radio ', k, val, parent[k]);
        old_val = radio_value[k];
        radio_value[k] = val;
        inc = 0;
        if (val && !old_val) inc = 1;
        if (!val && old_val) inc = -1;
        if (inc) {
            while (1) {
                k = parent[k];
                if (!k) break;
                nprefs[k] += inc;
            }
        }
        render();
    }

    // click on expand/contract link
    //
    function expand_contract(k) {
        expanded[k] = !expanded[k];
        set_expand(k, expanded[k]?-1:1);
        var h = expanded[k]?false:true;
        //console.log('expand_contract ', k, h);
        render();
        return false;
    }

    // return true if all ancestrors of i are expanded or nprefs>0
    //
    function ancestors_expanded(i) {
        while (1) {
            i = parent[i];
            if (!i) break;
            if (!nprefs[i] && !expanded[i]) return false;
        }
        return true;
    }

    function render() {
        for (i=0; i<nkws; i++) {
            j = ids[i];
            if (terminal[j]) {
                set_expand(j, 0);
                if (nprefs[parent[j]]>0 || ancestors_expanded(j)) {
                    rows[j].hidden = false;
                } else {
                    rows[j].hidden = true;
                }
            } else {
                console.log("nprefs ", j, nprefs[j]);
                if (nprefs[j]) {
                    expanded[j] = true;
                    rows[j].hidden = false;
                    set_expand(j, 0);
                } else {
                    p = parent[j];
                    if (p) {
                        if (nprefs[parent[j]]>0 || ancestors_expanded(j)) {
                            rows[j].hidden = false;
                            set_expand(j, expanded[j]?-1:1);
                        } else {
                            rows[j].hidden = true;
                        }
                    } else {
                        rows[j].hidden = false;
                        set_expand(j, expanded[j]?-1:1);
                    }
                }
            }
        }
    }

    render();

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
    form_start("su_prefs.php");
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
