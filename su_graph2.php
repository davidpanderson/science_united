<?php

// form for graphs
// args:
// what: ec/time/jobs
// type: user/total
// id: if type is user, the user to show (check permission)
//      if absent/0, show logged-in user
//      if present, don't show type options
// ndays: how far back to show
// integrate: if true, show cumulative total

require_once('../inc/util.inc');

function form($type, $what, $integrate, $ndays, $user, $is_me) {
    form_start('su_graph2.php');
    if ($type == 'user' ) {
        if (!$is_me) {
            form_input_hidden('id', $user->id);
            form_input_hidden('type', 'user');
        }
    }
    if ($is_me) {
        form_radio_buttons('Source', 'type',
            [['user', 'My computers'],['total', 'All Science United computers']],
            $type
        );
    }
    form_radio_buttons('What to graph', 'what',
        [['ec','FLOPs'],['time', 'Time'], ['jobs', 'Jobs']],
        $what
    );
    form_checkboxes('Show cumulative total', [['integrate', '', $integrate]]);
    form_input_text('Duration (days)', 'ndays', $ndays);
    form_submit('OK');
    form_end();
}

function main($type, $what, $ndays, $integrate, $user, $is_me) {
    if ($type == 'user') {
        if ($is_me) {
            page_head('Work done by you');
        } else {
            page_head(sprintf('Work done by %s', $user->name));
        }
    }  else {
        page_head('Work done by Science United');
    }
    form($type, $what, $integrate, $ndays, $user, $is_me);
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

$type = get_str('type', true);
if (!$type) $type = 'user';

$is_me = false;
if ($type == 'user') {
    $id = get_int('id', true);
    if ($id) {
        $user = BoincUser::lookup_id($id);
        if (!$user) error_page('no user');
        if (!$user->donated) error_page('no access');
    } else {
        $user = get_logged_in_user();
        $is_me = true;
    }
}

$integrate = get_str('integrate', true);
$integrate = $integrate?1:0;

main($type, $what, $ndays, $integrate, $user, $is_me);

?>
