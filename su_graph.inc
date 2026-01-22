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
require_once("su_util.inc");

// show 1-month accounting graph a user
// if no recent work, explain

function show_user_graph($user, $what, $ndays) {
    $min_time = time() - $ndays*86400;
    $n = SUAccountingUser::count("user_id=$user->id and create_time>$min_time");
    if ($n>1) {
        $url = sprintf(
            'su_graph2.php?userid=%d&what=%s&ndays=%d',
            $user->id, $what, 365
        );
        $img_url = sprintf(
            'su_graph.php?type=user&id=%d&what=%s&ndays=%d&xsize=600&ysize=350',
            $user->id, $what, $ndays
        );
        echo sprintf('
            <p>%s<p>
            <a href=%s>
            <img class="img-responsive" src="%s">
            </a>
            ',
            tra("Past month (click to see more):"),
            $url,
            $img_url
        );
    } else {
        $m = SUAccountingUser::count("user_id=$user->id");
        if ($m == 0) {
            // no host activity so far
            //
            echo tra("No work reported so far.");
            if (is_logged_in_user($user)) {
                echo tra("%1Need help?%2", " <a href=su_help.php>", "</a>");
            }
        } else {
            if ($n == 0) {
                // last report was > $ndays days ago.
                //
                echo tra("No work reported in last %1 days.", $ndays);
                if (is_logged_in_user($user)) {
                    echo tra("%1Need help?%2", " <a href=su_help.php>", "</a>");
                }
            } else {
                // one report in last 30 days
                //
                echo tra("Not enough data yet");
            }
        }
    }
}

?>
