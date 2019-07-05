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

require_once("su_db.inc");

function show_user_graph($user, $what, $ndays) {
    $min_time = time() - $ndays*86400;
    $n = SUAccountingUser::count("user_id=$user->id and create_time>$min_time");
    if ($n>1) {
        echo sprintf('
            <p>
            %s
            <p>
            <img class="img-responsive" src="su_graph.php?type=user&id=%d&what=%s&ndays=%d&xsize=450&ysize=250">
        ',
        tra("Past month:"), $user->id, $what, $ndays
        );
    } else {
        $m = SUAccountingUser::count("user_id=$user->id");
        if ($m == 0) {
            // no host activity so far
            //
            echo tra("No work reported so far. %1Need help?%2",
                "<a href=su_help.php>", "</a>"
            );
        } else {
            if ($n == 0) {
                // last report was > 30 days ago.
                //
                echo tra("No work reported in last %1 days. %2Need help?%3",
                    $ndays,
                    "<a href=su_help.php>", "</a>"
                );
            } else {
                // one report in last 30 days
                //
                echo tra("Not enough data yet");
            }
        }
    }
}

?>
