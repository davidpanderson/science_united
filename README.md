# Science United
This repo is the source code for Science United (https://scienceunited.org),
an interface to BOINC-based volunteer computing.
Volunteers who use SU sign up for science areas (biomed, astronomy, etc.)
and/or geographical areas,
rather than for specific projects.

This code may be useful as a starting point for other BOINC account managers,
especially those based on a similar
[coordinated model](https://scienceunited.org/doc/model.pdf).

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
It uses BOINC's code for login, account creation, message boards, and web utility functions.

SU uses a database which is an extension of the BOINC database;
it adds tables for projects, accounts, and computational book-keeping info.
See schema.sql.

If you want to make a clone of Science United, the steps are:
1. Download the BOINC source code.
1. Use **make_project** to create a project.
1. Run SU's schema.sql to add SU's DB tables.
1. Copy or link SU's web files to the appropriate subdirectories
   of the project's html/ directory.
   .inc files go in html/inc/;
   maintenance scripts go in html/ops/ (allocate.php, do_accounting.php, su_delete_spammers, su_email.php).
   Other .php files go in html/user/.
