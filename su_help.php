<?php
require_once("../inc/util.inc");
page_head(tra("Need help?"));
echo sprintf("
<ul>
<li>
Problems installing or running BOINC:
<a href=http://boinc.berkeley.edu/wiki/BOINC_Help>get help here</a>.
<p>
<li>
Problems with %s: ask for help
on the <a href=forum_index.php>%s message boards</a>.

<p>
<li>
To report a bug or request a feature in %s,
create an issue on <a href=https://github.com/davidpanderson/science_united>the %s Github repository</a>
(you'll need a free Github account).
</ul>",
    PROJECT, PROJECT, PROJECT, PROJECT
);
page_tail();
?>
