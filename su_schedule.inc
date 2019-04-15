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
require_once("../inc/su_util.inc");
require_once("../inc/su_project_infos.inc");
require_once("../inc/download_util.inc");

define('LOG_PROJECT_SELECT', true);

define('MIN_PROJECTS', 2);
    // min # of projects to have a client run (if possible).
    // this is for appearance's sake,
    // to emphasize that there are multiple projects
define('MAX_PROJECTS', 10);
    // max # of projects.
    // this could happen if lots of project aren't supplying work to the host

// Our "canonical" resource names are cpu, nvidia, amd, and intel_gpu
// Convert other variants to canonical
//
function canonical_resource_name($x) {
    switch ($x) {
    case "CPU": return "cpu";
    case "NVIDIA": return "nvidia";
    case "ATI": return "amd";
    case "intel_gpu": return "intel_gpu";
    }
    log_write("Unknown resource $x");
    return $x;
}

// canonical to human-readable 
//
function human_resource_name($x) {
    switch ($x) {
    case "cpu": return "CPU";
    case "nvidia": return "NVIDIA GPU";
    case "amd": return "AMD GPU";
    case "intel_gpu": return "Intel GPU";
    }
    return $x;
}

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
            return .1;
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

function allocation_score($project) {
    $su_allocate = SUAllocate::get();
    $x = $project->avg_ec_adjusted/$su_allocate->avg_ec_total;
    $y = $project->share/$su_allocate->share_total;
    return -$x/$y;
}

// compute a score for this project, given user prefs.
// higher = more preferable
// -1 means don't use
//
function project_score($project, $ukws, $host, $req) {
    global $project_infos;
    $pkws = $project_infos[$project->id]->kws;

    $project->platform_score = platform_score($project, $host);
    $project->keyword_score = keywd_score($pkws, $ukws);
    $project->allocation_score = 0;
    if ($project->platform_score < 0 || $project->keyword_score < 0) {
        $project->score = -1;
        return $project;
    }

    // term for project allocation policy
    //
    $project->allocation_score = allocation_score($project);
    if ($req) {
        //log_write("alloc score: ec $x share $y score $project->allocation_score");
    }

    // TODO: give an edge to projects the host is already running?

    $s = $project->platform_score
        + $project->keyword_score
        + $project->allocation_score
    ;

    $project->score = $s;
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
            $host->resources[] = "amd";
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
//      set these fields in projects:
//          nrpc_failures: nonzero means last RPC failed
//          sched_req_no_work[]: resources where last sched req return no work
//
function rank_projects($user, $host, $req) {
    global $project_infos;
    $ukws = SUUserKeyword::enum("user_id=$user->id");
    $projects = SUProject::enum("status=".PROJECT_STATUS_AUTO);

    // get opted-out projects
    //
    $opt_out = array();
    $accts = SUAccount::enum("user_id=$user->id and opt_out>0");
    foreach ($accts as $a) {
        $opt_out[$a->project_id] = 1;
    }

    $host = populate_score_info($host, $req);
    $p2 = array();
    foreach ($projects as $p) {
        // skip old projects
        //
        if (!array_key_exists($p->id, $project_infos)) {
            continue;
        }

        $p = project_score($p, $ukws, $host, $req);
        $p->opt_out = false;
        if (array_key_exists($p->id, $opt_out)) {
            $p->score = -1;
            $p->opt_out = true;
        }
        if ($req) {
            // for RPCs, add small random factor for project variety
            // TODO: remove this; balance does this
            //
            if ($p->score < 0) continue;
            $p->score += rand(0, 10000)/1000000.;

            // look for starvation info in request
            //
            $p->sched_req_no_work = array();
            $p->nrpc_failures = 0;
            foreach ($req->project as $reqp) {
                if ((string)$reqp->url == $p->url) {
                    if ($reqp->nrpc_failures) {
                        $p->nrpc_failures = (int)$reqp->nrpc_failures;
                    }
                    foreach ($reqp->sched_req_no_work as $rsc) {
                        $p->sched_req_no_work[] = canonical_resource_name((string)$rsc);
                    }
                    break;
                }
            }
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
// select a list that uses all of the host's resources.
// For each project p, include an array p->use of resources that it should use.
//
function select_projects_resource($host, $projects) {
    global $in_rpc;

    $used = array();
    foreach ($host->resources as $r) {
        $used[$r] = false;
    }
    foreach ($projects as $p) {
        $p->included = false;
    }
    $p2 = array();
    foreach ($projects as $p) {
        if (LOG_PROJECT_SELECT && $in_rpc) {
            log_write("select: $p->name $p->score");
        }
        $p->use = array();
        $found = false;
        foreach ($used as $r => $u) {
            $p->use[$r] = false;
            if ($u) continue;
            if (can_use($p, $host, $r)) {
                $found = true;
                $p->use[$r] = true;

                // don't count this project as using this resource
                // if it hasn't supplied work for the resource recently
                //
                if ($in_rpc) {
                    if ($p->nrpc_failures) {
                        if (LOG_PROJECT_SELECT) {
                            log_write("select: $p->name can't use $r: RPC failures");
                        }
                    } else if (in_array($r, $p->sched_req_no_work)) {
                        if (LOG_PROJECT_SELECT) {
                            log_write("select: $p->name can't use $r: sched req didn't get work");
                        }
                    } else {
                        $used[$r] = true;
                        if (LOG_PROJECT_SELECT) {
                            log_write("select: $p->name can use $r");
                        }
                    }
                } else {
                    $used[$r] = true;
                }
            }
        }
        if ($found) {
            $p2[] = $p;
            $p->included = true;
            if (LOG_PROJECT_SELECT && $in_rpc) {
                log_write("select: adding $p->name");
            }
        }

        if (all_used($used)) {
            break;
        }
        if (count($p2) >= MAX_PROJECTS) {
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
        $found = false;
        foreach ($host->resources as $r) {
            $p->use[$r] = false;
            if (can_use($p, $host, $r)) {
                $p->use[$r] = true;
                $found = true;
            }
        }
        if ($found) {
            $p2[] = $p;
            if (LOG_PROJECT_SELECT && $in_rpc) {
                log_write("select: adding $p->name to reach MIN_PROJECTS");
            }
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
        $chosen_projects[] = $p;
        $n++;
        if ($n == 1) break;     // 1 is enough for now
    }
    return $chosen_projects;
}

// Decide what projects to have this user/host run.
// Called from AM RPC handler.
// Return list of accounts.
//
function choose_projects_rpc($user, $host, $req) {
    $projects = rank_projects($user, $host, $req);

    // select subset of projects that use all resources
    //
    $projects = select_projects_resource($host, $projects);

    $n = 0;
    $accounts_to_send = array();
    foreach ($projects as $p) {
        $account = SUAccount::lookup(
            "project_id = $p->id and user_id = $user->id"
        );
        if (!$account) {
            $ret = SUAccount::insert(
                sprintf("(project_id, user_id, create_time, authenticator, state) values (%d, %d, %f, '%s', %d)",
                    $p->id, $user->id, time(), $p->authenticator, ACCT_SUCCESS
                )
            );
            $account = SUAccount::lookup(
                "project_id = $p->id and user_id = $user->id"
            );
        }
        if (!$account) {
            log_write("no account");
            continue;
        }
        $accounts_to_send[] = array($p, $account);
        $n++;
        log_write("sending attach to $p->name");
        $x = "";
        foreach ($p->use as $r=>$v) {
            if ($v) $x .= "$r ";
        }
        log_write("   use: $x");
    }
    return $accounts_to_send;
}

function adjust($p, $inc) {
    log_write("adjusting avg_ec of $p->name: $inc");
    if ($p->nhosts == 0) {
        log_write("no adjustment; nhosts is 0");
        return;
    }
    $x = $p->avg_ec / $p->nhosts;
    if (!$inc) $x = -$x;
    $ret = $p->update("avg_ec_adjusted = avg_ec_adjusted + $x");
    log_write("$p->name: add $x to avg_ec");
    if (!$ret) {
        log_write("project update in avg_ec adjust failed");
    }
}

// decrement/increment avg_ec of outgoing/incoming projects
//
function adjust_avg_ec($current_projects, $accounts_to_send) {
    $old_ids = array();
    $new_ids = array();
    foreach ($current_projects as $p) {
        $old_ids[] = $p->id;
    }
    foreach ($accounts_to_send as $a) {
        $p = $a[0];
        $new_ids[] = $p->id;
    }
    foreach ($current_projects as $p) {
        if (!in_array($p->id, $new_ids)) {
            adjust($p, false);
        }
    }
    foreach ($accounts_to_send as $a) {
        $p = $a[0];
        if (!in_array($p->id, $old_ids)) {
            adjust($p, true);
        }
    }
}

?>
