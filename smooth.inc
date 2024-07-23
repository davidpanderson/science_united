<?php

// the SU accounting data has (for unknown reasons)
// occasional outlier values, 10X or 20X the nearby averages.

// the input array has
// - valid data
// - outliers (too-large values)
// - zeroes (missing data)
// replace the outliers with zero
//
function remove_outliers(&$vals) {
    $n = count($vals);
    $m = $n>20?10:3;
    $avg = 0;
    for ($i=0; $i<$n; $i++) {
        $x = $vals[$i];
        //echo "$i $x $avg\n";
        if (!$x) continue;
        if ($x < 2*$avg) {
            $avg = .9*$avg + .1*$x;
            continue;
        }
        $y = local_median($vals, $n, $i, $m);
        //echo "local median $y\n";
        if ($x > 3*$y) {
            // outlier
            $vals[$i] = 0;
        }
        $avg = .9*$avg + .1*$x;
    }
}

// the median of the 2m elements on either side of $i
function local_median($vals, $n, $i, $m) {
    $j1 = $i - $m;
    if ($j1<0) $j1 = 0;

    $j2 = $i + $m;
    if ($j2 > $n) $j2 = $n;

    $y = [];
    for ($k=$j1; $k<$j2; $k++) {
        if ($k == $i) continue;
        $x = $vals[$k];
        if (!$x) continue;
        $y[] = $x;
    }
    if (!$y) return 0;
    return median($y);
}

// $x is nonempty
function median($x) {
    sort($x);
    $n = count($x);
    $h = intdiv($n, 2);
    if ($n & 1) {
        return $x[$h];
    }
    return ($x[$h-1]+$x[$h])/2;
}

?>
