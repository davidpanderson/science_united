<?php

// AM RPC handler
//
// The user account already exists; the host may not.
// this call must return quickly, else the client will hang.
// So what we do is:
// - decide what projects to have the host run
// - for each project for which the user doesn't have an account,
//   create an account record; a periodic task will create the account later
// - return a list of projects for which the user has a working account

$dir = getcwd();
chdir("/mydisks/a/users/boincadm/projects/test2/html/user");
require_once("../inc/xml.inc");
require_once("../inc/boinc_db.inc");
chdir($dir);

require_once("su_db.inc");

// if user has a pref for this keyword, return -1/1, else 0
//
function keyword_score($kw_id, $ukws) {
    foreach ($ukws as $ukw) {
        if ($ukw->keyword_id == $kw_id) {
            return $ukw->type;
        }
    }
    return 0;
}

// compute a score for this project, given user prefs.
// higher = more preferable
// -1 means don't use
//
function project_score($project, $ukws) {
    $pkws = SUProjectKeyword::enum("project_id = $project->id");
    $score = 0;
    foreach ($pkws as $pwk) {
        $s = keyword_score($pwk->keyword_id, $ukws);
        if ($s == KW_NO) {
            return -1;
        }
        $score += $s;
    }
    return $score*$project->allocation;
}

// return list of projects ordered by descending score
//
function rank_projects($user, $host) {
    $ukws = SUUserKeyword::enum("user_id=$user->id");
    $projects = SUProject::enum();
    foreach ($projects as $p) {
        $p->score = project_score($p, $ukws);
    }
    usort($projects,
        function($x, $y){
            if ($x->score < $y->score) return 1;
            if ($x->score == $y->score) return 0;
            return -1;
        }
    );
    return $projects;
}

// $accounts is an array of array(project, account)
//
function send_reply($accounts) {
    xml_header();
    echo "<acct_mgr_reply>\n"
        ."<name>Science United</name>\n"
        ."<signing_key>\n"
    ;
    readfile('signing_key_public');
    echo "</signing_key>\n"
        ."<repeat_sec>86400</repeat_sec>\n"
    ;
    foreach ($accounts as $a) {
        $proj = $a[0];
        $acct = $a[1];
        echo "<account>\n"
            ."<url>$proj->url</url>\n"
            ."<url_signature>\n$proj->url_signature\n</url_signature>\n"
        ;
    }
    echo "</acct_mgr_reply>\n";
}

function main() {
    //$req = simplexml_load_string($_POST['request']);
    $req = simplexml_load_file('req.xml');
    if (!$req) {
        xml_error(-1, "can't parse request");
    }

    $email_addr = (string)$req->name;
    $user = BoincUser::lookup_email_addr($email_addr);
    if (!$user) {
        xml_error(-1, 'no account found');
    }

    $passwd_hash = (string)$req->password_hash;
    echo "$passwd_hash";
    if ($passwd_hash != $user->passwd_hash) {
        xml_error(-1, 'bad password');
    }

    $host_cpid = (int)$req->host_cpid;
    $host = BoincHost::lookup_cpid($host_cpid);
    if (!$host) {
        $host_cpid = (int)$req->previous_host_cpid;
        $host =  BoincHost::lookup_cpid($host_cpid);
    }
    if ($host) {
        $host_id = $host->id;
        // TODO: update host info
    } else {
        $now = time();
        $host_id = BoincHost::insert(
            "(create_time, user_id, host_cpid) values ($now, $user->id, $host_cpid)"
        );
        // TODO: fill in host info
    }

    $projects = rank_projects($user, $host);
    $n = 0;
    $accounts_to_send = array();
    foreach ($projects as $p) {
        $account = SUAccount::lookup(
            "project_id = $p->id and user_id = $user->id"
        );
        if ($account) {
            $accounts_to_send[] = array($p, $account);
        } else {
            SUAccount::insert(
                sprintf("(project_id, user_id, state) values (%d, %d, %d)",
                    $p->id, $user->id, INIT
                )
            );
        }
        $n++;
        if ($n == 3) break;
    }
    send_reply($accounts_to_send());
}

main();

?>
