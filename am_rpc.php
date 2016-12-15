<?php

// AM RPC handler

$dir = getcwd();
chdir("/disks/a/user/boincadm/projects/test2/html/user");
require_once("../inc/xml.inc");
chdir($dir);

// The user account already exists.
// Project accounts should already exist.
// The host may not.

function main() {
    $req = simplexml_load_string($_POST['request']);
    if (!$req) {
        xml_error(-1, "can't parse request");
    }
    $host_cpid = (int)$req->host_cpid;
    $host =  BoincHost::lookup_cpid($host_cpid);
    if (!$host) {
        $host_cpid = (int)$req->previous_host_cpid;
        $host =  BoincHost::lookup_cpid($host_cpid);
    }
    if (!$host) {
        $id = BoincHost::insert();
        $host = BoincHost::lookup_id($id);
    }

}

main();

?>
