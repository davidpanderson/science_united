# Science United
This repo is the source code for Science United (https://scienceunited.org),
an interface to BOINC-based volunteer computing.
Volunteers who use SU sign up for science areas (biomed, astronomy, etc.)
and/or geographical areas,
rather than for specific projects.

This code may be useful as a starting point for other BOINC account managers,
especially those based on a similar
[coordinated model](https://scienceunited.org/doc/su_overview.pdf).

Science United is developed by the BOINC project at U.C. Berkeley,
led by David Anderson, and supported by the National Science Foundation.

## Contribute

If you're a programmer or web designer and would like to contribute to Science United,
that would be great!
To get started:
1. Use Science United and explore its features.
1. Read through the source code.
1. Create issues for any bugs or suggested changes you find.  Discuss with us.
1. Once the approach for an issue has been decided, create a branch and pull request.

## Implementation notes

SU is implemented as a BOINC [account manager](https://boinc.berkeley.edu/trac/wiki/AccountManagement).
The SU code consists of 3 main parts:
1. The handler for RPCs from clients (rpc.php).
1. Web page scripts.
1. Scripts for periodic back-end maintenance tasks.

SU piggy-backs on the BOINC web code.
It uses BOINC's code for account creation, login, message boards, and web utility functions.

SU uses a MySQL database which is an extension of the BOINC database;
it adds tables for projects, accounts, and computational book-keeping info.
See schema.sql.

## Cloning Science United

If you want to make a clone of SU, the steps are:
1. Download the BOINC source code.
1. Use **make_project --web_only** to create a project, say 'su_clone'.
1. Run SU's schema.sql to add SU's DB tables: **mysql su_clone < schema.sql**
1. Copy or link SU's web files to the appropriate subdirectories
   of  projects/su_clone/html/.
   .inc files go in html/inc/;
   maintenance scripts go in html/ops/.
   Other .php files go in html/user/.
   You can use 'copy_files' to do this.
1. Add to the project's config.xml: `<account_manager/>`
1. Use BOINC'c [crypt_prog](https://boinc.berkeley.edu/trac/wiki/CodeSigning) to generate a key pair, code_sign_private and code_sign_public, in su_clone/keys/.
1. Get the list of BOINC projects: wget https://boinc.berkeley.edu/project_list.php.  Put the result in html/ops/projects.xml.
1. In html/inc, wget https://boinc.berkeley.edu/project_ids.inc
1. In html/ops, run project_init.php.  This will populate the su_project table.
1. In html/ops, run project_digest.php.  This creates projects.ser, a serialized file of project info.

Create an account for yourself.
Visit the message boards to create a forum_preferences entry for that user.
Then (using mysql) set the 'special_user' field of that entry to '01'; that makes
the account administrative.
Then go to su_manager.php to see the admin web interface.

## Project accounts

The code supports two modes of operation:
1. A separate account is created on each BOINC project for each SU user.
1. All SU users share a single account on each BOINC project.

Originally SU used the first mode;
it switched to the second mode because of GDPR privacy issues,
and because it's much simpler.

To use the first mode, put this in your config.xml:
>        <task>
>            <cmd> run_in_ops ./make_accounts.php --timer</cmd>
>            <period> 5 minutes </period>
>            <disabled> 1 </disabled>
>            <output> make_accounts.out </output>
>        </task>

To use the second mode:
create an account on each BOINC project you want to support.
Store the *WEAK* authenticator of the account in the "authenticator" field
of the project's entry in the su_project table.

## Resource allocation among projects

SU tries to divide computing fairly among projects.
This mechanism is embodied in its project assignment algorithm (rpc.php).
Projects can be assigned different "shares",
which you can edit in the admin web interface.
To enable this mechanism, put the following in your config.xml:

>        <task>
>            <cmd> run_in_ops ./allocate.php </cmd>
>            <period> 24 hours </period>
>            <disabled> 0 </disabled>
>            <output> allocate.out </output>
>        </task>

## Accounting

SU keeps track of how much computing each device has done,
in terms of "estimated credit" reported by BOINC clients.
To enable this, include the following in your config.xml:

>        <task>
>            <cmd> run_in_ops ./do_accounting.php </cmd>
>            <period> 24 hours </period>
>            <disabled> 0 </disabled>
>            <output> do_accounting.out </output>
>        </task>

## Custom functionality

The SU code supports the model where users choose keywords,
and project assignment is based on keywords.

With a certain amount of work, SU could be changed to the
model where users explicitly choose projects (like BAM! and Gridrepublic).
You'd need to change a number of files, starting with rpc.php.
