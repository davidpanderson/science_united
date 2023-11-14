<?php

require_once("../inc/util.inc");
require_once("../inc/su_db.inc");
require_once("../inc/su_project_infos.inc");
require_once("../inc/keywords.inc");

// see how much this user has contributed to each top-level science keyword
// return an array mapping keyword ID to a structure containing
// cpu_ec, cpu_time, gpu_ec, gpu_time, njobs_success, njobs_fail
//
// Do this by:
// for each host H belonging to user
//    for each project P that H has contributed to (su_host_project)
//       K = P's top-level science keywords
//       for each k in K:
//           accumulate quantities, divided by sizeof(K)

function add_items($hp, $x) {
    $x->cpu_ec += $hp->cpu_ec;
    $x->cpu_time += $hp->cpu_time;
    $x->gpu_ec += $hp->gpu_ec;
    $x->gpu_time += $hp->gpu_time;
    $x->njobs_success += $hp->njobs_success;
    $x->njobs_fail += $hp->njobs_fail;
    return $x;
}

function set_items($hp) {
    $x = new stdClass();
    $x->cpu_ec = $hp->cpu_ec;
    $x->cpu_time = $hp->cpu_time;
    $x->gpu_ec = $hp->gpu_ec;
    $x->gpu_time = $hp->gpu_time;
    $x->njobs_success = $hp->njobs_success;
    $x->njobs_fail = $hp->njobs_fail;
    return $x;

}

// get project's top-level science keywords as (keyword_id, fraction) pairs
//
function project_areas($pid) {
    global $project_infos, $job_keywords;
    $areas = array();

    // NOTE: this omits defunct projects like SETI@home
    // Need to change this

    if (!array_key_exists($pid, $project_infos)) {
        return $areas;
    }
    $p = $project_infos[$pid];

    foreach ($p->kws as $kw) {
        $kwid = $kw->keyword_id;
        $k = $job_keywords[$kwid];
        if ($k->level == 0 && $k->category == KW_CATEGORY_SCIENCE) {
            $areas[] = $kw;
        }

    }

    // normalize fraction if > 1 areas
    //
    if (count($areas)>1) {
        $sum = 0;
        foreach ($areas as $a) {
            $sum += $a->fraction;
        }
        foreach ($areas as $a) {
            $a->fraction /= $sum;
        }
    }
    return $areas;
}

function scale($pt, $frac) {
    $pt2 = clone $pt;
    $pt2->cpu_ec *= $frac;
    $pt2->cpu_time *= $frac;
    $pt2->gpu_ec *= $frac;
    $pt2->gpu_time *= $frac;
    $pt2->njobs_success *= $frac;
    $pt2->njobs_fail *= $frac;
    return $pt2;
}

function get_area_totals($user) {
    $accounts = SUAccount::enum("user_id=$user->id");
    $area_totals = array();
    foreach ($accounts as $pt) {
        $kws = project_areas($pt->project_id);
        foreach ($kws as $kw) {
            $kwid = $kw->keyword_id;
            $pt2 = scale($pt, $kw->fraction);
            if (array_key_exists($kwid, $area_totals)) {
                $area_totals[$kwid] = add_items($pt2, $area_totals[$kwid]);
            } else {
                $area_totals[$kwid] = $pt2;
            }
        }
    }
    return $area_totals;
}

function comp_str($a) {
    $c = sprintf('%s CPU hours', number_format($a->cpu_time/3600., 2));
    if ($a->gpu_time) {
        $c .= sprintf(' and %s GPU hours', number_format($a->gpu_time/3600, 2));
    }
    return $c;
}

function show_cert($user) {
    global $job_keywords;

    $join = gmdate('j F Y', $user->create_time);
    $today = gmdate('j F Y', time(0));
    $border = 9;
    $font = "\"Optima,Lucida Bright,Times New Roman\"";

    $areas = get_area_totals($user);
    echo "
        <table width=1200 height=800 border=$border cellpadding=20><tr><td>
        <center>
        <table width=1000 border=0><tr><td>
        <center>
            <img width=\"80\" style=\"vertical-align:-50%\" src=power.png>
            &nbsp;&nbsp; <font face=Helvetica style=\"font-size:56\">Science United</font>
        <font face=$font style=\"font-size:30\">
        <br><br>
        Certificate of Computation
        <p>
        <font face=$font style=\"font-size:40\">
        $user->name

        <font face=$font style=\"font-size:20\">
        <p>
        Has participated in Science United since $join,
        and has contributed:
        <br><br>
        <table>
    ";
    foreach ($areas as $kwid=>$a) {
        if (!$a->cpu_time) continue;
        $kw = $job_keywords[$kwid];
        echo sprintf('<tr><td align=right>%s%s:</td><td>%s%s</td></tr>',
            '<font face=$font style="font-size:18">',
            $kw->name,
            '<font face=$font style="font-size:18">',
            comp_str($a)
        );
    }
    echo "</table>";
    echo "
        <br><br>
        </td><tr></table>
    ";
    echo "
        <table width=1100><tr>
        <td width=20% align=left>
            <img width=\"150\" src=ucbseal.png>
        </td>
        <td align=center>
            <p>
            <img valign=center width=\"280\" src=pictures/dpa_signature.png>
            <br>
            <font face=$font style=\"font-size:18\">
            <p>David P. Anderson
            <br>Director, Science United
            <br>
            $today
        </td>
        <td align=right width=20% valign=center>
            <img width=\"170\" src=pictures/NSF_4-Color_bitmap_Logo.png>
        </td>
        </tr>
        </table>
    ";
    echo "
    </td><tr></table>
    ";
}

$user = get_logged_in_user();
show_cert($user);
?>
