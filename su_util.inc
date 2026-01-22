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
    return ec_to_flops($ec)/1.e9;
}

function ec_to_tflops($ec) {
    return ec_to_flops($ec)/1.e12;
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

function total_update_string($d) {
    return "cpu_ec_total = $d->cpu_ec,
        gpu_ec_total = $d->gpu_ec,
        cpu_time_total = $d->cpu_time,
        gpu_time_total = $d->gpu_time,
        njobs_success_total = $d->njobs_success,
        njobs_fail_total = $d->njobs_fail
    ";
}

function su_weak_auth($user) {
    $x = md5($user->authenticator.$user->passwd_hash);
    return "{$user->id}_$x";
}

function is_logged_in_user($user) {
    $u = get_logged_in_user(false);
    return $u?($u->id == $user->id):false;
}

// input: a request message, parsed into a SimpleXML struct
// converts this to the structure used in host.misc,
// and returns the JSON for it
//
function make_hostinfo_json($req) {
    $x = new StdClass;
    $gpus = [];
    $config = [];
    $x->client_version = (string)$req->client_version;
    foreach ($req->host_info->coprocs->children() as $k => $c) {
        switch($k) {
        case 'coproc_cuda':
            $gpus[] = make_gpu($c, 'nvidia', (string)$c->cudaVersion);
            break;
        case 'coproc_ati':
            $gpus[] = make_gpu($c, 'amd', (string)$c->CALVersion);
            break;
        case 'coproc_intel_gpu':
            $gpus[] = make_gpu($c, 'intel');
            break;
        case 'coproc_apple_gpu':
            $gpus[] = make_gpu($c, 'apple');
            break;
        default:
            $gpus[] = make_gpu($c, 'opencl');
            break;
        }
    }

    $v = $req->host_info->virtualbox_version;
    if ($v) {
        $x->vbox = make_vbox($req);
    }

    [$version, $type, $d] = get_docker_info($req->host_info);
    if ($version) {
        $x->docker = make_docker($version, $type, $d);
    }

    if ($req->dont_use_docker) {
        $config['dont_use_docker'] = true;
    }
    if ($req->dont_use_wsl) {
        $config['dont_use_wsl'] = true;
    }

    if ($gpus) $x->gpus = $gpus;
    if ($config) $x->config = $gpus;

    return json_encode($x, JSON_PRETTY_PRINT);
}

function make_gpu($c, $type, $driver_version = null) {
    $x = new StdClass;
    $x->type = $type;
    $x->model = (string)$c->name;
    $x->count = (int)$c->count;
    $x->ram_mb = (float)$c->available_ram/MEGA;
    if ($driver_version) {
        $x->driver_version = $driver_version;
    }
    if (!empty($c->coproc_opencl->opencl_device_version)) {
        $x->opencl_version = (string)$c->coproc_opencl->opencl_device_version;
    }
    return $x;
}

function make_vbox($req) {
    $x = new StdClass;
    $x->version = (string)$req->host_info->virtualbox_version;
    $x->hw_accel = strstr($req->host_info->p_features, 'vmx')
        || strstr($req->host_info->p_features, 'svm')
    ;
    $x->hw_accel_enabled = !($req->host_info->p_vm_extensions_disabled);
    return $x;
}

function make_docker($version, $type, $d) {
    $x = new StdClass;
    $x->version = (string)$version;
    $x->type = (int)$type;
    if ($d) {
        $x->wsl_distro = $d;
    }
    return $x;
}

function get_docker_info($host_info) {
    if (strstr($host_info->os_name, 'Windows')) {
        foreach ($host_info->wsl->distro as $distro) {
            if ($distro->docker_version) {
                $d = new StdClass;
                $d->wsl_distro = (string)$distro->distro_name;
                if (!empty($distro->distro_version)) {
                    $d->boinc_buda_runner_version = (int)$distro->distro_version;
                }
                return [$distro->docker_version, $distro->docker_type, $d];
            }
        }
        return [null, null, null];
    } else {
        return [$host_info->docker_version, $host_info->docker_type, null];
    }
}

?>
