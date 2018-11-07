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

// utility functions not involving UI

// 24 GFLOP/hrs = 200 points
//

date_default_timezone_set("America/Los_Angeles");

function ec_to_flops($ec) {
    return $ec*86400.*1e9/200.;
}

function ec_to_gflop_hours($ec) {
    return .12*$ec;
}

function ec_to_gflops($ec) {
    return $ec*.12*3600.e9;
}

function ec_to_tflops($ec) {
    return $ec*.12*3600.e6;
}

function new_delta_set() {
    $x = new StdClass;
    $x->cpu_time = 0;
    $x->cpu_ec = 0;
    $x->gpu_time = 0;
    $x->gpu_ec = 0;
    $x->njobs_success = 0;
    $x->njobs_fail = 0;
    return $x;
}

function delta_set_nonzero($ds) {
    if ($ds->cpu_time) return true;
    if ($ds->cpu_ec) return true;
    if ($ds->gpu_time) return true;
    if ($ds->gpu_ec) return true;
    if ($ds->njobs_success) return true;
    if ($ds->njobs_fail) return true;
    return false;
}

function add_delta_set($x, $y) {
    $y->cpu_time += $x->cpu_time;
    $y->cpu_ec += $x->cpu_ec;
    $y->gpu_time += $x->gpu_time;
    $y->gpu_ec += $x->gpu_ec;
    $y->njobs_success += $x->njobs_success;
    $y->njobs_fail += $x->njobs_fail;
    return $y;
}

function add_record($x, $y) {
    $y->cpu_time += $x->cpu_time_delta;
    $y->cpu_ec += $x->cpu_ec_delta;
    $y->gpu_time += $x->gpu_time_delta;
    $y->gpu_ec += $x->gpu_ec_delta;
    $y->njobs_success += $x->njobs_success_delta;
    $y->njobs_fail += $x->njobs_fail_delta;
    return $y;
}

function log_write_deltas($ds) {
    log_write("   CPU time:        $ds->cpu_time");
    log_write("   CPU EC:          $ds->cpu_ec");
    log_write("   GPU time:        $ds->gpu_time");
    log_write("   GPU EC:          $ds->gpu_ec");
    log_write("   #jobs success:   $ds->njobs_success");
    log_write("   #jobs fail:      $ds->njobs_fail");
}

function delta_update_string($d) {
    return "cpu_ec_delta = cpu_ec_delta + $d->cpu_ec,
        gpu_ec_delta = gpu_ec_delta + $d->gpu_ec,
        cpu_time_delta = cpu_time_delta + $d->cpu_time,
        gpu_time_delta = gpu_time_delta + $d->gpu_time,
        njobs_success_delta = njobs_success_delta + $d->njobs_success,
        njobs_fail_delta = njobs_fail_delta + $d->njobs_fail
    ";
}

?>
