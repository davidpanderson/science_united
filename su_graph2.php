<?php

// form for graphs

require_once('../inc/util.inc');

function form($type, $what, $integrate, $ndays) {
    form_start('su_graph2.php');
    form_radio_buttons('Source', 'type',
        [['user', 'My computers'],['total', 'All Science United computers']],
        $type
    );
    form_radio_buttons('What to graph', 'what',
        [['ec','FLOPs'],['time', 'Time'], ['jobs', 'Jobs']],
        $what
    );
    form_checkboxes('Show cumulative total', [['integrate', '', $integrate]]);
    form_input_text('Duration (days)', 'ndays', $ndays);
    form_submit('OK');
    form_end();
}

function main($type, $what, $ndays, $integrate) {
    $user = get_logged_in_user();
    page_head('Work done');
    form($type, $what, $integrate, $ndays);
    $url = sprintf('su_graph.php?type=%s&id=%d&what=%s&ndays=%d&integrate=%d&xsize=%d&ysize=%d',
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

$integrate = get_str('integrate', true);
$integrate = $integrate?1:0;

main($type, $what, $ndays, $integrate);

?>
