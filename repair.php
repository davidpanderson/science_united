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

// one-time DB fixes

require_once("../inc/su_db.inc");
require_once("../inc/boinc_db.inc");

// fill in email_addr and passwd_has for old (non-anonymous) accounts
// DID THIS ONCE - DON'T DO AGAIN
//
function populate_accounts() {
    die;
    $accts = SUAccount::enum();
    foreach ($accts as $acct) {
        $user = BoincUser::lookup_id($acct->user_id);
        if (!$user) {
            echo "no such user: $acct->user_id\n";
            continue;
        }
        $query = sprintf("email_addr = '%s', passwd_hash='%s', name='%s'",
            $user->email_addr,
            $user->passwd_hash,
            $user->name
        );
        echo "$query\n";
        $ret = $acct->update($query);
        if (!$ret) {
            echo "update failed: $query\n";
        }
    }
}

//populate_accounts();

?>
