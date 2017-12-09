<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2017 University of California
//
// BOINC is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// BOINC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

// functions for choosing projects to attach a user to

require_once("../inc/su_db.inc");

// if user has a pref for this keyword, return -1/1, else 0
// TODO: should more specific keywords (e.g. level > 0) get a higher score?
//
function keyword_score($kw_id, $ukws) {
    foreach ($ukws as $ukw) {
        if ($ukw->keyword_id == $kw_id) {
            return $ukw->yesno;
        }
    }
    return 0;
}

$project_avs = null;

// return
// -1 if host and project have no common "extended platforms"
//   (platform, gpu, vbox)
// 1 if host and project have a common platform that uses GPU or vbox
// 0 otherwise
// "host" has been populated with platforms, gpu, vbox
//
function platform_score($project, $host) {
    global $project_avs;
    if (!$project_avs) {
        $project_avs = unserialize(file_get_contents("project_avs.ser"));
    }
    $avs = $project_avs[$project->id];
    if (!$avs) {
        return -2;
    }
    $found = false;
    foreach ($avs as $av) {
        if (!in_array($av->platform, $host->platforms)) {
            continue;
        }
        if ($av->gpu && !in_array($av->gpu, $host->gpus)) {
            continue;
        }
        if ($av->vbox && !$host->vbox) {
            continue;
        }
        // here if host supports this platform
        //
        if ($av->gpu || $av->vbox) {
            return 1;
        }
        $found = true;
    }
    if ($found) return 0;
    return -1;
}

// compute a score for this project, given user prefs.
// higher = more preferable
// -1 means don't use
//
// Currently based just on keywords.
// TODO: take GPUs into account?
// TODO: take balance into account
//
function project_score($project, $ukws, $host) {
    $pkws = SUProjectKeyword::enum("project_id = $project->id");
    $score = 0;
    foreach ($pkws as $pwk) {
        $s = platform_score($project, $host);
        $s = keyword_score($pwk->keyword_id, $ukws);
        if ($s == KW_NO) {
            return -1;
        }
        $score += $s;
    }
    return $score*$project->share;

    // TODO: give an edge to projects the host is already running
}

// populate host->platforms based on CPU and OS info
//
function host_info_to_platforms($host) {
    $p = array();
    if (strstr($host->os_name, "Windows")) {
        $p[] = "windows_intelx86";
        if (strstr($host->os_version, "x64")) {
            $p[] = "windows_x86_64";
        }

    // for linux and mac we have no info about 32/64 bit; assume 64
    //
    } else if (strstr($host->os_name, "Linux")) {
        $p[] = "x86_64-pc-linux-gnu";
        $p[] = "i686-pc-linux-gnu";
    } else if (strstr($host->os_name, "Darwin")) {
        $p[] = "x86_64-apple-darwin";
    } else if (strstr($host->os_name, "Android")) {
        $p[] = "arm-android-linux-gnu";
    }
    $host->platforms = $p;
    return $host;
}

// populate $host->gpus and $host->vbox based on contents of serialnum
//
function host_info_to_gpus_vbox($host) {
    $host->gpus = array();
    $host->vbox = false;
    $descs = explode("]", $host->serialnum);
    foreach ($descs as $desc) {
        $desc = trim($desc, "[");
        $d = explode("|", $desc);
        if ($d[0] == "vbox") {
            $host->vbox = true;
        } else if ($d[0] == "CUDA") {
            $host->gpus[] = "nvidia";
        } else if ($d[0] == "CAL") {
            $host->gpus[] = "ati";
        } else if ($d[0] == "INTEL") {
            $host->gpus[] = "intel_gpu";
        }
    }
    return $host;
}

// populate $host->platforms, $host->gpus, $host_vbox
//
function populate_host($host, $req) {
    if (!$host) {
        // return a host w/ platforms based on agent string
        //
        $h = new StdClass;
        $client_info = $_SERVER['HTTP_USER_AGENT'];
        $h->platforms = array(client_info_to_platform_long($client_info));
        return $h;
    }

    // here we have host.
    //
    if ($req) {
        // get platforms from AM request
        //
        $host->platforms = array();
        $p = (string)$req->platform_name;
        if ($p) {
            $host->platforms[] = $p;
        }
        foreach ($req->alt_platform as $ap) {
            $p = (string)$ap->name;
            if ($p) {
                $host->platforms[] = $p;
            }
        }
    } else {
        // no request - get platform from processor and OS info
        //
        $host = host_info_to_platforms($host);
    }

    // get GPUs and Vbox info
    //
    $host = host_info_to_gpus_vbox($host);

    return $host;
}

// return list of projects ordered by descending score
// Called:
// - at join time (host, req = null)
// - from host detail page (req = null)
// - from RPC (host, req not null)
//
function rank_projects($user, $host, $req) {
    $ukws = SUUserKeyword::enum("user_id=$user->id");
    $projects = SUProject::enum("status=".PROJECT_STATUS_AUTO);
    $host = populate_host($host, $req);
    foreach ($projects as $p) {
        $p->score = project_score($p, $ukws, $host);
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

// decide what projects to have this user run.
// called when account is first created.
// we don't know anything about host at this point.
//
function choose_projects_join($user) {
    $projects = rank_projects($user, null, null);
    $n = 0;
    $chosen_projects = array();
    foreach ($projects as $p) {
        $ret = SUAccount::insert(
            sprintf("(project_id, user_id, state) values (%d, %d, %d)",
                $p->id, $user->id, ACCT_INIT
            )
        );
        $chosen_projects[] = $p;
        $n++;
        if ($n == 3) break;
    }
    return $chosen_projects;
}

// decide what projects to have this user/host run.
// Called from AM RPC handler.
// Return list of accounts that are confirmed,
// and a flag indicating new accounts were requested
//
function choose_projects_rpc($user, $host, $req) {
    $projects = rank_projects($user, $host, $req);
    $n = 0;
    $accounts_to_send = array();
    $new_accounts = false;
    foreach ($projects as $p) {
        $account = SUAccount::lookup(
            "project_id = $p->id and user_id = $user->id"
        );
        if ($account) {
            if ($account->state == ACCT_SUCCESS) {
                $accounts_to_send[] = array($p, $account);
                $n++;
                if ($n == 3) break;
            } else {
                continue;
            }
        } else {
            $ret = SUAccount::insert(
                sprintf("(project_id, user_id, state) values (%d, %d, %d)",
                    $p->id, $user->id, ACCT_INIT
                )
            );
            if (!$ret) {
                su_error(-1, "account insert failed");
            }
            touch("make_accounts_trigger");
            $new_accounts = true;
        }
    }
    return array($accounts_to_send, $new_accounts);
}

?>
