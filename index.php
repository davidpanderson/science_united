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

// This is a template for your web site's front page.
// You are encouraged to customize this file,
// and to create a graphical identity for your web site.
// by customizing the header/footer functions in html/project/project.inc
// and picking a Bootstrap theme
//
// If you add text, put it in tra() to make it translatable.

require_once("../inc/db.inc");
require_once("../inc/util.inc");
require_once("../inc/news.inc");
require_once("../inc/cache.inc");
require_once("../inc/uotd.inc");
require_once("../inc/sanitize_html.inc");
require_once("../inc/text_transform.inc");
require_once("../project/project.inc");
require_once("../inc/bootstrap.inc");
require_once("../inc/su_user.inc");

define('CURRENT_CLIENT_VERSION', '7.6.0');

$stopped = web_stopped();
$user = get_logged_in_user(false);

// top - shown only when site is down
//
function top() {
    global $stopped, $master_url, $user;
    if ($stopped) {
        echo '
            <p class="lead text-center">'
            .tra("%1 is temporarily shut down for maintenance.", PROJECT)
            .'</p>
        ';
    }
}

function user_summary($user) {
    show_download($user);
    show_problem_accounts($user);
    //show_supported_keywords($user);
    echo "<h3>Your contributions</h3>\n";
    show_last_month($user);
    show_last_day($user);
    show_calls_to_action();
}

function left(){
    global $user, $master_url;
    $title = $user?"Welcome back, $user->name": tra("Join", PROJECT);
    panel(
        $title,
        function() use($user) {
            if ($user) {
                user_summary($user);
            } else {
                echo sprintf('
                    <p>
                    %s lets you join scientific research projects
                    by giving them computing power.
                    These projects do research in astronomy, physics,
                    biomedicine, mathematics, and environmental science;
                    you can pick the areas you want to support.
                    <p>
                    You help by installing a free program on your computer,
                    which runs scientific jobs in the background
                    and when you\'re not at the computer.
                    This program is secure and will not
                    affect your normal use of the computer.
                    </p>
                    ', PROJECT
                );
                echo '<center><a href="su_join.php" class="btn btn-success"><font size=+2>'.tra('Get %1', PROJECT_WHITE).'</font></a><br><br>Already joined? <a href=login_form.php>Log in.</a></center>
                ';
            }
        },
        "panel-info"
    );
    global $stopped;
    if (!$stopped) {
        $profile = get_current_uotd();
        if ($profile) {
            panel('User of the Day',
                function() use ($profile) {
                    show_uotd($profile);
                }
            );
        }
    }
}

function right() {
    panel(tra('News'),
        function() {
            include("motd.php");
            if (!web_stopped()) {
                show_news(0, 5);
            }
        },
        "panel-info"
    );
}

page_head("Onboard", null, true);

grid('top', 'left', 'right');

page_tail(false, "", true);

?>
