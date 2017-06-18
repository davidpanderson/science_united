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

$dir = getcwd();
chdir('/mydisks/a/users/boincadm/projects/test2/html/user');
require_once("../inc/db.inc");
require_once("../inc/util.inc");
require_once("../inc/news.inc");
require_once("../inc/cache.inc");
require_once("../inc/uotd.inc");
require_once("../inc/sanitize_html.inc");
require_once("../inc/text_transform.inc");
require_once("../project/project.inc");
require_once("../inc/bootstrap.inc");
chdir($dir);

$config = get_config();
 
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

function left(){
    global $user, $master_url;
    panel(
        tra("What is %1?", PROJECT),
        function() use($user) {
            if ($user) {
                echo sprintf('
                    Welcome back, %s.
                    Recently, your computer has been
                    doing work for projects doing
                    (list of science keywords)
                    located in (list of locations).
                    <p>
                    graph of computing over last month or so
                    (FLOPS, CPU time, GPU time).
                    <p>
                    amount of CPU time, # jobs etc.
                    contributed in last 24 hours
                    <p>
                    other stuff in su_user.php
                ', $user->name
                );
            } else {
            echo "
                <p>
                Science United lets you help scientific research projects
                by giving them computing power.
                These projects do astronomy, physics, biomedicine,
                and environmental research;
                you can pick which of these you want to support.
                <p>
                You help by running a free program on your computer,
                which runs scientific jobs in the background
                and when you're not at the computer.
                This program is secure and will not
                affect your normal use of the computer.

                </p>
            ";
                echo '<center><a href="join.php" class="btn btn-success"><font size=+2>'.tra('Join %1', PROJECT).'</font></a><br><br>Already joined? <a href=login_form.php>Log in.</a></center>
                ';
            }
        }
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
        }
    );
}

page_head(null, null, true);

grid('top', 'left', 'right');

page_tail(false, "", true);

?>
