<?php
require_once("../inc/util.inc");
page_head("Weak authentication");
text_start();
echo <<<EOT
<p>
Science United accounts have an email address and password.
You enter these when attaching a BOINC client to Science United
for the first time.
Science United then sends the client
a long random string called an "authenticator".
This is used to authenticate the client in subsequent RPC requests
to Science United.
<p>
The authenticator is stored in a configuration file "acct_mgr_login.xml"
in the BOINC data directory
(on Windows, this is typically "C:/ProgramData/BOINC/").
<p>
The authenticator gives full access to the account.
You can use it to log into the account on the web site.
Then you can e.g. change the account's email address or password.
<p>
This scheme works fine for running Science United
on computers under your control.
But suppose you want to run Science United, under a particular account,
on computers controlled by random other people.
They'd be able to get the authenticator from the config file,
log in to the account, and do mischief.
<p>
To handle this case, Science United uses
a second type of authenticator, the "weak authenticator".
This lets the BOINC client authenticate itself to Science United,
but it can't be used to log in to the account.
<p>
To find the weak authenticator of your account,
log in to Science United and click on "Account".

<h2>Using the weak authenticator</h2>
<p>
To attach a BOINC client to a Science United account
using the weak authenticator:
<p>
<li>
If the client is already attached to Science United,
open "acct_mgr_login.xml" in a text editor.
Find the line that looks like
<p>
<pre>
&lt;authenticator>***&lt;/authenticator>
</pre>
<p>
and replace the authenticator (i.e. the *** part) with the weak authenticator.

<p>
<li> If the client is not already attached to Science United,
create these two files in the BOINC data directory:
<p>
<pre>
acct_mgr_url.xml:

&lt;acct_mgr>
    &lt;name>Science United&lt;/name>
    &lt;url>https://scienceunited.org/&lt;/url>
&lt;/acct_mgr>
</pre>
<pre>
acct_mgr_login.xml:

&lt;acct_mgr_login>
    &lt;authenticator>***&lt;/authenticator>
&lt;/acct_mgr_login>
</pre>
<p>
where *** is the weak authenticator.
<p>
When the BOINC client starts,
it will be attached to the Science United account.

<h2>Notes</h2>
<p>
The weak authenticator of an account is a function of
<ul>
<li> The account's email address.
<li> The account's password.
</ul>
<p>
Hence changing an account's email address or password will
change its weak authenticator.
Its old authenticator will no longer work,
and BOINC clients that have it will get
"authentication failed" error messages when they contact Science United.
<p>
This is a feature: it lets you invalidate weak authenticators.
But it means that if you use weak authenticators
you should pick your account's email address and password carefully.
EOT;
text_end();
page_tail();
?>
