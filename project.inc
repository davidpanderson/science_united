<?php

// This is a sample project file.
// Modify it to suit your project.
// Put your version in html/project/project.inc
// add tra() around visible strings to make them translatable
// see inc/translation.inc and https://boinc.berkeley.edu/trac/wiki/TranslateProject for Details

require_once("../inc/util.inc");

$master_url = parse_config(get_config(), "<master_url>");
//define("PROJECT", '<nobr><img style="vertical-align: text-top; height:.95em;" src=power.png>nboard</nobr>');
// other ideas: SciOn, Onsite, Onward
//define("PROJECT_WHITE", '<nobr><img style="vertical-align: text-top; height:1em;" src=power_white.png>nboard</nobr>');
define("PROJECT", "Science United");
define("URL_BASE", $master_url);
define("IMAGE_PATH", "../user_profile/images/");
define("IMAGE_URL", "user_profile/images/");
define("PROFILE_PATH", "../user_profile/");
define("PROFILE_URL", "user_profile/");
define("STYLESHEET", "custom.css");
define("STYLESHEET2", "su.css");
define("LANGUAGE_FILE", "languages.txt");
    // add your own stylesheets (included after bootstrap)
define("FORUM_QA_MERGED_MODE", true);
    // Set to true to merge Message boards and Q&A section
define("COPYRIGHT_HOLDER", "UC Berkeley");
define("SYS_ADMIN_EMAIL", "davea@berkeley.edu");
define("SHORTCUT_ICON", "power.png");
define("UOTD_ADMIN_EMAIL", "davea@berkeley.edu");
    // who gets user of the day pool running low e-mails?
define ("DISABLE_TEAMS", true);
define ("DISABLE_PROFILES", true);
    // enable profiles only after enabling reCAPTCHA
    // http://boinc.berkeley.edu/trac/wiki/ProtectionFromSpam
define("USE_STOPFORUMSPAM", true);
    // use http://www.stopforumspam.com to suppress spammer accounts
define("NO_COMPUTING", true);
define("USER_HOME", "su_home.php");
define("WELCOME_PAGE", "su_welcome.php");
//define("INVITE_CODES", '/foobar/');
define("PROJECT_ID", 100);
define("GLOBAL_HEAD_EXTRA", "
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src=\"https://www.googletagmanager.com/gtag/js?id=UA-130506275-1\"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'UA-130506275-1');
</script>
");

define("POST_REPORT_EMAILS", "davea@berkeley.edu");
    // Email addresses separated by pipe ( | ) that will receive user reports
    // of offensive forum posts.

define("RPC_DEBUG", false);
    // Enables PHP error messages in public Web RPCs

if (0) {
function make_php_mailer() {
    $mail = new PHPMailer();
    $mail->Mailer = 'smtp';
    $mail->Host = 'clarke.ssl.berkeley.edu';
    return $mail;
}
}

// If you use PHPMailer, uncomment the following
// and complete the function definition based on your SMTP server
// (not all fields may be needed)
//
if (0) {
function make_php_mailer() {
    $mail = new PHPMailer();
    $mail->IsSMTP();
    //$mail->Mailer = 'smtp';
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = "tls";
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587;
    $mail->Username = "john.doe@gmail.com";
    $mail->Password = "xxx";
        // Google's application-specific password,
        // if you are using the 2-Step Verification: 16 characters, no spaces.
        // OR: Put here the regular Gmail password if you are not using the
        // 2-Step Verification with your Gmail account.
        // See https://support.google.com/accounts/answer/185833?hl=en";
    $mail->SetFrom('admin@boincproject.com', 'John Doe');
    $mail->AddReplyTo("admin@boincproject.com", "John Doe");
    $mail->From = "admin@boincproject.com";
    return $mail;
}
}

function su_navbar_right($user) {
    global $is_login_page;
    echo '
      </ul>
      <ul class="nav navbar-nav navbar-right">
    ';
    if (!$is_login_page) {
        if ($user) {
            echo sprintf('
                <li><a href=%ssu_home.php>'.$user->name.'</a></li>
                ', url_base()
            );
            $url_tokens = url_tokens($user->authenticator);
            echo sprintf('<li><a href="%slogout.php?%s">%s</a></li>',
                url_base(), $url_tokens, tra("Log out")
            );
        } else {
            echo sprintf('
                <li><a href="%ssu_join.php">%s</a></li>
                <li><a href="%slogin_form.php">%s</a></li>
                ', url_base(), tra("Join"), url_base(), tra("Login")
            );
        }
    }
}
function su_navbar(
    $url_prefix,
        // prefix for links; needed for pages not in top dir
    $user,
        // logged-in user, if any
    $fixed=false,
        // if true, navbar is fixed at top of page.
        // NOTE: if you do this, you must set a global var $fixed_navbar
        // to true at compile time
        // (it needs to be set when page_head() is called).
    $inverse=false,
        // white on black?
    $is_main
) {
    global $master_url;

    if ($is_main) {
        $logo = "Science United";
    } else {
        $logo = "<nobr><img style=\"vertical-align:text-bottom;\" src=power.png height=20> Science United</nobr>";
    }
    $brand = "<a class=\"navbar-brand\" href=$master_url>$logo</a>";
    navbar_start($brand, $fixed, $inverse);

    $x = array();
    $x[] = array(tra("About %1", PROJECT), $url_prefix."su_about.php");
    $x[] = array(tra("Help"), $url_prefix."su_help.php");
    navbar_menu(tra("Project"), $x);

    navbar_menu(tra("Community"), array(
        array(tra("Message boards"), $url_prefix."forum_index.php"),
        //array(tra("Questions and Answers"), $url_prefix."forum_help_desk.php"),
        //array(tra("Teams"), $url_prefix."team.php", tra("create or join a team")),
        //array(tra("Profiles"), $url_prefix."profile_menu.php"),
        //array(tra("User search"), $url_prefix."user_search.php"),
        //array(tra("User of the day"), $url_prefix."uotd.php"),
    ));
    navbar_menu(tra("Site"), array(
        array(tra("Search"), $url_prefix."site_search.php"),
        array(tra("Languages"), $url_prefix."language_select.php")
    ));

    // add your own menu here if you want

    su_navbar_right($user);
    navbar_end();
}

// Put your project title and logo here
// If you include any links, prepend URL with $prefix
// if you need to open divs here close them in project_footer()
//
function project_banner($title, $prefix, $is_main) {
    if ($is_main) {
        echo '<p><center><font size=+6>
<img style="vertical-align: sub; height:1.2em;" src=power.png>
            '.PROJECT.'</font>';
        echo '<br><font size=+1>'.tra('Do science research on your computer, tablet, or phone').'</font></center>
            <p>
        ';
        //echo '<center><img class="img-responsive" src="logo.png"></center>';
    }
    su_navbar(secure_url_base(), get_logged_in_user(false), false, false, $is_main);
    if ($title && !$is_main) {
        echo "<h2>$title</h2>\n";
    }
}
//$fixed_navbar = true;

function project_footer($show_return, $show_date, $prefix) {
    // If you include any links, prepend URL with $prefix
    //
    echo '<br><br>
        <p class="text-center"> &copy; '.gmdate("Y ").COPYRIGHT_HOLDER.'</p>
    ';
    if ($show_date) {
        $t = time_str(time());
        echo "<center><small>".tra("Generated")." $t</small><center>\n";
    }
}

// Authorize access to administrative pages.
// You can check for login, IP address, or whatever you want.
//
function auth_ops_example() {
    // if running from cmdline, skip checks
    //
    if (!$_SERVER['REMOTE_ADDR']) {
        return;
    }

    // example: require login as a particular user (id 1 in this case)
    //
    if (0) {
        auth_ops_userid(array(1));
        return;
    }

    // example: require that logged-in user have ADMIN or DEV flags
    // set in their forum_prefs.privilege
    //
    if (0) {
        auth_ops_privilege();
        return;
    }
}

function show_profile_heading1() {
    return tra("Your personal background.");
}

function show_profile_question1() {
    return tra("Tell us about yourself. You could tell us where you're from, your age, occupation, hobbies, or anything else about yourself.");
}

function show_profile_heading2() {
    return tra("Your opinions about %1", PROJECT);
}

function show_profile_question2() {
    return tra("Tell us your thoughts about %1<ol>
    <li>Why do you run %1?
    <li>What are your views about the project?
    <li>Any suggestions?
    </ol>", PROJECT);
}

function project_workunit($wu){
    // shown in the workunit page
}

function project_user_summary($user){
    // shown in the user summary page
}

function project_user_page_private($user){
    // shown in the private account page
}

if (0) {

// show project-specific credit on user/team pages
//
function show_app_credit_user($user, $app_name, $appids) {
    $t = 0;
    $a = 0;
    $n = 0;
    foreach ($appids as $appid) {
        $cu = BoincCreditUser::lookup(
            "userid=$user->id and appid=$appid and credit_type=0"
        );
        if ($cu) {
            $t += $cu->total;
            $a += $cu->expavg;
            $n += $cu->njobs;
        }
    }
    row2("$app_name credit",
        format_credit_large($t)." total, ".
        format_credit($a)." average".
        " ($n tasks)"
    );
}

function show_app_credit_team($team, $app_name, $appids) {
    $t = 0;
    $a = 0;
    $n = 0;
    foreach ($appids as $appid) {
        $ct = BoincCreditTeam::lookup(
            "teamid=$team->id and appid=$appid and credit_type=0"
        );
        if ($ct) {
            $t += $ct->total;
            $a += $ct->expavg;
            $n += $ct->njobs;
        }
    }
    row2("$app_name credit",
        format_credit_large($t)." total, ".
        format_credit($a)." average".
        " ($n tasks)"
    );
}

// a list of "sub-projects", used in the display of per-app credit and badges.
// A subproject is:
// - a set of 1 or more apps; an app can belong to at most 1 subproject.
// - a name, shown on the web site
// - a short name, used in badge names.  Don't use "total".
//
$sub_projects = array(
    array("name" => "Remote Test", "short_name" => "RT", "appids" => array(16)),
    array("name" => "Uppercase", "short_name" => "UC", "appids" => array(1, 25)),
);

function project_user_credit($user){
    global $sub_projects;
    foreach ($sub_projects as $sp) {
        show_app_credit_user($user, $sp["name"], $sp["appids"]);
    }
}

function project_team_credit($team) {
    global $sub_projects;
    foreach ($sub_projects as $sp) {
        show_app_credit_team($team, $sp["name"], $sp["appids"]);
    }
}

}   // if(0)

function project_forum_post_rules() {
    // additional forum post rules
    return "";
}

?>
