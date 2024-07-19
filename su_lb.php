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
    $user = get_logged_in_user();
    $users = BoincUser::enum('donated>0');
    $x = [];
    foreach ($users as $user) {
        $a = get_work_info($user->id);
        if (!$a) continue;
        $x[] = [$user, $a];
    }
    usort($x, $sort);
    page_head('Leading contributors');
    text_start(800);
    echo '
        <p>
        The volunteers who have contributes the most
        computing to Science United are listed below.
        <p>
        Volunteers are shown only if they have
        <a href=su_lb_intro.php>opted in</a>;
        you are encouraged to do so.
        <p>
    ';
    echo "<style> .rt { text-align: right; } </style>";
    start_table('table-striped');
    row_heading_array(
        [
            'Volunteer',
            sort_heading($sort, 'ec_total', 'Total TFLOPs'),
            sort_heading($sort, 'ec_avg', 'Recent TFLOPs per day'),
            //sort_heading($sort, 'time_total', 'Total processor time (days)'),
            //sort_heading($sort, 'time_avg', 'Recent processor time (days per day)')
        ],
        //['class=bg-primary', '', '', '', ''], 'bg-primary rt'
        ['class=bg-primary', '', ''], 'bg-primary rt'
    );
    $i = 0;
    foreach ($x as [$user, $a]) {
        $i++;
        row_array(
            [
                sprintf(
                    '%d. <a href=su_show_user.php?userid=%d>%s</a>',
                    $i, $user->id, $user->name
                ),
                number_format(ec_to_tflops($a->ec_total), 2),
                number_format(ec_to_tflops($a->ec_avg), 2),
                //number_format($a->time_total/86400, 2),
                //number_format($a->time_avg/86400,2)
            ],
            //['', 'class=rt', 'class=rt', 'class=rt', 'class=rt']
            ['', 'class=rt', 'class=rt']
        );
    }

    end_table();
    end_text();
    page_tail();
}

$sort = get_str('sort', true);
if (!$sort) $sort = 'ec_avg';
main($sort);

?>
