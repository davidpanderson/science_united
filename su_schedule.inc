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

// compute a score for this project, given user prefs.
// higher = more preferable
// -1 means don't use
//
// Currently based just on keywords.
// TODO: take GPUs into account?
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

    // TODO: give an edge to projects the host is already running
}

// return list of projects ordered by descending score
//
function rank_projects($user, $host=null) {
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

// decide what projects to have this user run.
// called when account is first created.
// we don't know anything about host at this point.
//
function choose_projects_join($user) {
    $projects = rank_projects($user);
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
// Return list of accounts that are confirmed.
//
function choose_projects_rpc($user, $host) {
    $projects = rank_projects($user, $host);
    $n = 0;
    $accounts_to_send = array();
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
        }
    }
    return $accounts_to_send;
}

?>
