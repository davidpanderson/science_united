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

// SU home page; also can be used for AMs based on SU source code

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

//$stopped = web_stopped();
$stopped = false;
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
    echo "<h3>".tra("Your recent contribution")."</h3>\n";
    show_user_graph($user, "ec", 30);
    show_calls_to_action();
    echo sprintf('<center><a href=%ssu_home.php style="background-color:seagreen; color:white; font-size:18px" class="btn btn-success">%s</a></center>
',
        URL_BASE,
        tra('Continue to your home page')
    );
}

function intro_panel() {
    panel(null,
        function() {
            echo "<p>";
            echo tra("%1 lets you help scientific research projects by giving them computing power.  These projects do research in astronomy, physics, biomedicine, mathematics, and environmental science; you can pick the areas you want to support.", "<b>".PROJECT."</b>");
            echo "<p>";
            echo tra("You help by installing BOINC, a free program that runs scientific jobs in the background and when you're not using the computer.  BOINC is secure and will not affect your normal use of the computer.");
            echo "<p>";
            if (PROJECT == 'Science United') {
                echo tra(
                    "%1 is operated by the BOINC project at UC Berkeley.",
                    PROJECT
                );
                echo " ";
                echo tra(
                    "%1 and the research projects it supports are non-profit.",
                    PROJECT
                );
            }
            echo "<br><br>\n";
            echo sprintf('<center><a href="%ssu_join.php" class="btn btn-success"><font size=+2>%s</font></a></center>
                ',
                URL_BASE,
                tra('Join %1', PROJECT)
            );
            echo sprintf('<br><br>%s <a href=%slogin_form.php>%s</a>',
                tra("Already joined?"),
                URL_BASE,
                tra("Log in.")
            );
            if (PROJECT == 'Science United') {
                echo "<p><p>BOINC user? <a href=intro.php>Read this</a>.\n";
            }
        }
    );
}

function user_panel(){
    global $user, $master_url;
    panel(
        tra("Welcome, %1", $user->name),
        function() use($user) {
            user_summary($user);
        },
        "panel-primary"
    );
    if (!DISABLE_PROFILES) {
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

function news_panel() {
    panel(tra('News'),
        function() {
            include("motd.php");
            if (!web_stopped()) {
                show_news(0, 5);
            }
        },
        "panel-primary"
    );
}

function slide_show() {
    $pics = array(
        "virus.jpg"=>tra("Ebola-Infected VERO E6 Cell"),
        "higgs.jpg"=>tra("Simulation of Higgs boson detection"),
        "earth.jpg"=>tra("The Earth's interrelated climate systems"),
        "protein.png"=>tra("Structure of protein GIF"),
        "hubble.jpg"=>tra("The Hubble ultra-deep field image"),
    );
    echo '<div class="carousel slide" data-interval="4000" data-ride="carousel">
        <div class="carousel-inner">
    ';
    
    $c = "item active";
    foreach ($pics as $pic=>$caption) {
        echo sprintf('
            <div class="%s">
            <img class="d-block img-fluid" width=400 src="pictures/%s">
            <div class="carousel-caption">%s</div>
            </div>
            ', $c, $pic, $caption
        );
        $c = "item";
    }
    echo '</div></div>
    ';
}

page_head(PROJECT, null, true, '',
    '<meta name=description content="Science United lets you supply computing power to science research projects in a wide range of areas">
    <meta name=keywords content="distributed scientific computing grid BOINC volunteer computing">
    '
);

if ($stopped) {
    grid('closed_panel', function(){}, function(){});
} else {
    if ($user) {
        grid(null, 'user_panel', 'news_panel');
    } else {
        grid(null, 'intro_panel', 'slide_show');
    }
}

if (PROJECT == 'Science United') {
    echo "
    <p>
    <table width=100%>
    <tr>
    <td width=30%></td>
    <td valign=top>
        <center>
        <nobr><img src=pictures/NSF_4-Color_bitmap_Logo.png height=120> &nbsp; &nbsp; &nbsp; <img src=ucbseal.png height=100></nobr>
        <br><small>
    ";
    echo tra("Science United is funded by the %1National Science Foundation%2, award #1664190, and is based at the %3University of California, Berkeley%4.",
            "<a href=https://nsf.gov>", "</a>",
            "<a href=https://berkeley.edu>", "</a>"
    );
    echo "
        Image credits: CERN and NIAID.
        </small></center>
    </td>
    <td width=30%></td>
    </tr></table>
    ";
}

page_tail(false, "", true);

?>
