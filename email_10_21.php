<?php

require_once("../inc/email.inc");

function send($user) {
    echo "sending to $user->name $user->email_addr\n";

    $x = "Dear $user->name:

If you are running BOINC on a Windows computer,
please install a new version (7.16.20) of the software.
The previous version stopped working recently because of an expired SSL file
that prevented it from communicating with this project.

You can download the new version here:
https://boinc.berkeley.edu/download.php

Sorry for the inconvenience.

";
    send_email($user, PROJECT.": please install new BOINC version", $x);
}

function main() {
    $lines = file("email_user_id");
    foreach ($lines as $line) {
        $id = (int)$line;
        if ($id <= 0) continue;
        $user = BoincUser::lookup_id($id);
        if (!$user) continue;
        send($user);
    }
}

main();
?>
