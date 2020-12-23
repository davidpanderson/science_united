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

require_once("../inc/util.inc");
page_head(sprintf("%s %s", tra("About"), PROJECT));
text_start();
echo sprintf("
    <h2>Goals</h2>
    <p>
    <a href=https://boinc.berkeley.edu>BOINC</a> is the preeminent
    platform for volunteer computing (VC).
    It is used by most VC projects, including SETI@home, Einstein@home,
    Climateprediction.net, IBM World Community Grid, and Rosetta@home.
    <p>
    %s is a new way to particate in BOINC.
    With %s, computer owners volunteer for science areas
    rather than for specific projects.
    %s assigns computers to appropriate projects;
    these assignments may change over time.
    <p>
    We call this the 'coordinated model' for VC.
    It has the advantage that new projects can get computing power
    without having to do their own publicity and volunteer recruitment.
    The goal is to provide the power of VC to thousands of scientists,
    rather than a few dozen as was previously the case.
    <p>
    Learn more:
    <a href=doc/model.pdf>Coordinated Volunteer Computing</a>

    <p>
    The user interface of %s is designed to appeal to
    a wider audience than the current BOINC user base,
    which is mostly male and tech-savvy.
    For example, %s has no leader boards.
    <p>
    Learn more:
    <a href=doc/ui_goals.pdf>%s: UI/UX goals</a>

    <p>
    %s is also intended to serve as a unified 'brand' for VC,
    so that it can be marketed more effectively.

    <h2>How Science United allocates resources</h2>

    <p>
    Science United maintains a set of BOINC projects to
    which it assigns volunteer computers.
    For now, all <a href=https://boinc.berkeley.edu/projects.php>science projects
    vetted by BOINC</a> are included in this set,
    and they have equal allocations.
    Eventually the selection of science projects,
    and the allocation of computing power among them,
    will be determined by a committee of scientists.
    <p>
    If you are a scientist considering using BOINC,
    please <a href=https://boinc.berkeley.edu/anderson/>contact us</a>.
    It may be possible for us to guarantee inclusion of your project,
    and to provide an estimate of the computing power it will receive.

    <h2>The Project</h2>
    <p>
    Science United is operated at UC Berkeley under the direction of
    <a href=https://boinc.berkeley.edu/anderson/>David P. Anderson</a>,
    the founder of BOINC.
    The project was funded by the
    <a href=https://nsf.gov>National Science Foundation</a>
    under awards 1550601 and 1664190.

    <h2>Software</h2>
    <p>
    The Science United software is distributed under the LGPL v3 license.
    It is hosted on
    <a href=https://github.com/davidpanderson/science_united>Github</a>.
    <p>
    Learn more: <a href=doc/implementation.pdf>%s: Implementation</a>.



    <h2>Help wanted</h2>
    <p>
    You can help %s in the following ways:

    <ul>
    <li> Planning and design:
    this is done through Github issues.
    Feel free to create and/or comment on issues.
    <li> Programming:
    If you have the needed technical skills (PHP, MySQL, HTML, CSS, Bootstrap)
    you can help develop and debug %s.
    Go to Github and scan the issues.
    <li> Translation:
    much of the %s web site is translatable to non-English languages.
    These translations are done within the
    <a href=https://boinc.berkeley.edu/translate.php>BOINC translation system</a>
    </ul>
    ", PROJECT, PROJECT, PROJECT, PROJECT, PROJECT, PROJECT,
    PROJECT, PROJECT, PROJECT, PROJECT, PROJECT, PROJECT, PROJECT
);
text_end();
page_tail();
?>
