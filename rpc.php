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

require_once("../inc/xml.inc");
require_once("../inc/boinc_db.inc");

require_once("../inc/su_db.inc");

$now = 0;

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

    // TODO: give an edge to projects the host is already running
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
function send_reply($host, $accounts) {
    echo "<acct_mgr_reply>\n"
        ."<name>Science United</name>\n"
        ."<signing_key>\n"
    ;
    readfile('code_sign_public');
    echo "</signing_key>\n"
        ."<repeat_sec>86400</repeat_sec>\n"
    ;
    foreach ($accounts as $a) {
        $proj = $a[0];
        $acct = $a[1];
        echo "<account>\n"
            ."<url>$proj->url</url>\n"
            ."<url_signature>\n$proj->url_signature\n</url_signature>\n"
            ."<authenticator>$acct->authenticator</authenticator>\n"
            ."</account>\n"
        ;
    }
    echo "</acct_mgr_reply>\n";
}

function make_serialnum($hi) {
}

function update_host($req, $host) {
    global $now;
    $hi = $req->host_info;
    $ts = $req->time_stats;
    $timezone = (int)$hi->timezone;
    $domain_name = BoincDb::escape_string((string)$hi->domain_name);
    $host_cpid = BoincDb::escape_string((string)$req->host_cpid);
    $serialnum = make_serialnum($hi);
    $last_ip_addr = BoincDb::escape_string($_REQUEST['ip_addr']);
    $on_frac = (double)$ts->on_frac;
    $connected_frac = (double)$ts->connected_frac;
    $active_frac = (double)$ts->active_frac;
    $p_ncpus = (int)$hi->p_ncpus;
    $p_vendor = BoincDb::escape_string((string)$hi->p_vendor);
    $p_model = BoincDb::escape_string((string)$hi->p_model);
    $p_features = BoincDb::escape_string((string)$hi->p_features);
    $p_fpops = (double)$hi->p_fpops;
    $p_iops = (double)$hi->p_iops;
    $p_vm_extensions_disabled = (int)$hi->p_vm_extensions_disabled;
    $m_nbytes = (double)$hi->m_nbytes;
    $m_cache = (double)$hi->m_cache;
    $m_swap = (double)$hi->m_swap;
    $d_total = (double)$hi->d_total;
    $d_free = (double)$hi->d_free;
    $os_name = BoincDb::escape_string((string)$hi->os_name);
    $os_version = BoincDb::escape_string((string)$hi->os_version);
    $host->update("
        rpc_time = $now,
        timezone = $timezone,
        domain_name = '$domain_name',
        serialnum = '$serialnum',
        last_ip_addr = '$last_ip_addr',
        on_frac = $on_frac,
        connected_frac = $connected_frac,
        active_frac = $active_frac,
        p_ncpus = $p_ncpus,
        p_vendor = '$p_vendor',
        p_model = '$p_model',
        p_features = '$p_features',
        p_fpops = $p_fpops,
        p_iops = $p_iops,
        p_vm_extensions_disabled = $p_vm_extensions_disabled,
        m_nbytes = $m_nbytes,
        m_cache = $m_cache,
        m_swap = $m_swap,
        d_total = $d_total,
        d_free = $d_free,
        os_name = '$os_name',
        os_version = '$os_version'
    ");
}

function create_host($req) {
    $now = time();
    $id = BoincHost::insert(
        "(create_time, userid, host_cpid) values ($now, $user->id, '$host_cpid')"
    );
    $host = BoincHost::lookup_id($id);
    $host->update($req);
    return $host;
}

// look up user and host records; create host if needed
//
function lookup_records($req) {
    $email_addr = (string)$req->name;
    $user = BoincUser::lookup_email_addr($email_addr);
    if (!$user) {
        xml_error(-1, 'no account found');
    }

    $passwd_hash = (string)$req->password_hash;
    if ($passwd_hash != $user->passwd_hash) {
        xml_error(-1, 'bad password');
    }

    $host_id = (string)$req->opaque->host_id;
    $host = BoincHost::lookup_id($host_id);
    if ($host) {
        update_host($req, $host);
    } else {
        $host = create_host($req);
    }
}

//
function check_time_delta($dt, $delta, $is_gpu) {
    if ($delta < 0) return 0;
    $max_inst = $is_gpu?8:256;
    $max_delta = $max_inst*dt;
    return min($delta, $max_delta);
}

function check_ec_delta($dt, $delta, $is_gpu) {
    if ($delta < 0) return 0;
    $max_inst = $is_gpu?8:256;
    $max_rate = $is_gpu?100e12/COBBLESTONE_FACTOR:100e9/COBBLESTONE_FACTOR;
    $max_delta = $max_inst*$max_rate*dt;
    return min($delta, $max_delta);
}

// compute deltas for CPU/GPU time and flops.
// update host record.
// create accounting_user record if needed.
// update total, project, host accounting records
//
function do_accounting($req, $user, $host) {
    global $now;
    $dt = $now = $host->last_rpc_time;
    if ($dt < 0) {
        return;
    }
    $cpu_time = 0;
    $cpu_ec = 0;
    $gpu_time = 0;
    $gpu_ec = 0;
    $delta_cpu_time = 0;
    $delta_cpu_ec = 0;
    $delta_gpu_time = 0;
    $delta_gpu_ec = 0;
    foreach($req->project as $rp) {
        $url = (string)$rp->url;
        $project = SUProject::lookup("url=$url");
        if (!$project) {
            continue;
        }
        $d_cpu_time = (double)$rp->cpu_time - $host->cpu_time;
        $d_cpu_time = check_time_delta($dt, $d_cpu_time, false);
        $d_cpu_ec = (double)$rp->cpu_ec - $host->cpu_ec;
        $d_cpu_ec = check_ec_delta($dt, $d_cpu_ec, false);
        $d_gpu_time = (double)$rp->gpu_time - $host->gpu_time;
        $d_gpu_time = check_time_delta($dt, $d_gpu_time, true);
        $d_gpu_ec = (double)$rp->gpu_ec - $host->gpu_ec;
        $d_gpu_ec = check_ec_delta($dt, $d_gpu_ec, true);

        $d_cpu_time += $d_cpu_time;
        $d_cpu_ec += $d_cpu_ec;
        $d_gpu_time += $d_gpu_time;
        $d_gpu_ec += $d_gpu_ec;

        $cpu_time += (double)$rp->cpu_time;
        $cpu_ec += (double)$rp->cpu_ec;
        $gpu_time += (double)$rp->gpu_time;
        $gpu_ec += (double)$rp->gpu_ec;
    }
    $host->update(
        "cpu_time=$cpu_time, cpu_ec=$cpu_ec, gpu_time=$gpu_time, gpu_ec=$gpu_ec, last_rpc_time=$now"
    );

    SUAccounting::update(
        "cpu_time=cpu_time+$delta_cpu_time, cpu_ec=cpu_ec+$delta_cpu_ec, gpu_time=gpu_time+$delta_gpu_time, gpu_ec=gpu_ec+$delta_gpu_ec",
        "create_time=(max(create_time) from su_accounting"
    );
}

// decide what projects to have this host run.
//
function choose_projects($user, $host) {
    $projects = rank_projects($user, $host);
    $n = 0;
    $accounts_to_send = array();
    foreach ($projects as $p) {
        $account = SUAccount::lookup(
            "project_id = $p->id and user_id = $user->id"
        );
        if ($account) {
            if ($account->state == SUCCESS) {
                $accounts_to_send[] = array($p, $account);
                $n++;
                if ($n == 3) break;
            } else {
                continue;
            }
        } else {
            $ret = SUAccount::insert(
                sprintf("(project_id, user_id, state) values (%d, %d, %d)",
                    $p->id, $user->id, INIT
                )
            );
            if (!$ret) {
                xml_error(-1, "account insert failed");
            }
        }
    }
    return $accounts_to_send;
}

function main() {
    global $now;

    //$req = simplexml_load_file('php://input');
    $req = simplexml_load_file('req.xml');
    if (!$req) {
        xml_error(-1, "can't parse request");
    }

    $now = time();

    xml_header();   // do this before DB access

    list($user, $host) = lookup_records($req);
    do_accounting($req, $user, $host);
    $accounts_to_send = choose_projects($user, $host);
    send_reply($host, $accounts_to_send);
}

main();

?>
