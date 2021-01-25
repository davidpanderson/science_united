# Science United
This repo is the source code for Science United (https://scienceunited.org),
an interface to BOINC-based volunteer computing.
Volunteers who use SU sign up for science areas (biomed, astronomy, etc.)
and/or geographical areas,
rather than for specific BOINC projects.
Science United is developed by the BOINC project at U.C. Berkeley,
led by David Anderson, and supported by the National Science Foundation.

This code may be useful as a starting point for other BOINC account managers,
especially those based on a similar
[coordinated model](https://scienceunited.org/doc/su_overview.pdf).
See [how to clone SU](docs/clone.md).

Science United can be used as a "back end" for systems that provide
other interfaces to BOINC-based computing.
Technical details:
* [RPC interfaces to Science United](doc/rpcs.md)
* [One-click "auto-attach" software download](doc/auto_attach.md)
* [Getting computing info for Science United users](doc/computing_info.md)

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
The SU code is in PHP, and consists of 3 main parts:
1. The handler for RPCs from clients (rpc.php).
1. Web page scripts.
1. Scripts for periodic back-end maintenance tasks.

SU piggy-backs on the BOINC web code.
It uses this for account creation, login, message boards, and web utility functions.

SU uses a MySQL database which is an extension of the BOINC database;
it adds tables for projects, accounts, and computational book-keeping info.
See schema.sql.


