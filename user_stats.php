<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2019 University of California
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

// print usage/accounting info

require_once("../inc/su_db.inc");
require_once("../inc/su_util.inc");

function main() {
    $nactive = 0;
    $active_dur = 0;
    $ninactive = 0;
    $inactive_dur = 0;
    $nnever = 0;
    $users = BoincUser::enum("");
    $now = time();
    $total_ec = 0;
    foreach ($users as $u) {
        $sah = SUAccountingUser::last($u->id);
        if ($sah) {
            $total_ec += $sah->cpu_ec_total+$sah->gpu_ec_total;
        }
        $hosts = BoincHost::enum("userid = $u->id");
        if (count($hosts) == 0) {
            $nnever++;
            continue;
        }
        $last = 0;
        foreach ($hosts as $h) {
            if ($h->rpc_time > $last) {
                $last = $h->rpc_time;
            }
        }
        if ($last > $now-7*86400) {
            $nactive++;
            $active_dur += ($now - $u->create_time);
        } else {
            $ninactive++;
            $inactive_dur += ($last - $u->create_time);
        }
    }
    echo "active: $nactive\n";
    echo "dur: ", ($active_dur/$nactive)/86400, "\n";
    echo "inactive: $ninactive\n";
    echo "dur: ", ($inactive_dur/$ninactive)/86400, "\n";
    $flops = ec_to_gflop_hours($total_ec);
    echo "gflop hours: $flops\n";
}

main();

?>
