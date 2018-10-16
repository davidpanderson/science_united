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

// Make serialized version of projects list: projects.ser
// DO THIS WHENEVER THE BOINC PROJECT LIST CHANGES
// Run this in projects/scienceunited/html/ops
// Also run project_init.php

// first:
// wget https://boinc.berkeley.edu/project_list.php
// and put it in projects.xml

// read projects.xml and, for each project in our DB, make
// - a list of (keyword_id, frac)
// - a list of (platform, GPU type, is_vbox)
// and write these, serialized, to projects.ser

require_once("../inc/su_db.inc");

function get_avs($p) {
    $avs = array();
    foreach ($p->platforms->name as $x) {
        $y = explode("[", (string)$x);
        $z = new StdClass;
        $z->platform = $y[0];
        $z->gpu = "";
        $z->vbox = false;
        if (count($y) > 1) {
            if (strstr($y[1], "cuda")) {
                $z->gpu = "nvidia";
            }
            if (strstr($y[1], "nvidia")) {
                $z->gpu = "nvidia";
            }
            if (strstr($y[1], "amd")) {
                $z->gpu = "amd";
            }
            if (strstr($y[1], "ati")) {
                $z->gpu = "amd";
            }
            if (strstr($y[1], "intel_gpu")) {
                $z->gpu = "intel";
            }
            if (strstr($y[1], "vbox")) {
                $z->vbox = true;
            }
        }
        $avs[] = $z;
    }
    return array_unique($avs, SORT_REGULAR);
}

function get_kws($p) {
    $kws = array();
    $x = trim((string)$p->keywords);
    $y = explode(" ", $x);
    foreach ($y as $z) {
        $a = new StdClass;
        $w = explode(":", $z);
        if (count($w) > 1) {
            $a->keyword_id = $w[0];
            $a->fraction = $w[1];
        } else {
            $a->keyword_id = $w[0];
            $a->fraction = 1;
        }
        $kws[] = $a;
    }
    return $kws;
}

function main() {
    $x = simplexml_load_file("projects.xml");
    $y = array();
    foreach ($x->project as $p) {
        $x = new StdClass;
        $x->id = (int)$p->id;
        $sup = SUProject::lookup_id($x->id);
        if (!$sup) continue;
        $x->name = (string)$p->name;
        $x->avs = get_avs($p);
        $x->kws = get_kws($p);
        $y[$x->id] = $x;
        echo "processed $x->id: $x->name\n";
    }
    file_put_contents("../user/projects.ser", serialize($y));
}

main();

?>
