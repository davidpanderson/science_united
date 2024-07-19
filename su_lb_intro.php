<?php
require_once('../inc/su.inc');

function main($user) {
    page_head('Science United leaderboards');
    text_start();
    echo '
        <p>
        The <a href=su_lb.php>leaderboards</a> show volunteers
        who have done the most computing for Science United,
        as measured in FLOPs (floating-point operations).
        There are two lists:
        <ul>
        <li> most total computing;
        <li> most computing in the past week.
        </ul>
        <p>
        These lists are opt-in;
        you won\'t appear on them unless you want to.
        If you opt in, the following will be visible to
        other Science United users:
        <p>
        <ul>
        <li> A list of your computers and their hardware/software details,
            such as their CPU/GPU type and operating system.
        <li> Graphs of your Science United computing history.
        </ul>
        <p>
    ';

    if ($user->donated) {
        echo '
            You have opted in to the leaderboards.
            If you like, you can
        ';
        show_button('su_lb_intro.php?action=opt_out', 'Opt out');
    } else {
        echo '
            You have not opted in to the leaderboards.
            If you like, you can
        ';
        show_button('su_lb_intro.php?action=opt_in', 'Opt in');
    }
    text_end();
    page_tail();
}

$user = get_logged_in_user();
$action = get_str('action', true);
if ($action == 'opt_in') {
    $user->update('donated=1');
    header('Location: su_lb_intro.php');
    exit();
}
if ($action == 'opt_out') {
    $user->update('donated=0');
    header('Location: su_lb_intro.php');
    exit();
}
main($user);

?>
