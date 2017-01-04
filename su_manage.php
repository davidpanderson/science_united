<?php

// management interface home page

require_once("../inc/util.inc");

function main() {
    page_head("Science United Administration");
    echo '
        <a href="su_projects_edit.php">Projects (info/edit)</a>
        <p> <a href="su_projects_acct.php">Projects (accounting)</a>
        <p> <a href="su_acct.php">Accounting totals</a>
    ';
    page_tail();
}

main();

?>
