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

function make_project($name, $url, $keywords, $web_rpc_url_base=null) {
    global $job_keywords;

    $project = SUProject::lookup("url='$url'");
    if (!$project) {
        $now = time();
        $cmd = "~/boinc/lib/crypt_prog -sign_string $url ~/science_united/code_sign_private";
        $out = array();
        $retval = 0;
        exec($cmd, $out, $retval);
        if ($retval) {
            die("$cmd failed\n");
        }
        if (!$web_rpc_url_base) {
            $web_rpc_url_base = $url;
        }
        $url_signature = implode("\n", $out);
        $id = SUProject::insert("(create_time, name, url, web_rpc_url_base, url_signature, share, status) values ($now, '$name', '$url', '$web_rpc_url_base', '$url_signature', 10, 2)");
    } else {
        $id = $project->id;
    }

    foreach ($keywords as $k) {
        $kw_id = $k[0];
        $frac = $k[1];
        SUProjectKeyword::insert("(project_id, keyword_id, work_fraction) values ($id, $kw_id, $frac)");
    }

    echo "Added project $name\n";
}

// create a limited set of projects (for testing)
//
function make_projects_limited() {
    make_project("LHC@home",
        "https://lhcathome.cern.ch/lhcathome/",
        array(
            array(KW_PARTICLE_PHYSICS, 1),
            array(KW_CERN, 1),
        )
    );
    make_project("SETI@home",
        "https://setiathome.berkeley.edu/",
        array(
            array(KW_SETI, 1),
            array(KW_UCB, 1),
        ),
        "https://setiathome.berkeley.edu/"
    );
    make_project("Rosetta@home",
        "https://boinc.bakerlab.org/rosetta/",
        array(
            array(KW_PROTEINS, 1),
            array(KW_UW, 1),
        )
    );
    make_project("BOINC Test Project",
        "https://boinc.berkeley.edu/test/",
        array(
            array(KW_MATH_CS, 1),
            array(KW_UCB, 1),
        )
    );
if (0) {
    // WCG is problematic because it doesn't use email addr for user ID
    make_project("World Community Grid",
        "https://www.worldcommunitygrid.org/",
        array(
            array(KW_BIOMED, .5),
            array(KW_EARTH_SCI, .7),
            array(KW_US, .5),
        ),
        "https://www.worldcommunitygrid.org/"
    );
}
    make_project("Herd",
        "https://herd.tacc.utexas.edu/",
        array(
            array(KW_BIOMED, .5),
            array(KW_PHYSICS, .5),
            array(KW_US, 1),
        )
    );
    make_project("nanoHUB",
        "https://boinc.nanohub.org",
        array(
            array(KW_NANOSCIENCE, 1),
            array(KW_US, 1),
        )
    );
}

// make projects based on master list
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
        // the following require invitation code
        //
        if ((int)$p->id == PROJ_COLLATZ) continue;
        if ((int)$p->id == PROJ_PRIMABOINCA) continue;
        if ((int)$p->id == PROJ_SRBASE) continue;
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
        make_project((string)$p->name, (string)$p->url, $keywords);
    }
}

clean();
make_projects();

?>
