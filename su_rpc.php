<?php

// This file is part of BOINC.
// https://boinc.berkeley.edu
// Copyright (C) 2021 University of California
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

// RPC handler for getting and setting user keyword preferences

require_once('../inc/xml.inc');
require_once('../inc/su_db.inc');

// get a list of the user's keyword prefs
//
function get_keywords($user) {
    $kws = SUUserKeyword::enum("user_id=$user->id");
    xml_header();
    echo "<keywords>\n";
    foreach ($kws as $kw) {
        echo sprintf(
'   <keyword>
        <id>%d</id>
        <yesno>%d</yesno>
    </keyword>
',
            $kw->keyword_id,
            $kw->yesno
        );
    }
    echo "</keywords>\n";
}

// Set new keyword prefs for the user.
// The RPC specifies a list of them; delete any not in this list
//
function set_keywords($user) {
    $new_kws = array();
    foreach ($_GET as $key=>$val) {
        if (is_numeric($key)) {
            $v = (int)$val;
            if ($v != 1 && $v != -1) {
                xml_error(-1, "value not integer");
            }
            $new_kws[(int)$key] = $v;
        }
    }
    $kws = SUUserKeyword::enum("user_id=$user->id");
    $old_kws = array();
    foreach ($kws as $kw) {
        $old_kws[$kw->keyword_id] = $kw->yesno;
    }

    // delete unspecified keywords
    //
    $kws2 = array();
    foreach ($old_kws as $id => $val) {
        if (array_key_exists($id, $new_kws)) {
            $kws2[$id] = $val;
        } else {
            $x = new SUUserKeyword;
            $x->user_id = $user->id;
            $x->keyword_id = $id;
            $x->delete();
        }
    }

    foreach ($new_kws as $id => $val) {
        if (array_key_exists($id, $kws2)) {
            if ($kws2[$id] != $val) {
                SUUserKeyword::update("yesno=$val where user_id=$user->id and keyword_id=$id");
            }
        } else {
            SUUserKeyword::insert("(user_id, keyword_id, yesno) values ($user->id, $id, $val)");
        }
    }
    xml_header();
    echo "<success/>\n";
}

$key = parse_config(get_config(), "<rpc_key>");
if ($key) {
    $rpc_key = get_str('rpc_key');
    if ($rpc_key != $key) {
        xml_error("RPC key");
    }
}

$action = get_str('action');
$user = BoincUser::lookup_auth(get_str('auth'));
if (!$user) {
    xml_error(-1, 'bad auth');
}

if ($action == 'get_keywords') {
    get_keywords($user);
} else if ($action == 'set_keywords') {
    set_keywords($user);
} else {
    xml_error(-1, 'bad action');
}

?>
