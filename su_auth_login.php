<?php

require_once("../inc/util.inc");

page_head("Logging in to Science United using your authenticator");
text_start();
echo "
<p>
Science United (for reasons we don't understand)
can't send emails to some addresses.
So the usual way of resetting your password doesn't work.
<p>
Instead, you can use your 'authenticator' to
log in to your account, and then change your password.
<ul>
<li> Go to the
<a href=https://boinc.berkeley.edu/wiki/BOINC_Data_directory>BOINC data directory</a>
on your computer.
<li>
Find the file 'acct_mgr_login.xml'
and open it using Notepad or another text editor.
It will look like
<p>
<pre>
&lt;acct_mgr_login>
    &lt;authenticator>e7cc870ab4597f5a2e7f627566b22d83&lt;/authenticator>
    ...
</pre>
<p>
Select the authenticator
(the long random string between &gt; and &lt;) and use Ctrl-C to copy it.
<li>
Go to the
<a href=https://scienceunited.org/login_form.php>login page</a>.
Enter the email address of your Science United account.
Use Ctrl-V to paste the authenticator into the Password field.
</ul>
You'll now be logged in.
Go to Settings / Account and choose a new password.
";
text_end();
page_tail();
?>
