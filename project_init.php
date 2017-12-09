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

// initialize or update the project DB table
//
// run this from test2/html/ops

require_once("/mydisks/a/users/boincadm/boinc-site/keywords.inc");
require_once("/mydisks/a/users/boincadm/boinc-site/project_ids.inc");
require_once("../inc/su_db.inc");

// remove projects and everything that refers to them
//
function clean() {
    foreach (SUProject::enum() as $p) {
        $p->delete();
    }
    SUProjectKeyword::delete_all();
    SUAccount::delete_all();
    SUHostProject::delete_all();
    SUAccountingProject::delete_all();
}

function get_keywords($p) {
    $keywords = array();
    $ks = explode(" ", (string)$p->keywords);
    foreach ($ks as $k) {
        $x = explode(":", $k);
        if (count($x) > 1) {
            $keywords[] = array((int)$x[0], (double)$x[1]);
        } else {
            $keywords[] = array((int)$x[0], 1);
        }
    }
    return $keywords;
}

function make_project($p) {
    $name = (string)$p->name;
    $url = (string)$p->url;
    $web_rpc_url_base = (string)$p->web_rpc_url_base;
    if (!$web_rpc_url_base) {
        $web_rpc_url_base = $url;
    }
    $project_id = (string)$p->id;
    $project = SUProject::lookup_id($project_id);
    $now = time();
    $cmd = "~/boinc/lib/crypt_prog -sign_string $url ~/science_united/code_sign_private";
    $out = array();
    $retval = 0;
    exec($cmd, $out, $retval);
    if ($retval) {
        die("$cmd failed\n");
    }
    $url_signature = implode("\n", $out);
    SUProject::insert("(id, create_time, name, url, web_rpc_url_base, url_signature, share, status) values ($project_id, $now, '$name', '$url', '$web_rpc_url_base', '$url_signature', 10, 2)");

    $keywords = get_keywords($p);
    foreach ($keywords as $k) {
        $kw_id = $k[0];
        $frac = $k[1];
        SUProjectKeyword::insert("(project_id, keyword_id, work_fraction) values ($project_id, $kw_id, $frac)");
    }

    if (!SUAccountingProject::insert("(project_id, create_time) values ($project_id, $now)")) {
        die("su_accounting_project insert failed\n");
    }

    echo "Added project $name\n";
}

// make projects based on projects.xml
//
function make_projects() {
    $x = simplexml_load_file("projects.xml");
    $projects = $x->project;
    foreach ($projects as $p) {
        if ((int)$p->id == PROJ_WCG) continue;
        if ((int)$p->id == PROJ_QCN) continue;
        if ((int)$p->id == PROJ_RADIOACTIVE) continue;
        if ((int)$p->id == PROJ_LEIDEN) continue;
        if ((int)$p->id == PROJ_MOO) continue;
        if ((int)$p->id == PROJ_YOYO) continue;

        // the following require invitation code; skip
        //
        if ((int)$p->id == PROJ_COLLATZ) continue;
        if ((int)$p->id == PROJ_PRIMABOINCA) continue;
        if ((int)$p->id == PROJ_SRBASE) continue;
        make_project($p);
    }
}

clean();
make_projects();

?>
