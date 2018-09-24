<?php
require_once("../inc/util.inc");

page_head("Intro to Science United for BOINC users");

text_start();
echo <<<EOT
<p>
Science United (SU) is a new way for computer owners
to participate in BOINC-based volunteer computing.
SU doesn't replace the old way of doing things,
where people attach to specific projects;
rather, it provides an alternative.
<p>
SU is designed for people who are not technical,
who don't want to spend much time on BOINC
(e.g., fiddling with settings or browsing project web sites),
and who are motivated primarily by science goals.

<p>
The computing power of SU participants is divided among
a set of vetted projects.
Eventually, the choice of projects and the allocation policy will be made by
an independent committee.
For now, all science projects vetted by BOINC are included,
with equal allocation shares.

<h3>Science areas, not projects</h3>

<p>
When you participate in SU, you sign up for science areas, not projects.
You can choose the areas and sub-areas you want to support,
and the ones you don't.
Similarly for the location of the research.

<p>
SU is implemented as an "account manager", like BAM! and GridRepublic.
When you attach a computer to SU,
it tells your computer what projects to run,
based on your preferences and other factors.
These may change over time, even from day to day.
You may end up running a project that didn't exist when you first signed up.
You don't have to browse and evaluate projects;
in effect, SU does that for you.

<p>
Equally importantly, it means that a new project,
if vetted by SU, can be assured of
a certain amount of computing power,
without doing any publicity or even developing a web site.
The need to do these things, and the risk of investing in BOINC,
have been major barriers to entry for scientists who might otherwise use BOINC.
The hope is that SU will lead to the creation of many more projects.

<h3>User-friendliness</h3>

<p>
In my conversations with "average" computer owners
(e.g. friends and family) about the BOINC user interface,
several themes emerge:
<ul>
<li> It's too complex.
<li> It presents too much information, and especially textual information.
When people are shown information they
assume they need to understand it all.
<li> It emphasizes competition (e.g. leaderboards).
Some volunteers are intimidated and put off by this.
</ul>
SU tries to remedy these problems.
In looking at the SU site, there are few things that you might notice:

<ul>
<li> New users download BOINC by the new "auto-attach" mechanism
where a) the download happens directly, rather than going through the
BOINC web site, and b) when BOINC starts up, it is already attached to SU.

<li> There are no leader boards.
Credit isn't shown anywhere.
Users see graphs of their own work history,
but no comparisons with other users.

<li> We try to minimize the use of technical terms like FLOPS.

<li> Computing preferences have been reduced to three options:
green, standard, and max.

<li> We avoid showing lots of information.

<li> We show information graphically rather than textually where possible.

<li> We de-emphasize the notion of project.
Users can see what projects they're contributing to,
but only by drilling down a bit.
</ul>

<p>
SU's target user wants to install the software in a couple of minutes,
set it, and forget it.
They may or may not ever return to the SU web site.
<p>
By default, SU sends users a weekly "status" email
whose purpose is to make the user feel good about their contribution,
and to alert them if any of their computers have stopped doing work.

<h3>Testing Science United</h3>
<p>
You create an SU account with an email and password.
These are used only for SU.
Project accounts will be created with anonymous credentials,
even if you already have accounts there.

<p>
To test SU, you'll need version 7.9.1 or later of the BOINC client.
You can get this from the BOINC web site,
or better yet, download it from SU.

<p>
Give testing feedback (bugs, questions, feature requests)
on the SU message boards.
You can also email me (David Anderson) directly.
Remember that the target audience of SU is not you;
we're not going to add "power user" features to SU.
Pretend you're interested in science but know little about computers.
Would you understand the overall idea?
Would you be able to follow the instructions?
Would you follow through to the end?

<h3>More information</h3>
<p>
Two more detailed documents are available:
<p>
<a href=doc/model.pdf>Coordinated Volunteer Computing</a>
describes why Science United is needed.
<p>
<a href=doc/implementation.pdf>Science United: Implementation</a>
explains how things work under the hood.

EOT;
text_end();

page_tail();
?>
