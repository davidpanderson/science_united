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

require_once("../inc/boinc_db.inc");

class BPCreditClaim {
    static function insert($clause) {
        $db = BoincDb::get();
        $db->insert('bp_credit_claim', $clause);
        return $db->insert_id();
    }
    static function lookup($clause) {
        $db = BoincDb::get();
        return $db->lookup('bp_credit_claim', 'BPCreditClaim', $clause);
    }
    static function lookup_id($id) {
        $db = BoincDb::get();
        return $db->lookup_id($id, 'bp_credit_claim', 'BPCreditClaim');
    }
    function update($clause) {
        $db = BoincDb::get();
        return $db->update($this, 'bp_credit_claim', $clause);
    }
    static function enum($where_clause=null) {
        $db = BoincDb::get();
        return $db->enum('bp_credit_claim', 'BPCreditClaim', $where_clause);
    }
}

class BPCreditClaimProject {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('bp_credit_claim_project', $clause);
    }
    static function lookup($clause) {
        $db = BoincDb::get();
        return $db->lookup('bp_credit_claim_project', 'BPCreditClaimProject', $clause);
    }
    static function enum($where_clause=null) {
        $db = BoincDb::get();
        return $db->enum('bp_credit_claim_project', 'BPCreditClaimProject', $where_clause);
    }
}
