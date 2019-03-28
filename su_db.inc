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

// SU-specific tables.
// In addition we use some fields from BOINC tables:
//
// host.rpc_time
//      last AM RPC
// host.total_credit
//      -1 means host is hidden
// user.send_email
//      days between status emails.  zero means don't send
// user.seti_last_result_time
//      when to send next status email

require_once("../inc/boinc_db.inc");

// values for keyword.category
//
define('SCIENCE', 0);
define('LOCATION', 1);

// values for user_keyword.type
//
define('KW_YES', 1);
define('KW_MAYBE', 0);
define('KW_NO', -1);

// values for su_account.state
//
define('ACCT_INIT', 0);
define('ACCT_SUCCESS', 1);
define('ACCT_TRANSIENT_ERROR', 3);

// values of keyword.priority
//
define('KW_MINOR', 1);
define('KW_MAJOR', 2);

// values of project.status
//
define('PROJECT_STATUS_HIDE', 0);
    // don't show; deprecated
define('PROJECT_STATUS_AUTO', 2);
    // normal

class SUProject {
    static function insert($clause) {
        $db = BoincDb::get();
        $ret = $db->insert('su_project', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function lookup_id($id) {
        $db = BoincDb::get();
        return $db->lookup_id($id, 'su_project', 'SUProject');
    }
    function update($clause) {
        $db = BoincDb::get();
        return $db->update($this, 'su_project', $clause);
    }
    function delete() {
        $db = BoincDb::get();
        return $db->delete($this, 'su_project');
    }
    static function enum($where_clause=null) {
        $db = BoincDb::get();
        return $db->enum('su_project', 'SUProject', $where_clause);
    }
    static function lookup($clause) {
        $db = BoincDb::get();
        return $db->lookup('su_project', 'SUProject', $clause);
    }
}

class SUProjectKeyword {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('su_project_keyword', $clause);
    }
    static function enum($where_clause=null) {
        $db = BoincDb::get();
        return $db->enum('su_project_keyword', 'SUProjectKeyword', $where_clause);
    }
    function delete() {
        $db = BoincDb::get();
        return $db->delete_aux('su_project_keyword',
            "project_id=$this->project_id and keyword_id=$this->keyword_id"
        );
    }
    static function delete_all() {
        $db = BoincDb::get();
        return $db->delete_aux('su_project_keyword', 1);
    }
}

class SUUserKeyword {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('su_user_keyword', $clause);
    }
    static function enum($where_clause=null) {
        $db = BoincDb::get();
        return $db->enum('su_user_keyword', 'SUUserKeyword', $where_clause);
    }
    function delete() {
        $db = BoincDb::get();
        return $db->delete_aux('su_user_keyword',
            "user_id=$this->user_id and keyword_id=$this->keyword_id"
        );
    }
    static function delete_user($user_id) {
        $db = BoincDb::get();
        return $db->delete_aux('su_user_keyword', "user_id=$user_id");
    }
    static function update($clause) {
        $db = BoincDb::get();
        return $db->update_aux('su_user_keyword', $clause);
    }
    static function delete_all() {
        $db = BoincDb::get();
        return $db->delete_aux('su_user_keyword', 1);
    }
}

class SUAccount {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('su_account', $clause);
    }
    static function enum($where_clause=null, $order_clause=null) {
        $db = BoincDb::get();
        return $db->enum('su_account', 'SUAccount', $where_clause, $order_clause);
    }
    static function lookup($where_clause) {
        $db = BoincDb::get();
        return $db->lookup('su_account', 'SUAccount', $where_clause);
    }
    function update($clause) {
        $db = BoincDb::get();
        $clause .= " where user_id=$this->user_id and project_id=$this->project_id";
        return $db->update_aux('su_account', $clause);
    }
    function delete() {
        $db = BoincDb::get();
        return $db->delete_aux('su_account',
            "user_id=$this->user_id and project_id=$this->project_id"
        );
    }
    static function delete_user($user_id) {
        $db = BoincDb::get();
        return $db->delete_aux('su_account', "user_id=$user_id");
    }
    static function delete_all() {
        $db = BoincDb::get();
        return $db->delete_aux('su_account', 1);
    }
}

class SUHostProject {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('su_host_project', $clause);
    }
    static function lookup($where_clause) {
        $db = BoincDb::get();
        return $db->lookup('su_host_project', 'SUHostProject', $where_clause);
    }
    static function update($clause) {
        $db = BoincDb::get();
        return $db->update_aux('su_host_project', $clause);
    }
    static function enum($where_clause, $order_clause=null) {
        $db = BoincDb::get();
        return $db->enum('su_host_project', 'SUHostProject', $where_clause, $order_clause);
    }
    static function delete_all() {
        $db = BoincDb::get();
        return $db->delete_aux('su_host_project', 1);
    }
}

class SUAccounting {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('su_accounting', $clause);
    }
    static function enum($where_clause='', $order_clause='') {
        $db = BoincDb::get();
        return $db->enum('su_accounting', 'SUAccounting', $where_clause, $order_clause);
    }
    static function last($i=0) {
        $db = BoincDb::get();
        $n = $i+1;
        $x = SUAccounting::enum("", "order by id desc limit $n");
        if ($x && count($x) > $i) {
            return $x[$i];
        } else {
            return null;
        }
    }
    function update($clause) {
        $db = BoincDb::get();
        return $db->update($this, 'su_accounting', $clause);
    }
    static function delete_all() {
        $db = BoincDb::get();
        return $db->delete_aux('su_accounting', 1);
    }
}

class SUAccountingProject {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('su_accounting_project', $clause);
    }
    static function enum($where_clause, $order_clause=null) {
        $db = BoincDb::get();
        return $db->enum('su_accounting_project', 'SUAccountingProject', $where_clause, $order_clause);
    }
    static function last($proj_id) {
        $db = BoincDb::get();
        $x = SUAccountingProject::enum("project_id=$proj_id order by id desc limit 1");
        if ($x) {
            return $x[0];
        } else {
            return null;
        }
    }
    function update($clause) {
        $db = BoincDb::get();
        return $db->update($this, 'su_accounting_project', $clause);
    }
    static function delete_all() {
        $db = BoincDb::get();
        return $db->delete_aux('su_accounting_project', 1);
    }
}

class SUAccountingUser {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('su_accounting_user', $clause);
    }
    static function enum($where_clause, $order_clause=null) {
        $db = BoincDb::get();
        return $db->enum('su_accounting_user', 'SUAccountingUser', $where_clause, $order_clause);
    }
    static function last($user_id) {
        $db = BoincDb::get();
        $x = SUAccountingUser::enum("user_id=$user_id order by id desc limit 1");
        if ($x) {
            return $x[0];
        } else {
            return null;
        }
    }
    function update($clause) {
        $db = BoincDb::get();
        return $db->update($this, 'su_accounting_user', $clause);
    }
    static function count($clause) {
        $db = BoincDb::get();
        return $db->count('su_accounting_user', $clause);
    }
    static function delete_user($user_id) {
        $db = BoincDb::get();
        return $db->delete_aux('su_accounting_user', "user_id=$user_id");
    }
    static function delete_all() {
        $db = BoincDb::get();
        return $db->delete_aux('su_accounting_user', 1);
    }
}

class SUAllocate {
    static function lookup() {
        $db = BoincDb::get();
        return $db->lookup('su_allocate', 'SUAllocate', "true");
    }
    static function update($clause) {
        $db = BoincDb::get();
        return $db->update_aux('su_allocate', $clause);
    }
}
