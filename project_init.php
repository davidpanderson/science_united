<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2018 University of California
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
// run this from html/ops
// RUN WHENEVER BOINC PROJECT LIST HAS CHANGED
//
// input: html/ops/projects.xml
// get this from https://boinc.berkeley.edu/project_list.php
//
// IF ANYTHING HAS CHANGED:
// run project_digest.php as well (to update projects.ser)

require_once("../inc/keywords.inc");
require_once("../inc/project_ids.inc");
require_once("../inc/su_db.inc");

// remove projects and everything that refers to them
// THINK TWICE BEFORE DOING THIS
//
function clean() {
    die();
    foreach (SUProject::enum() as $p) {
        $p->delete();
    }
    SUAccount::delete_all();
    SUHostProject::delete_all();
    SUAccountingProject::delete_all();
}

function update_project($p) {
    $name = (string)$p->name;
    $url = (string)$p->url;
    $web_url = (string)$p->web_url;
    $web_rpc_url_base = (string)$p->web_rpc_url_base;
    if (!$web_rpc_url_base) {
        $web_rpc_url_base = $url;
    }
    $project_id = (string)$p->id;
    $now = time();
    $cmd = "~/boinc/lib/crypt_prog -sign_string $url ~/science_united/code_sign_private";
    $out = array();
    $retval = 0;
    exec($cmd, $out, $retval);
    if ($retval) {
        die("$cmd failed\n");
    }
    $url_signature = implode("\n", $out);

    $project = SUProject::lookup_id($project_id);
    if ($project) {
        $project->update("status = ".PROJECT_STATUS_AUTO);
        if ($url_signature != $project->url_signature) {
            echo "updating $project->name URL signature\n";
            $ret = $project->update("url_signature='$url_signature'");
            if (!$ret) echo "update failed\n";
        }
        if ($url != $project->url) {
            echo "updating $project->name URL\n";
            $ret = $project->update("url='$url'");
            if (!$ret) echo "update failed\n";
        }
        if ($web_url != $project->web_url) {
            echo "updating $project->name web URL\n";
            $ret = $project->update("web_url='$web_url'");
            if (!$ret) echo "update failed\n";
        }
        if ($web_rpc_url_base != $project->web_rpc_url_base) {
            echo "updating $project->name RPC URL base\n";
            $ret = $project->update("web_rpc_url_base='$web_rpc_url_base'");
            if (!$ret) echo "update failed\n";
        }
    } else {
        SUProject::insert("(id, create_time, name, url, web_rpc_url_base, url_signature, share, status) values ($project_id, $now, '$name', '$url', '$web_rpc_url_base', '$url_signature', 10, ".PROJECT_STATUS_AUTO.")");

        if (!SUAccountingProject::insert("(project_id, create_time) values ($project_id, $now)")) {
            die("su_accounting_project insert failed\n");
        }
        echo "Added project $name\n";
    }
}

// create or update projects based on projects.xml
// If a project is not there, flag as deprecated
// Skip certain projects.
//
function update_projects() {
    $projs = SUProject::enum();
    foreach ($projs as $p) {
        $p->update("status = ".PROJECT_STATUS_HIDE);
    }
    $x = simplexml_load_file("projects.xml");
    $projects = $x->project;
    foreach ($projects as $p) {
        //if ((int)$p->id == PROJ_WCG) continue;
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
        update_project($p);
    }
}

update_projects();

?>
