<?php
require_once("../inc/util.inc");

page_head("Intro to Science United for BOINC users");

echo <<<EOT
<p>
Science United (SU) is a new way for computer owners
to participate in BOINC-based volunteer computing.
SU doesn't replace the old way of doing things,
where people attach to specific;
rather, it provides an alternative.
<p>
SU is designed for people who are not technical,
who don't want to spend much time on BOINC
(e.g., fiddling with settings or browsing project web sites),
and who are motivated primarily by science goals.

<p>
The computing power of SU participants is divided among
a set of vetted projects.
The choice of projects and the allocation policy
will be made by a panel of independent scientists.

<h3>Science areas, not projects</h3>

<p>
Participants in SU sign up for science areas, not projects.
They can choose which areas and sub-areas they want to support,
and which they don't.
Similarly for the location of the research.

<p>
SU - which is implemented as an "account manager", like BAM! and GridRepublic -
tells their computer what projects to run,
based on their preferences and other factors.
This may change over time, even from day to day.
They may end up running a project that didn't exist when they first signed up.

This is preferable for time-limited users;
they don't have to browse and evaluate projects;
SU does that for them.

<p>
Equally importantly, it means that a new project,
if it is vetted by SU, is guaranteed to get a
(hopefully large) amount of computing power,
without doing any publicity or even developing a web site.
The need to do these things has been a major barrier
to entry for scientists who might want to use BOINC.

<h3>User-friendliness</h3>

<p>
SU's target user wants to install the software in a couple of minutes,
set it, and forget it.
They may or may not ever return to the SU web site.

<p>
There are few things that you might notice:

<ul>
<li> New users download BOINC by the new "auto-attach" mechanism
where a) the download happens directly, rather than going through the
BOINC web site, and b) when BOINC starts up, it is already attached to SU.

<li> There are no leader boards.
Credit isn't shown anywhere.
SU shows users graphs of their own work history,
but there are no comparisons with other users.

<li> We try to avoid technical terminology like CPU, GPU, and FLOPS.
Words like "computer" and "job" are OK.

<li> Computing preferences have been reduced to three options:
green, standard, and max.

<li> We avoid showing lots of information.
When people are shown too much information they
assume they should understand it all, and this stresses them.

<li> We show information graphically rather than textually where possible.
</ul>

<p>
By default SU sends users a weekly "status" email
whose purpose is to make the user feel good about their contribution,
and to alert them if any of their computers have stopped doing work.

<h3>Testing Science United</h3>
<p>
When you create an SU account with a given email and password,
it will try to create accounts on various BOINC projects
with the same credentials.
If these fail because there's already an account with that email
but different password,
you'll be notified on the SU web site
and you can enter the project-specific password.

<p>
So if you want to test SU with your standard BOINC email address,
you might want to use your standard password.

<p>
You're also encouraged to test the "new user" scenario,
where you use an email address that you haven't used
on any BOINC projects.

<p>
To test SU, you'll need version 7.9.1 or later of the BOINC client.
You can get this from the BOINC web site,
or better yet, download it from SU.

<p>
Give testing feedback (bugs, questions, feature requests)
on the SU message boards.
You can also email me (David Anderson) directly.
Remember that the target audience of SU is not you;
I'm not going to be adding "power user" features to SU.
Pretend you're interested in science but no very little about computers.
Would you understand the overall idea?
Would you be able to follow the instructions?
Would you follow through to the end?
EOT;

page_tail();
?>
