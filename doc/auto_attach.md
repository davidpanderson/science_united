# One click "auto-attach" software download

If you use Science United as a back end from your front-end web site,
and you create SU accounts for your users,
you can provide link, on your web site, for downloading BOINC in such a way that

* The user doesn't leave your web site; they don't see either the SU or BOINC web sites.
* When the user completes the download and installs and runs BOINC,
they are "auto-attached" to their corresponding SU account;
the BOINC client doesn't show them dialogs for choosing projects.

Note: this works only for Windows and Mac computers.
For others (Linux and Android) you can provide download info on your site
or direct them to the download page on the BOINC web site;
but in case they'll have to manually attach to Science United from the BOINC client.

To use this mechanism, the "download" page on your web needs to:

## 1) Do an RPC to get download info

To create the Download button,
you'll need some information that depends on the user ID and the computer type.
You get this from SU using the **download_software()** RPC,
which is documented
[here](https://boinc.berkeley.edu/trac/wiki/WebRpc#download).

If your site is in PHP, you can use the PHP binding of this RPC
in the BOINC file [web_rpc_api.inc](https://github.com/BOINC/boinc/blob/master/html/inc/web_rpc_api.inc).

## 2) Use this info to create Download buttons

The **download_software()** RPC returns an XML structure that looks like this:
```
<download_info>
    <project_id>101</project_id>
<token>c991c11f</token>
<user_id>56234</user_id>
<platform>Windows 64-bit</platform>
<installer>
    <filename>boinc_7.16.11_windows_x86_64.exe</filename>
    <size>8.98 MB</size>
    <versions>BOINC 7.16.11</versions>
</installer>
<installer_vbox>
    <filename>boinc_7.16.11_windows_x86_64_vbox.exe</filename>
    <size>113.90 MB</size>
    <versions>BOINC 7.16.11, VirtualBox 6.1.12</versions>
<installer_vbox>
</download_info>
```

The script for your Download page should take these items and generate something like
```
<form action="https://boinc.berkeley.edu/concierge.php" method="post">
        <input type=hidden name=project_id value="101">
        <input type=hidden name=token value="c991c11f">
        <input type=hidden name=user_id value="56234">
        <input type=hidden name=filename value="boinc_7.16.11_windows_x86_64_vbox.exe">
        <button class="btn btn-success">
        <font size=+1><u>Download BOINC + VirtualBox</u></font>
        <br>for Windows 64-bit (113.90 MB)
        <br><small>BOINC 7.16.11, VirtualBox 6.1.12</small></a>
 </form>

<form action="https://boinc.berkeley.edu/concierge.php" method="post">
        <input type=hidden name=project_id value="101">
        <input type=hidden name=token value="c991c11f">
        <input type=hidden name=user_id value="56234">
        <input type=hidden name=filename value="boinc_7.16.11_windows_x86_64.exe">
        <button class="btn btn-info">
        <font size=2><u>Download BOINC</u></font>
        <br>for Windows 64-bit (8.98 MB)
        <br><small>BOINC 7.16.11</small></button>
 </form>
```

But don't worry - all these details are taken care of for you in example code in
[web_rpc_api.inc](https://github.com/BOINC/boinc/blob/master/html/inc/web_rpc_api.inc).
We recommend that you use this code.
