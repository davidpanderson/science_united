#! usr/bin/env php
<?php

// get project list XML file and put in projects.xml

file_put_contents(
    'projects.xml',
    file_get_contents('https://boinc.berkeley.edu/project_list.php')
);

?>
