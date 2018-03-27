<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2018 University of California
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

// functions for choosing projects to attach a host to
//
// this is used in three places:
//
// AM RPC (rpc.php)
//      choose_projects_rpc(user, host, req)
//          rank_projects(user, host, req)
//              select_projects_resource(host, projects)
// account creation
//      choose_projects_join(user)
//          rank_projects(user, null, null)
// host/projects web page
//      rank_projects(user, host, null)
//      select_projects_resource()

require_once("../inc/su_db.inc");
require_once("../inc/su_project_infos.inc");
require_once("../inc/download_util.inc");

define('MIN_PROJECTS', 2);
    // min # of projects to have a client run (if possibl).
    // this is for appearance's sake,
    // to emphasize that there are multiple projects

// return project keyword's contribution to project score.
// -1 means don't use.
// TODO: should more specific keywords (e.g. level > 0) get a higher score?
//
function keyword_score($kw, $ukws) {
    // lookup up keyword in user list
    //
    foreach ($ukws as $ukw) {
        if ($ukw->keyword_id == $kw->keyword_id) {
            if ($ukw->yesno == KW_YES) {
                return $kw->fraction;
            } else if ($ukw->yesno = KW_NO) {
                if ($kw->fraction == 1) {
                    return -1;
                } else {
                    return 0;
                }
            }
        }
    }
    return 0;
}

// return
// -1 if host and project have no common "extended platforms"
//   (platform, gpu, vbox)
// 1 if host and project have a common platform that uses GPU or vbox
// 0 otherwise
// "host" has been populated with platforms, gpu, vbox
//
function platform_score($project, $host) {
    global $project_infos;
    $avs = $project_infos[$project->id]->avs;
    if (!$avs) {
        return -2;
    }
    $found = false;
    foreach ($avs as $av) {
        if (!in_array($av->platform, $host->platforms)) {
            continue;
        }
        if ($av->gpu && !in_array($av->gpu, $host->resources)) {
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

function keywd_score($pkws, $ukws) {
    $x = 0;
    foreach ($pkws as $pwk) {
        $s = keyword_score($pwk, $ukws);
        if ($s == KW_NO) {
            $x = -1;
            break;
        } else {
            $x += $s;
        }
    }
    return $x;
}

// compute a score for this project, given user prefs.
// higher = more preferable
// -1 means don't use
//
function project_score($project, $ukws, $host) {
    global $project_infos;
    $pkws = $project_infos[$project->id]->kws;

    $s = platform_score($project, $host);

    $project->platform_score = $s;
    $project->score = $s;

    $project->keyword_score = keywd_score($pkws, $ukws);

    if ($project->platform_score < 0 || $project->keyword_score < 0) {
        $project->score = -1;
    } else {
        $project->score = $project->platform_score + $project->keyword_score + $project->projected_balance;
    }

    // TODO: give an edge to projects the host is already running

    return $project;
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

// populate $host->resources and $host->vbox based on contents of serialnum
//
function host_serialnum_to_gpus_vbox($host) {
    $host->resources = array("cpu");
    $host->vbox = false;
    $descs = explode("]", $host->serialnum);
    foreach ($descs as $desc) {
        $desc = trim($desc, "[");
        $d = explode("|", $desc);
        if ($d[0] == "vbox") {
            $host->vbox = true;
        } else if ($d[0] == "CUDA") {
            $host->resources[] = "nvidia";
        } else if ($d[0] == "CAL") {
            $host->resources[] = "ati";
        } else if ($d[0] == "INTEL") {
            $host->resources[] = "intel_gpu";
        }
    }
    return $host;
}

// populate $host->platforms, $host->resources, $host_vbox
//
function populate_score_info($host, $req) {
    if (!$host) {
        // return a host w/ platforms based on agent string
        //
        $h = new StdClass;
        $client_info = $_SERVER['HTTP_USER_AGENT'];
        $h->platforms = array(client_info_to_platform_long($client_info));
        $h->resources = array("cpu");
        $h->vbox = false;
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
        if (count($host->platforms) == 0) {
            log_write("request has no platforms");
            am_error_reply("Incomplete request message.  Please upgrade to the latest BOINC client.");
        }
        $host->resources = array("cpu");
        if ($req->host_info->coprocs->coproc_cuda) {
            $host->resources[] = "nvidia";
        }
        if ($req->host_info->coprocs->coproc_ati) {
            $host->resources[] = "amd";
        }
        if ($req->host_info->coprocs->coproc_intel_gpu) {
            $host->resources[] = "intel_gpu";
        }

        $host->vbox = (string)$req->host_info->virtualbox_version?true:false;
    } else {
        // no request - get platform from processor and OS info
        //
        $host = host_info_to_platforms($host);
        // get GPUs and Vbox info
        //
        $host = host_serialnum_to_gpus_vbox($host);
    }

    return $host;
}

// return list of projects ordered by descending score
// Called:
// - at join time (host, req = null)
// - from host detail page (req = null)
// - from RPC (host, req not null)
//      don't return projects w/ negative scores
//
function rank_projects($user, $host, $req) {
    $ukws = SUUserKeyword::enum("user_id=$user->id");
    $projects = SUProject::enum("status=".PROJECT_STATUS_AUTO);
    $accts = SUAccount::enum("user_id=$user->id and opt_out>0");
    $opt_out = array();
    foreach ($accts as $a) {
        $opt_out[$a->project_id] = 1;
    }

    $host = populate_score_info($host, $req);
    $p2 = array();
    foreach ($projects as $p) {
        $p = project_score($p, $ukws, $host);
        $p->opt_out = false;
        if (array_key_exists($p->id, $opt_out)) {
            $p->score = -1;
            $p->opt_out = true;
        }
        if ($req) {
            if ($p->score < 0) continue;
            $p->score += rand(0, 10000)/1000000.;
        }
        $p2[] = $p;
    }
    usort($p2,
        function($x, $y){
            if ($x->score < $y->score) return 1;
            if ($x->score == $y->score) return 0;
            return -1;
        }
    );

    return $p2;
}

// $used is an array of rsc => used_flag
// Return true if all resources used
//
function all_used($used) {
    foreach ($used as $r => $u) {
        if (!$u) return false;
    }
    return true;
}

// can the project use resource r on the given host?
//
function can_use($project, $host, $r) {
    global $project_infos;
    $avs = $project_infos[$project->id]->avs;
    foreach ($avs as $av) {
        if (!in_array($av->platform, $host->platforms)) continue;
        if ($av->vbox && !$host->vbox) continue;
        if ($r == "cpu") {
            if ($av->gpu) continue;
        } else {
            if ($av->gpu != $r) continue;
        }
        return true;
    }
    return false;
}

// Given the score-ordered list of projects,
// select a list that uses all resources.
// For each project p, include an array p->use of resources that it should use.
//
function select_projects_resource($host, $projects) {
    $used = array();
    foreach ($host->resources as $r) {
        $used[$r] = false;
    }
    foreach ($projects as $p) {
        $p->included = false;
    }
    $p2 = array();
    foreach ($projects as $p) {
        $p->use = array();
        $found = false;
        foreach ($used as $r => $u) {
            $p->use[$r] = false;
            if ($u) continue;
            if (can_use($p, $host, $r)) {
                $found = true;
                $p->use[$r] = true;
                $used[$r] = true;
            }
        }
        if ($found) {
            $p2[] = $p;
            $p->included = true;
        }

        if (all_used($used)) {
            break;
        }
    }

    // if not enough projects, add ones that can use some resource
    //
    foreach ($projects as $p) {
        if (count($p2) >= MIN_PROJECTS) {
            break;
        }
        if ($p->included) continue;
        //echo "considering $p->name\n";
        $found = false;
        foreach ($host->resources as $r) {
            if (can_use($p, $host, $r)) {
                $p->use[$r] = true;
                $found = true;
            }
        }
        //echo "found: $found\n";
        if ($found) {
            $p2[] = $p;
        }
    }
    return $p2;
}

// Called when account is first created.
// Decide what projects to have this user run initially.
// We don't know anything about the host at this point.
//
function choose_projects_join($user) {
    $projects = rank_projects($user, null, null);
    $n = 0;
    $chosen_projects = array();
    foreach ($projects as $p) {
        if ($p->score < 0) break;
        $ret = SUAccount::insert(
            sprintf("(project_id, user_id, create_time, state) values (%d, %d, %f, %d)",
                $p->id, $user->id, time(), ACCT_INIT
            )
        );
        $chosen_projects[] = $p;
        $n++;
        if ($n == 1) break;     // 1 is enough for now
    }
    return $chosen_projects;
}

// Decide what projects to have this user/host run.
// Called from AM RPC handler.
// Return list of accounts that are confirmed,
// and a flag indicating whether new accounts were requested
//
function choose_projects_rpc($user, $host, $req) {
    $projects = rank_projects($user, $host, $req);

    // select subset of projects that use all resources
    //
    $projects = select_projects_resource($host, $projects);

    $n = 0;
    $accounts_to_send = array();
    $new_accounts = false;
    foreach ($projects as $p) {
        // see if user has account on the project
        //
        $account = SUAccount::lookup(
            "project_id = $p->id and user_id = $user->id"
        );
        if ($account) {
            if ($account->state == ACCT_SUCCESS) {
                $accounts_to_send[] = array($p, $account);
                $n++;
            } else {
                continue;
            }
        } else {
            // if not, initiate the creation of one
            //
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
