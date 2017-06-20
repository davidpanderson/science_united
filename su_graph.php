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

// draw accounting graph with gnuplot
//
// URL args:
// type=user/project/total
// id=n
// what=jobs/time/ec
//      job: show success and fail job deltas
//      time: show CPU and GPU time deltas
//      ec: show CPU and GPU EC deltas
// ndays=n
// xsize=n,ysize=m

require_once("../inc/util.inc");
require_once("../inc/su.inc");

function graph($type, $id, $what, $ndays, $xsize, $ysize) {
    $min_time = time() - $ndays*86400;
    if ($type == 'user') {
        $accts = SUAccountingUser::enum("user_id=$id and create_time>$min_time");
    } else if ($type == 'project') {
        $accts = SUAccountingProject::enum("project_id=$id and create_time>$min_time");
    } else {
        $accts = SUAccounting::enum("create_time>$min_time");
    }

    // write data to temp file
    //
    $fn = tempnam("/tmp", "su_data");
    $f = fopen($fn, "w");
    foreach ($accts as $a) {
        if ($what == "job") {
            fprintf($f, "%f %d %d\n",
                $a->create_time, $a->njobs_success_delta, $a->njobs_fail_delta
            );
        } else if ($what = "time") {
            fprintf($f, "%f %f %f\n",
                $a->create_time, $a->cpu_time_delta, $a->gpu_time_delta
            );
        } else if ($what = "ec") {
            fprintf($f, "%f %f %f\n",
                $a->create_time, $a->cpu_ec_delta, $a->gpu_ec_delta
            );
        }
    }
    fclose($f);

    // write gnuplot file
    //
    $gn = tempnam("/tmp", "su_gp");
    $g = fopen($gn, 'c');
    fprintf($g,
        'set terminal png
        plot "%s" using 1:2 with linespoints title "CPU time", "%s" using 1:3 with linespoints title "GPU time"
        ',
        $fn, $fn
    );

    fclose($g);

    $cmd = "gnuplot $gn";
    header("Content-Type: image/png");
    passthru($cmd);

    unlink($gn);
    unlink($fn);
}

if (0) {
    $type = get_str('type');
    $id = get_int('id', true);
    $what = get_str('what');
    $ndays = get_int('ndays');
    $xsize = get_int('xsize');
    $ysize = get_int('ysize');
    graph($type, $id, $what, $ndays, $xsize, $ysize);
} else {
    graph('total', 0, 'job', 100, 800, 600);
}
?>
