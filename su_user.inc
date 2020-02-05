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

// UI related to users

require_once("../inc/host.inc");
require_once("../inc/su_db.inc");

function latest_rpc_time($hosts) {
    $t = 0;
    foreach($hosts as $h) {
        if ($h->rpc_time > $t) {
            $t = $h->rpc_time;
        }
    }
    return $t;
}

define('WARNING', "<font color=red>&#9888;</font> ");
define('CURRENT_CLIENT_VERSION', '7.14.2');

// suggest download in various situations
// - if account is > 1 hr old and no host
//      download link, help link
// - if last RPC from any host is > 1 week
//      warn; suggest reinstall
// - if client version is too old on an active machine
//      suggest upgrade
//
function show_download($user) {
    if (time() - $user->create_time < 3600) {
        return;
    }
    $hosts = BoincHost::enum("userid=$user->id");
    if (count($hosts) == 0) {
        echo WARNING."It looks like BOINC isn't running on your computer.
            Try <a href=download.php?dev=1>re-installing it</a>.
            If you need help,
            <a href=https://boinc.berkeley.edu/help.php>go here</a>.
            <br>
        ";
        return;
    }
    $dt = time() - latest_rpc_time($hosts);
    if ($dt > 7*86400) {
        echo WARNING."None of your computers has contacted us in the last ".time_diff($dt, 0)." ago.
            Please check that BOINC is running.
            You may need to <a href=download.php?dev=1>re-install BOINC</a>.
            <br>
        ";
        return;
    }
    foreach ($hosts as $host) {
        if (time() - $host->rpc_time > 7*86400) {
            continue;
        }
        $cv = boinc_version($host->serialnum);
        if (version_compare($cv, CURRENT_CLIENT_VERSION) < 0) {
            echo WARNING."Your computer $host->domain_name is running an old version of BOINC.
                Please <a href=su_download_info.php>upgrade to the current version</a>.
                <br>
            ";
        }
    }
}

// show keywords of recent jobs
//
// TODO: need client/scheduler support for this.
// - jobs must include list of keywords (area/loc)
// - AM request includes list of keywords of jobs on client
//
function show_supported_keywords($user) {
    echo "
        <p>
        Recently, your computer has been
        doing work for projects doing
        (list of science keywords)
        located in (list of locations).
    ";
}

function show_stats(
    $cpu_ec, $cpu_time, $gpu_ec, $gpu_time, $njobs_success, $njobs_fail
){
    echo sprintf(
        "CPU: %.2f hours
        ",
        $cpu_time/3600
    );
    if ($gpu_time > 0) {
        echo sprintf(
            "<br>GPU: %.2f hours
            ",
            $gpu_time/3600
        );
    }
    echo sprintf(
        "<br>jobs: %d success, %d failure
        ",
        $njobs_success, $njobs_fail
    );
}

function show_last_day($user) {
    $au = SUAccountingUser::last($user->id);
    if (!$au) {
        return;
    }
    echo "<p>Last day<br>";
    show_stats(
        $au->cpu_ec_delta,
        $au->cpu_time_delta,
        $au->gpu_ec_delta,
        $au->gpu_time_delta,
        $au->njobs_success_delta,
        $au->njobs_fail_delta
    );
    echo "<p>Total<br>";
    show_stats(
        $au->cpu_ec_total,
        $au->cpu_time_total,
        $au->gpu_ec_total,
        $au->gpu_time_total,
        $au->njobs_success_total,
        $au->njobs_fail_total
    );
}

function show_calls_to_action() {
    echo sprintf('
        <h3>%s</h3>
        <ul>
        <li> %s
        <li> %s
        </ul>
        %s
        ',
        tra('Want to help more?'),
        tra('Tell friends and family about %1.', PROJECT),
        tra("Run BOINC on your other computing devices (desktops, laptops, tablets, Android phones)."),
        tra("Is BOINC running on this computer?  If not, %1Download it%2.",
            '<a href="download.php">',
            '<a/>'
        )
    );
    echo "<p>\n";
}

?>
