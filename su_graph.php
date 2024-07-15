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
require_once("../inc/smooth.inc");

// graph last month of FLOPS of top N projects
//
function projects_graph($ndays, $gpu, $xsize, $ysize) {
    $t = time() - 86400;
    // get top projects
    //
    if ($gpu) {
        $projs = SUAccountingProject::enum("gpu_ec_delta>0 and create_time>$t order by gpu_ec_total desc limit 10");
    } else {
        $projs = SUAccountingProject::enum("create_time>$t order by cpu_ec_total desc limit 10");
    }

    // make data file per project
    //

    $t0 = time() - $ndays*86400;
    $projs2 = Array();
    foreach ($projs as $p) {
        $proj = SUProject::lookup_id($p->project_id);
        if ($proj->status != PROJECT_STATUS_AUTO) {
            continue;
        }
        $p->name = $proj->name;
        $projs2[] = $p;
        $accts = SUAccountingProject::enum("project_id = $p->project_id and create_time>$t0 order by create_time");
        $f = fopen("tmp/$gpu/$p->project_id", "w");

        $m = count($accts);
        for ($i=0; $i<$m-11; $i++) {
            $s = 0;
            for ($j=$i; $j<$i+10; $j++) {
                $a = $accts[$j];
                $s += $gpu?$a->gpu_ec_delta:$a->cpu_ec_delta;
            }
            $x = ec_to_flops($s/10.);
            $tf = $x/1e12;
            $tf /= 86400.;
            fprintf($f, "%d %f\n", $a->create_time, $tf);
        }
        fclose($f);
    }
    $g = fopen("tmp/$gpu/gp", 'w');
    fprintf($g,
        'set terminal pngcairo size %s,%s
        set xdata time
        set timefmt "%%s"
        set format x "%%d %%b"
        set style fill solid 1.0
        set boxwidth 70000
        set yrange [0:]
        set xtics %d
        set xtics rotate
        set ylabel "%s"
        plot ',
        $xsize, $ysize,
        $ndays*86400./8,
        $gpu?"GPU TeraFLOPS":"CPU TeraFLOPS"
    );
    $plast = end($projs2);
    foreach ($projs2 as $p) {
        fprintf($g,
            '"tmp/%d/%d" using 1:2 with linespoints title "%s"',
            $gpu, $p->project_id, str_replace("@", "\\\\@", $p->name)
        );
        if ($p != $plast) {
            fprintf($g, ", ");
        }
    }
    fprintf($g, "\n");
    fclose($g);
    $cmd = "gnuplot tmp/$gpu/gp";
    header("Content-Type: image/png");
    passthru($cmd);
}

function graph($type, $id, $what, $ndays, $xsize, $ysize, $integrate) {
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
        if ($integrate) {
            $title2 = tra("Successful jobs");
            $title1 = tra("Failed jobs");
        } else {
            $title2 = tra("Successful jobs/day");
            $title1 = tra("Failed jobs/day");
        }
        $color2 = "green";
        $color1 = "red";
        $graph_two = true;
        $graph_type = 'boxes';
        $success_smooth = new SMOOTHER();
        $fail_smooth = new SMOOTHER();
        break;
    case 'time':
        if ($integrate) {
            $title1 = tra("CPU hours");
            $title2 = tra("GPU hours");
        } else {
            $title1 = tra("CPU hours/day");
            $title2 = tra("GPU hours/day");
        }
        $color1 = "khaki";
        $color2 = "orange";
        $graph_two = false;
        $graph_type = 'filledcurve x1';
        $cpu_smooth = new SMOOTHER();
        $gpu_smooth = new SMOOTHER();
        break;
    case 'ec':
        if ($integrate) {
            $title1 = "CPU TFLOPs";
            $title2 = "GPU TFLOPs";
        } else {
            $title1 = "CPU TFLOPs/sec";
            $title2 = "GPU TFLOPs/sec";
        }
        $color1 = "khaki";
        $color2 = "orange";
        $graph_two = false;
        $graph_type = 'filledcurve x1';
        $graph_type = 'boxes';
        $cpu_smooth = new SMOOTHER();
        $gpu_smooth = new SMOOTHER();
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

            //continue;
            $end_time = time();
        } else {
            $end_time = $accts[$i+1]->create_time;
        }
        $dt = $end_time - $a->create_time;
        if ($dt<86400) $dt=86400;
        switch ($what) {
        case 'jobs':
            if ($integrate) {
                fprintf($f, "%f %f %f\n",
                    $a->create_time,
                    $a->njobs_fail_total,
                    $a->njobs_success_total
                );
            } else {
                $dt /= 86400;
                $ss = $success_smooth->val($a->njobs_success_delta);
                $fs = $fail_smooth->val($a->njobs_fail_delta);
                fprintf($f, "%f %f %f\n",
                    $a->create_time,
                    $fs, $ss
                );
            }
            break;
        case 'time':
            if ($integrate) {
                fprintf($f, "%f %f %f\n",
                    $a->create_time,
                    $a->cpu_time_total,
                    $a->cpu_time_total+$a->gpu_time_total
                );
            } else {
                $dt /= 24;
                $cs = $cpu_smooth->val($a->cpu_time_delta);
                $gs = $gpu_smooth->val($a->gpu_time_delta);
                fprintf($f, "%f %f %f\n",
                    $a->create_time,
                    $cs/$dt,
                    ($cs+$gs)/$dt
                );
            }
            if ($a->gpu_time_delta) {
                $graph_two = true;
            }
            break;
        case 'ec':
            if ($integrate) {
                fprintf($f, "%f %f %f\n",
                    $a->create_time,
                    $a->cpu_ec_total, $a->gpu_ec_total
                );
            } else {
                $cs = $cpu_smooth->val($a->cpu_ec_delta);
                $gs = $gpu_smooth->val($a->gpu_ec_delta);
                $c = ec_to_gflop_hours($cs);
                $g = ec_to_gflop_hours($gs);
                $dt /= 3600;
                fprintf($f, "%f %f %f\n",
                    $a->create_time,
                    $c/($dt*1000),
                    ($c+$g)/($dt*1000)
                );
            }
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
    $g = fopen($gn, 'w');
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
    $xtics = 86400.*$ndays/6;
    fprintf($g,
        'set terminal png size %s,%s
        set xdata time
        set timefmt "%%s"
        set format x "%%d %%b %%y"
        set style fill solid 1.0
        set boxwidth 70000
        set yrange [0:]
        set xtics %d
        set xtics rotate
        %s
        ',
        $xsize, $ysize, $xtics, $plot
    );

    fclose($g);


    $cmd = "gnuplot $gn";
    header("Content-Type: image/png");
    passthru($cmd);

    unlink($gn);
    unlink($fn);
}

if (1) {
    $xsize = get_int('xsize');
    $ysize = get_int('ysize');
    $ndays = get_int('ndays');
    $type = get_str('type', true);
    if ($type == 'projects') {
        projects_graph($ndays, get_int('gpu'), $xsize, $ysize);
        exit;
    }
    $id = get_int('id', true);
    $integrate = get_int('integrate', true);
    $what = get_str('what');
    graph($type, $id, $what, $ndays, $xsize, $ysize, $integrate);
} else {
    graph('total', 22203, 'ec', 1000, 800, 600, false);
    //graph('user', 22203, 'ec', 30, 800, 600);
}#
?>
