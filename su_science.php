<?php
require_once("../inc/util.inc");
require_once("../inc/su_util.inc");
require_once("../inc/su_graph.inc");
require_once("../inc/keywords.inc");

function keywd_str($kws) {
    global $job_keywords;
    $x = "";
    $last = end($kws);
    foreach ($kws as $kw) {
        $n = $kw->keyword_id;
        $k = $job_keywords[$n];
        $x .= $k->name;
        if ($kw != $last) {
            $x .= ' &middot; ';
        }
    }
    return $x;
}

function show_proj($p) {
    echo "<hr>";
    echo sprintf('<h4>%s</h4>
        <p>
        Web site: <a href=%s>%s</a>
        <p>%s
        <p>Keywords: %s
        ',
        $p->name,
        $p->url, $p->url,
        $p->description,
        keywd_str($p->kws)
    );
}

function project_list() {
    echo "<h3>Projects</h3>";
    $projs = unserialize(file_get_contents("projects.ser"));
    shuffle($projs);
    foreach ($projs as $p) {
        show_proj($p);
    }
}

function project_graphs() {
    echo "<h3>Computing</h3>";
    echo '
        <br>&nbsp;<br>
        <img src="su_graph.php?type=projects&ndays=40&gpu=0&xsize=600&ysize=400">
        <br>&nbsp;<br>
        <img src="su_graph.php?type=projects&ndays=40&gpu=1&xsize=600&ysize=400">
    ';
}

function top() {
    echo "<p>";
    echo tra("By using Science United, you are supporting these science projects.  The choice of projects is determined your preferences.");
    echo "<p>";
    echo tra("Visit project web sites to learn more.");
}

function main() {
    page_head(tra("Science"));
    grid('top', 'project_list', 'project_graphs');
    page_tail();
}

main();
?>
