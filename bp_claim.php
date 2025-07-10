<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2019 University of California
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

// let a user claim BOINC credit.
//
// 1) show a form where they pick a project and enter credentials
// 2) send an email w/ a code, and save code in user record
// 3) show a form for entering the code
// 4) fetch CPID, get credit info from netsoft
// 5) show user a page w/ credit info;
//      make DB records:
//      credit_claim
//          (userid, email, project_id, CPID, date)
//      credit_claim_project
//          credit_claim_id, project_id, credit, (user)id, 
//          index project_id, user_id
//
// what stops a user from changing the email address of all their BOINC
// accounts, then claiming credit again (possibly as a different user?)
//      after 1), if already a credit_claim record for that
//      email/project, reject
//      in 5), lookup each (project_id, user_id) in response.
//      If already a credit_claim_project record, reject

require_once("../inc/util.inc");
require_once("../inc/su_project_infos.inc");
require_once("../inc/bp_db.inc");

function lookup_project_id($url) {
    global $project_infos;
    foreach ($project_infos as $id=>$p) {
        if ($p->url == $url) {
            return $id;
        }
    }
    return 0;
}

// the user has filled out the confirm-email form
//
function action() {
    global $project_infos, $user;
    $passwd = post_str('passwd');
    $code = post_str('code');
    $cc_id = post_str('cc_id');

    $cc = BPCreditClaim::lookup_id($cc_id);
    if (!$cc || $cc->user_id != $user->id) {
        error_page("No credit claim");
    }

    if (strtolower($code) != $cc->code) {
        sleep(5);
        error_page("Invalid code");
    }
    $email_addr = $cc->email_addr;

    // check if anyone has successfully claimed this email
    //
    $cc2 = BPCreditClaim::lookup("email_addr='$email_addr' and status=2");
    if ($cc2) {
        error_page("Already claimed credit");
    }

    $project_id = $cc->project_id;
    $project = $project_infos[$project_id];

    // At this point everything looks OK.
    // do a lookup_account() RPC to get authenticator
    //
    $passwd_hash = md5($passwd.strtolower($email_addr));
    $url = $project->url."lookup_account.php?email_addr=$email_addr&passwd_hash=$passwd_hash";
    $x = url_get_contents($url);
    $e = parse_element($x, "<error_msg>");
    if ($e) {
        error_page("Error: $e");
    }
    $auth = parse_element($x, "<authenticator>");
    if (!$auth) {
        error_page("Couldn't get account info - try again later.");
    }

    // do a am_get_info() RPC to get the cross-project ID
    //
    $url = $project->url."am_get_info.php?account_key=$auth";
    $x = url_get_contents($url);
    $cpid = parse_element($x, "<cpid>");

    // get the project/credit info from netsoft
    //
    $url = "http://boinc.netsoft-online.com/get_user.php?cpid=".$cpid;
    $x = url_get_contents($url);
    if (!$x) {
        error_page("Can't get credit data - please try again later.");
    }
    //echo $x;

    $xml_object = @simplexml_load_string($x);
    $projects = @json_decode(json_encode((array)$xml_object))->project;

    // see if anyone has claimed credit for any of the accounts
    //
    foreach ($projects as $p) {
        $project_id = lookup_project_id($p->url);
        if (!$project_id) continue;
        $puid = $p->id;
        $ccp = BPCreditClaimProject::lookup(
            "project_id=$project_id and project_user_id=$puid"
        );
        if ($ccp) {
            error_page("Credit already claimed");
        }
    }

    page_head("Claimed credit for $email_addr");
    start_table();
    $total_credit = 0;
    foreach ($projects as $p) {
        $project_id = lookup_project_id($p->url);
        if (!$project_id) continue;
        $c = $p->total_credit;
        row2($p->name, $c);
        $total_credit += $c;
        $puid = $p->id;

        BPCreditClaimProject::insert(
            "(credit_claim_id, project_id, credit, project_user_id) values ($cc->id, $project_id, $c, $puid)"
        );
    }
    row2("Total credit", $total_credit);
    end_table();
    echo "<p>Next step: <a href=download_software.php>Download BOINC</a>";
    page_tail();

    $cc->update("status=2, total_credit=$total_credit");
}

// user filled out project/email form.
// check that this email isn't already claimed.
// then email them a code, and ask them to enter it
//
function code_form() {
    global $user;

    $project_id = post_str('project_id');
    $email_addr = post_str('email_addr');
    $passwd = post_str('passwd');

    // see if there's a completed claim for this email
    //
    $cc = BPCreditClaim::lookup("email_addr='$email_addr' and status=2");
    if ($cc) {
        error_page("Credit for $email_addr has already been claimed.");
    }

    // if user claimed this email earlier, use that DB record
    //
    $now = time();
    $cc = BPCreditClaim::lookup("email_addr='$email_addr' and user_id=$user->id");
    if ($cc) {
        $cc->update("create_time=$now, project_id=$project_id, status=0");
        $code = $cc->code;
        $cc_id = $cc->id;
    } else {
        $code = substr(random_string(), 0, 6);
        $cc_id = BPCreditClaim::insert(
            "(user_id, create_time, project_id, email_addr, code, status) values ($user->id, $now, $project_id, '$email_addr', '$code', 0)"
        );
    }

    $subject = PROJECT.": verify email address";
    $body = "Your email verification code is $code.
Please enter this code in your web browser.
";
    $ret = send_email(null, $subject, $body, null, $email_addr);
    if (!$ret) {
        error_page("Couldn't send email to $email_addr");
    }

    page_head("Validate email address");
    echo "
        <p>
        To confirm this email address,
        a code has been sent to $email_addr.
    ";
    form_start("bp_claim.php", "post");
    form_input_hidden('passwd', $passwd);
    form_input_hidden('cc_id', $cc_id);
    form_input_text("Please enter the code here:", 'code');
    form_submit('OK');
    form_end();
    page_tail();
}

function form() {
    global $project_infos;
    page_head("Claim BOINC credit");
    form_start("bp_claim.php", "post");
    $x = array();
    foreach ($project_infos as $id=>$p) {
        $x[] = array($id, $p->name);
    }
    form_select("Choose a BOINC project where you have an account:",
        'project_id', $x
    );
    form_input_text("Email address of that account:", 'email_addr');
    form_input_text("Password of that account:", 'passwd', '', 'password');
    form_submit('OK');
    form_end();
    page_tail();
}

function display_cc($cc) {
    global $project_infos;
    $ccps = BPCreditClaimProject::enum("credit_claim_id=$cc->id");
    $p = $project_infos[$cc->project_id];
    start_table();
    row2("Project", $p->name);
    row2("Email address", $cc->email_addr);
    foreach ($ccps as $ccp) {
        $p = $project_infos[$ccp->project_id];
        row2($p->name, $ccp->credit);
    }
    end_table();
}

function display() {
    global $user;
    page_head("Claimed credit");
    $ccs = BPCreditClaim::enum("user_id=$user->id and status=2");
    if (count($ccs) == 0) {
        echo "No claimed credit so far.";
    } else {
        foreach ($ccs as $cc) {
            display_cc($cc);
        }
    }
    echo "<a href=bp_claim.php?form=1>Claim more credit<a/>\n";
    page_tail();
}

function intro() {
    page_head("Claim BOINC credit");
    echo "
        <p>
        ".PROJECT."grants 'tokens' for your computing.
        If you're already running BOINC,
        you can convert your existing credit into tokens.
        Do you want to do this?
        <p>
        <a href=bp_claim.php?form=1>Yes - I'm already running BOINC<a/>.
        <p>
        <a href=download_software.php>No - I'm new to BOINC<a/>.
    ";
    page_tail();
}

$user = get_logged_in_user();

if (get_str('form', true)) {
    form();
} else if (get_str('display', true)) {
    display();
} else if (post_str('code', true)) {
    action();
} else if (post_str('project_id', true)) {
    code_form();
} else {
    intro();
}

?>
