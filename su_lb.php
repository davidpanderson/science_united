<?php

require_once('../inc/su.inc');

function ec_total($a, $b) {
    return $a[1]->ec_total < $b[1]->ec_total;
}
function ec_avg($a, $b) {
    return $a[1]->ec_avg < $b[1]->ec_avg;
}
function time_total($a, $b) {
    return $a[1]->time_total < $b[1]->time_total;
}
function time_avg($a, $b) {
    return $a[1]->time_avg < $b[1]->time_avg;
}

function sort_heading($sort, $col_sort, $title) {
    if ($sort == $col_sort) {
        return "<u>$title</u>";
    } else {
        return sprintf('<a href=su_lb.php?sort=%s>%s</a>',
            $col_sort, $title
        );
    }
}

function main($sort) {
    $users = BoincUser::enum('donated>0');
    $x = [];
    foreach ($users as $user) {
        $acs = SUAccountingUser::enum("user_id=$user->id", "order by id desc limit 7");
        if (!$acs) {
            continue;
        }
        $ec_sum = 0;
        $time_sum = 0;
        foreach ($acs as $ac) {
            $ec_sum += $ac->cpu_ec_delta + $ac->gpu_ec_delta;
            $time_sum += $ac->cpu_time_delta + $ac->gpu_time_delta;
        }
        $a = new StdClass;
        $n = count($acs);
        $a->ec_avg = $ec_sum/$n;
        $a->time_avg = $time_sum/$n;
        $ac = $acs[0];
        $a->ec_total = $ac->cpu_ec_total + $ac->gpu_ec_total;
        $a->time_total = $ac->cpu_time_total + $ac->gpu_time_total;
        $x[] = [$user, $a];
    }
    usort($x, $sort);
    page_head('Leaders');
    echo "<style> .rt { text-align: right; } </style>";
    start_table();
    row_heading_array(
        [
            'Volunteer',
            sort_heading($sort, 'ec_total', 'Total TFLOPs'),
            sort_heading($sort, 'ec_avg', 'Recent TFLOPs per day'),
            sort_heading($sort, 'time_total', 'Total processor time (days)'),
            sort_heading($sort, 'time_avg', 'Recent processor time (days per day)')
        ],
        ['class=bg-primary', '', '', '', ''], 'bg-primary rt'
    );
    foreach ($x as [$user, $a]) {
        row_array(
            [
                sprintf('<a href=show_user.php?userid=%d>%s</a>', $user->id, $user->name),
                number_format(ec_to_tflops($a->ec_total), 2),
                number_format(ec_to_tflops($a->ec_avg), 2),
                number_format($a->time_total/86400, 2),
                number_format($a->time_avg/86400,2)
            ],
            ['', 'class=rt', 'class=rt', 'class=rt', 'class=rt']
        );
    }

    end_table();
    page_tail();
}

$sort = get_str('sort', true);
if (!$sort) $sort = 'ec_avg';
main($sort);

?>