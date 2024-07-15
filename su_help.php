<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2019 University of California
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
page_head(tra("Help"));
text_start();
echo sprintf("<p>\n%s",
    tra("If you received a 'no work reported' email from Science United or are having other problems, please do the following:")
);
echo sprintf('<h3>%s</h3>',
    tra("Make sure BOINC is installed on your computer")
);
echo sprintf('
    <p>
    <ul>
    <li> On desktop systems:
    %s
    <li> On headless Linux systems: run
    <pre>ps -x | grep boinc</pre>
    and look for a "boinc-client" process
    </ul>',
    tra("Look for the BOINC icon %1 in your system tray or dock.",
        "<img height=24 src=https://boinc.berkeley.edu/logo/boinc32.bmp>"
    )
);
echo sprintf('
    <p>
    %s
    ',
    tra("If BOINC is not running, %1 download and install BOINC%2.",
        "<a href=download_software.php>", "</a>"
    ),
    tra("Science United requires BOINC version 7.16 or later.")
);
echo sprintf("<h3>%s</h3>",
    tra("Make sure BOINC is attached to Science United")
);
echo sprintf("
    <p>
    <ul>
    <li> On desktop systems:
        <ul>
            <li> %s
            <li> %s
            <li> %s
        </ul>
        <br><img width=400 src=pictures/su_menu.jpg>
    ",
    tra("Double-click the BOINC icon to open the BOINC Manager."),
    tra("Switch to the Advanced View (View / Advanced View)."),
    tra("Click on the Tools menu.  You should see 'Synchronize with Science United'.  If not, select 'Use account manager' and attach BOINC to your Science United account.")
);
echo sprintf("
    <p>
    <li> On headless Linux systems:
        <ul>
        <li> Run 
            <pre>boinccmd --acct_mgr info</pre>
            You should see
<pre>
Account manager info:
   Name: Science United
   URL: https://scienceunited.org/
</pre>
        <li> If not, run
<pre>boinccmd --acct_mgr attach https://scienceunited.org email password</pre>
    using the email address and password of your Science United account.
        </ul>
    </ul>
    "
);

echo sprintf("<h3>%s</h3>
    <ul>
    <li> %s
    <li> %s
    <li> %s
    <li> %s
    </ul>
    ",
    tra("Other sources of help"),
    tra("Problems installing or running BOINC: %1 get help here%2.",
        "<a href=http://boinc.berkeley.edu/wiki/BOINC_Help>",
        "</a>"
    ),
    tra("Problems with Science United: ask for help on the %1Science United message boards%2.",
        "<a href=forum_index.php>",
        "</a>"
    ),
    tra("To report a bug or request a feature in Science United, create an issue on the %1Science United Github repository%2 (you'll need a free Github account).",
        "<a href=https://github.com/davidpanderson/science_united>",
        "</a>"
    ),
    tra("If none of the above work for you, %1 email us%2.",
        "<a href=https://boinc.berkeley.edu/anderson/>", "</a>"
    )
);
text_end();
page_tail();
?>
