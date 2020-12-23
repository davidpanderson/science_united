<?php
require_once("../inc/util.inc");
page_head("Privacy policy");
text_start();
echo "
<h3>What data we collect</h3>
<p>
When you join and participate in Science United,
we collect and store two types of data:
<ul>
<li>
<b>Account information</b>: your email address and password,
your science-area preferences, your computing preferences,
and your message-board posts.
You supply these items when you create an account
and when you interact with the web site.
<li>
<b>Computer information</b>: data about the computers that run BOINC
using your Science United account.
This includes descriptions of the hardware (CPU type, memory and disk size. etc.)
and software (OS type and version).
This information is reported to Science United by the BOINC client.
</ul>

<p>
<h3>How we use this data</h3>
<p>
We use your account information to keep track
of the computing done by your computers,
and to let you log in to your Science United account
and view this information.
<p>
We use your email address to send you periodic emails
describing the amount of computing your computers have done.
You can control the frequence of these emails
or opt out of them altogether.
<p>
We use your computer information to determine what
BOINC projects are able to use your computers.

<h3>What data we disclose to third parties</h3>
<p>
We don't disclose your account or computer information to third parties.

<h3>Your access to and control over data</h3>
<p>
You can view and modify your account information
using the Science United web site.
If you wish, you can delete your account.
In this case, your account information will be deleted from our server.
<p>
You can view your computer information
using the Science United web site.
Information about your computers is visible only to you.
<p>
<h3>Contact</h3>
<p>
Contact <a href=https://boinc.berkeley.edu/anderson/>David P. Anderson</a>.
";

text_end();
page_tail();
?>
