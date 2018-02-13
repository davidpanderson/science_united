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
// what:
//      jobs: show success and fail job deltas
//      time: show CPU and GPU time deltas
//      ec: show CPU and GPU EC deltas
//      users: show # active users and hosts
// xsize=n,ysize=m

require_once("../inc/util.inc");
require_once("../inc/su.inc");

function graph($type, $id, $what, $ndays, $xsize, $ysize) {
    $min_time = time() - $ndays*86400;
    $accts = null;
    switch ($type) {
    case 'user':
        $accts = SUAccountingUser::enum("user_id=$id and create_time>$min_time order by id");
        break;
    case 'project':
        $accts = SUAccountingProject::enum("project_id=$id and create_time>$min_time order by id");
        break;
    case 'total':
        $accts = SUAccounting::enum("create_time>$min_time order by id");
        break;
    }

    if (!$accts) {
        echo "No data";
        exit;
    }

    switch ($what) {
    case 'jobs':
        $title1 = "Successful jobs/day";
        $title2 = "Failed jobs/day";
        $color1 = "green";
        $color2 = "red";
        $graph_two = true;
        $graph_type = 'lines';
        break;
    case 'time':
        $title1 = "CPU hours/day";
        $title2 = "GPU hours/day";
        $color1 = "khaki";
        $color2 = "orange";
        $graph_two = false;
        $graph_type = 'filledcurve x1';
        break;
    case 'ec':
        $title1 = "CPU GFLOPS";
        $title2 = "GPU GFLOPS";
        $color1 = "khaki";
        $color2 = "orange";
        $graph_two = false;
        $graph_type = 'filledcurve x1';
        break;
    case 'users':
        $title1 = "Active users";
        $title2 = "Active computers";
        $color1 = "green";
        $color2 = "blue";
        $graph_two = true;
        $graph_type = 'lines';
        break;
    }

    // write data to temp file
    //
    $fn = tempnam("/tmp", "su_data");
    $f = fopen($fn, "w");
    $n = count($accts);

    for ($i=0; $i<$n; $i++) {
        $a = $accts[$i];
        if ($i == $n-1) {
            // don't show the last segment because computers
            // may not have done RPCs during it,
            // so it will generally show a downturn
            //

            continue;
        }
        $end_time = $accts[$i+1]->create_time;
        $dt = $end_time - $a->create_time;
        switch ($what) {
        case 'jobs':
            $dt /= 86400;
            fprintf($f, "%f %f %f\n",
                $a->create_time,
                $a->njobs_success_delta/$dt,
                $a->njobs_fail_delta/$dt
            );
            break;
        case 'time':
            $dt /= 24;
            fprintf($f, "%f %f %f\n",
                $a->create_time,
                $a->cpu_time_delta/$dt,
                ($a->cpu_time_delta+$a->gpu_time_delta)/$dt
            );
            if ($a->gpu_time_delta) {
                $graph_two = true;
            }
            break;
        case 'ec':
            $c = ec_to_gflop_hours($a->cpu_ec_delta);
            $g = ec_to_gflop_hours($a->gpu_ec_delta);
            $dt /= 3600;
            fprintf($f, "%f %f %f\n",
                $a->create_time,
                $c/($dt),
                ($c+$g)/($dt)
            );
            if ($a->gpu_ec_delta) {
                $graph_two = true;
            }
            break;
        case 'users':
            fprintf($f, "%f %d %d\n",
                $a->create_time,
                $a->nactive_users,
                $a->nactive_hosts
            );
            break;
        }
    }
    fclose($f);

    // write gnuplot file
    //
    $gn = tempnam("/tmp", "su_gp");
    $g = fopen($gn, 'c');
    if ($graph_two) {
        $plot = sprintf('
set style fill noborder
plot "%s" using 1:3 with %s title "%s" lc rgb "%s", \
"%s" using 1:2 with %s title "%s" lc rgb "%s"
',
            $fn, $graph_type, $title2, $color2,
            $fn, $graph_type, $title1, $color1
        );
    } else {
        $plot = sprintf(
            'plot "%s" using 1:2 with %s title "%s" lc rgb"%s"',
            $fn, $graph_type, $title1, $color1
        );
    }
    fprintf($g,
        'set terminal png size %s,%s
        set xdata time
        set timefmt "%%s"
        set format x "%%d %%b"
        set yrange [0:]
        set xtics 604800
        %s
        ',
        $xsize, $ysize, $plot
    );

    fclose($g);


    $cmd = "gnuplot $gn";
    header("Content-Type: image/png");
    passthru($cmd);

    unlink($gn);
    unlink($fn);
}

if (1) {
    $type = get_str('type');
    $id = get_int('id', true);
    $what = get_str('what');
    $ndays = get_int('ndays');
    $xsize = get_int('xsize');
    $ysize = get_int('ysize');
    graph($type, $id, $what, $ndays, $xsize, $ysize);
} else {
    //graph('total', 0, 'job', 100, 800, 600);
    graph('user', 22192, 'ec', 30, 800, 600);
}
?>
