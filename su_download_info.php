<?php

require_once("../inc/xml.inc");
require_once("../inc/account.inc");

function get_platform($user_agent) {
    if (strstr($user_agent, 'Windows')) {
        if (strstr($user_agent, 'Win64')||strstr($user_agent, 'WOW64')) {
            return 'windows_x86_64';
        } else {
            return 'windows_intelx86';
        }
    } else if (strstr($user_agent, 'Mac')) {
        if (strstr($user_agent, 'PPC Mac OS X')) {
            return 'powerpc-apple-darwin';
        } else {
            return 'x86_64-apple-darwin';
        }
    } else {
        return null;
    }
}

// find release version for user's platform
//
function get_version($p) {
    $v = simplexml_load_file("versions.xml");
    foreach ($v->version as $i=>$v) {
        if ((string)$v->dbplatform != $p) {
            continue;
        }
        if (strstr((string)$v->description, "Recommended")) {
            return $v;
        }
    }
    return null;
}

function main($user_agent, $auth) {
    $user = BoincUser::lookup_auth($auth);
    if (!$user) {
        xml_error(ERR_DB_NOT_FOUND, "No such user");
    }
    $p = get_platform($user_agent);
    if ($p) {
        $token = make_login_token($user);
        $v = get_version($p);
        echo sprintf(
"    <project_id>101</project_id>
    <token>%s</token>\n
    <user_id>%d</user_id>
    <platform>%s</platform>
    <installer>
        <filename>%s</filename>
        <size>%s MB</size>
        <versions>BOINC %s</versions>
    </installer>
",
            $token,
            $user->id,
            (string)$v->platform,
            (string)$v->filename,
            (string)$v->size_mb,
            (string)$v->version_num
        );
        if ($v->vbox_filename) {
            echo sprintf(
"    <installer_vbox>
        <filename>%s</filename>
        <size>%s MB</size>
        <versions>BOINC %s, VirtualBox %s</versions>
    </installer_vbox>
",
            (string)$v->vbox_filename,
            (string)$v->vbox_size_mb,
            (string)$v->version_num,
            (string)$v->vbox_version
            );
        }
    } else {
        echo "   <platform></platform>\n";
    }
    echo "</download_info>\n";
}

xml_header();
echo "<download_info>\n";
$xml_outer_tag = "download_info";
$user_agent = get_str("user_agent");
$auth = get_str("auth");

main($user_agent, $auth);

?>
