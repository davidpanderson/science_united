<?php

// remove translations without a project-specific part

function main() {
    $compiled = scandir("../languages/compiled");
    $proj_spec = scandir("../languages/project_specific_translations");
    foreach ($compiled as $c) {
        if (substr($c, 1) == '.') continue;
        $x = explode('.', $c);
        $y = $x[0].'.'.$x[1];
        if (!in_array($y, $proj_spec)) {
            echo "deleting $c\n";
            unlink("../languages/compiled/$c");
        }
    }
}

main();

?>
