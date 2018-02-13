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

// SU home page

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
require_once("../inc/su_graph.inc");

define('CURRENT_CLIENT_VERSION', '7.6.0');

$stopped = web_stopped();
$user = get_logged_in_user(false);

// shown only when site is down
//
function closed_panel() {
    global $master_url;
    echo '
        <p class="lead text-center">'
        .tra("%1 is temporarily shut down for maintenance.", PROJECT)
        .'</p>
    ';
}

function user_summary($user) {
    show_download($user);
    show_problem_accounts($user);
    echo "<h3>Recent contribution</h3>\n";
    show_user_graph($user, "ec", 30);
    show_calls_to_action();
    echo '<center><a href=su_home.php class="btn btn-success">Continue to your home page</a></center>
    ';
}

function intro_panel() {
    panel(null,
        function() {
            echo sprintf('
                <p>
                <b>%s</b> lets you help scientific research projects
                by giving them computing power.
                These projects do research in astronomy, physics,
                biomedicine, mathematics, and environmental science;
                you can pick the areas you want to support.
                <p>
                You help by installing BOINC, a free program
                that runs scientific jobs in the background
                and when you\'re not using the computer.
                BOINC is secure and will not affect your normal use of the computer.
                <p>
                %s and the research projects it supports are non-profit.
                <br><br>
                ', PROJECT, PROJECT
            );
            echo '<center><a href="su_join.php" class="btn btn-success"><font size=+2>'.tra('Join %1', PROJECT).'</font></a></center>
            ';
            echo "
            <br><br>Already joined? <a href=login_form.php>Log in.</a>
            ";
        }
    );
}

function user_panel(){
    global $user, $master_url;
    panel(
        "Welcome, $user->name",
        function() use($user) {
            user_summary($user);
        },
        "panel-info"
    );
    $profile = get_current_uotd();
    if ($profile) {
        panel('User of the Day',
            function() use ($profile) {
                show_uotd($profile);
            }
        );
    }
}

function news_panel() {
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

function slide_show() {
    $pics = array(
        "virus.jpg"=>"Ebola-Infected VERO E6 Cell",
        "higgs.jpg"=>"Simulation of Higgs boson detection",
        "earth.jpg"=>"The Earth's interrelated climate systems",
        "protein.png"=>"Structure of protein GIF",
        "hubble.jpg"=>"The Hubble ultra-deep field image",
    );
    echo '<div class="carousel slide" data-ride="carousel">
        <div class="carousel-inner">
    ';
    
    $c = "item active";
    foreach ($pics as $pic=>$caption) {
        echo sprintf('
            <div class="%s">
            <img class="img-responsive" width=400 src="pictures/%s">
            <div class="carousel-caption">%s</div>
            </div>
            ', $c, $pic, $caption
        );
        $c = "item";
    }
    echo '</div></div>
    ';
}

page_head(PROJECT, null, true);

if ($stopped) {
    grid('closed_panel', function(){}, function(){});
} else {
    if ($user) {
        grid(null, 'user_panel', 'news_panel');
    } else {
        grid(null, 'intro_panel', 'slide_show');
    }
}

echo "
<p>
<table width=100%>
<tr>
<td width=50%></td>
<td valign=top>
    <nobr><img src=nsf1.jpg height=120> <img src=uc_logo.jpg height=120></nobr>
    <br><center><small>
        Science United is funded by the <a href=https://nsf.gov>National Science Foundation</a>, award #1664190, and is based at the <a href=https://berkeley.edu>University of California, Berkeley</a>.
    </small></center>
</td>
<td width=50%></td>
</tr></table>
";

page_tail(false, "", true);

?>
