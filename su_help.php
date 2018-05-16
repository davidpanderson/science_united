<?php
require_once("../inc/util.inc");
page_head(tra("Need help?"));
echo "
<ul>
<li>
";
echo tra("Problems installing or running BOINC: %1 get help here%2.",
    "<a href=http://boinc.berkeley.edu/wiki/BOINC_Help>",
    "</a>"
);
echo "
<p>
<li>
";
echo tra("Problems with %1: ask for help on the
        %2 %3 message boards.%4",
    PROJECT,
    "<a href=forum_index.php>",
    PROJECT,
    "</a>"
);
echo "
<p>
<li>
";
echo tra("To report a bug or request a feature in %1, create an issue on %2 the %3 Github repository%4 (you'll need a free Github account).",
    PROJECT,
    "<a href=https://github.com/davidpanderson/science_united>",
    PROJECT,
    "</a>"
);
echo "
</ul>
";
page_tail();
?>
