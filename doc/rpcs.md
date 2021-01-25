# Web RPC interfaces to Science United

Science United can be used as a computational back end for other systems.
You can make a web site where people sign up, download BOINC, and compute.
Maybe they do other things too, and maybe you reward them in some way
for computing and/or other activities.
You can use SU to manage the computing part of this,
and you can do this in a way where people interact only with your web site,
not with Science United or BOINC.

Doing this involves using a set of "Web RPCs" the let you create,
manage, and monitor Science United user accounts.

Some of these RPCs require security tokens issued by SU.
The ''create_account'' RPC requires an "invite code",
and some of the other RCPs require an "RPC key".
You can get these from [SU admins](https://scienceunited.org/su_about.php)
(after convincing us that what you're doing is legit and consistent with our goals).

Because SU is based on the BOINC web code, it supports most of the
[BOINC web RPCs](https://boinc.berkeley.edu/trac/wiki/WebRpc).
At the very least you'll need the ''create_account'' RPC.

In addition, SU provides the following RPCs:

## Getting keyword preferences

SU uses *keyword preferences* to let users choose the areas of science,
or the geographical/institutional entities, that they do or don't want to support.
The set of keywords is [here](https://boinc.berkeley.edu/keywords.php?header=html).

URL: https://scienceunited.org/su_rpc.php?action=get_keywords&rpc_key=X&auth=X

Action: get a user's keyword preferences

Arguments:

* rpc_key: your RPC key
* auth: the user's authenticator

Output: \<error>, or an XML document of the form
```
<keywords>
   <keyword>
      <id>3</id>
      <yesno>1</yesno>
   </keyword>
   ...
</keywords>
```

**yesno** is 1 for "yes", -1 for "no".

## Setting keyword preferences

URL: https://scienceunited.org/su_rpc.php?action=get_keywords&rpc_key=X&auth=X&p1=v1&...&pn=vn

Action: set a user's keyword preferences

Arguments:

* rpc_key: your RPC key
* auth: the user's authenticator
* p1 ... pn: a list of keyword IDs. Each one has a correspending value v1 ... vn,
which is either 1 (yes) or -1 (no).
The user's preferences are set to this list;
preferences for keywords not in the list are removed.

Output: \<success> or \<error>
