<?php

// read projects.xml and, for each project, make a list of
// (platform, GPU type, is_vbox)
// and write this to a file

function get_avs($p) {
    $avs = array();
    foreach ($p->platforms->name as $x) {
        $y = explode("[", (string)$x);
        $z = new StdClass;
        $z->platform = $y[0];
        $z->gpu = "";
        $z->vbox = false;
        if (count($y) > 1) {
            if (strstr($y[1], "cuda")) {
                $z->gpu = "nvidia";
            }
            if (strstr($y[1], "nvidia")) {
                $z->gpu = "nvidia";
            }
            if (strstr($y[1], "amd")) {
                $z->gpu = "amd";
            }
            if (strstr($y[1], "ati")) {
                $z->gpu = "amd";
            }
            if (strstr($y[1], "intel_gpu")) {
                $z->gpu = "intel";
            }
            if (strstr($y[1], "vbox")) {
                $z->vbox = true;
            }
        }
        $avs[] = $z;
    }
    return array_unique($avs, SORT_REGULAR);
}

function main() {
    $x = simplexml_load_file("projects.xml");
    $y = array();
    foreach ($x->project as $p) {
        $avs = get_avs($p);
        $y[(int)$p->id] = $avs;
    }
    file_put_contents("project_avs.ser", serialize($y));

}

main();

?>
