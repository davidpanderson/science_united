<?php

// form for graphs
// args:
// what: ec/time/jobs
// userid
//      if present, show a user (possibly logged in user)
//      else show totals
// ndays: how far back to show
// integrate: if true, show cumulative total

require_once('../inc/util.inc');

function form($what, $integrate, $ndays, $user, $is_me) {
    form_start('su_graph2.php');
    if ($user) {
        form_radio_buttons('Work source', 'userid',
            [
                [$user->id, $is_me?'Your computers':$user->name],
                [0, 'All Science United volunteers']
            ],
            $user->id
        );
    }
    form_radio_buttons('What to graph', 'what',
        [
            ['ec','FLOPs'],
            ['time', 'Time'],
            ['jobs', 'Jobs'],
            ['users', 'Active volunteers and computers']
        ],
        $what
    );
    form_checkboxes('Show cumulative total', [['integrate', '', $integrate]]);
    form_input_text('Duration (days)', 'ndays', $ndays);
    form_submit('OK');
    form_end();
}

function main($what, $ndays, $integrate, $user, $is_me) {
    if ($user) {
        if ($is_me) {
            page_head('Work done by you');
        } else {
            page_head(sprintf('Work done by %s', $user->name));
        }
        $type = 'user';
    }  else {
        page_head('Work done by Science United');
        $type = 'total';
    }
    form($what, $integrate, $ndays, $user, $is_me);
    $url = sprintf(
        'su_graph.php?type=%s&id=%d&what=%s&ndays=%d&integrate=%d&xsize=%d&ysize=%d',
        $type, $user->id, $what, $ndays, $integrate, 900, 500
    );    
    //echo $url;
    echo sprintf('<center><img class="img-responsive", src=%s></center>', $url);
    page_tail();
}

$ndays = get_int('ndays', true);
if (!$ndays) $ndays = 30;

$what = get_str('what', true);
if (!$what) $what = 'ec';

$is_me = false;
$liu = get_logged_in_user(false);
$liu_id = $liu?$liu->id:0;
$userid = get_int('userid', true);
if ($userid) {
    if ($userid == $liu_id) {
        $user = $liu;
        $is_me = true;
    } else {
        $user = BoincUser::lookup_id($userid);
        if (!$user) error_page('no user');
        if (!$user->donated) error_page('no access');
    }
} else {
    $user = null;
}

$integrate = get_str('integrate', true);
$integrate = $integrate?1:0;

main($what, $ndays, $integrate, $user, $is_me);

?>
