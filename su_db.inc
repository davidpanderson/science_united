<?php

// SU-specific tables.
// In addition we use some fields from BOINC tables:
//
// host.rpc_time
// host.expavg_credit
// host.expavg_time

$dir = getcwd();
chdir('/mydisks/a/users/boincadm/projects/test2/html/user');
require_once("../inc/boinc_db.inc");
chdir($dir);

// values for keyword.category
//
define('SCIENCE', 0);
define('LOCATION', 1);

// values for user_keyword.type
//
define('KW_YES', 1);
define('KW_MAYBE', 0);
define('KW_NO', -1);

// values for account.state
//
define('ACCT_INIT', 0);
define('ACCT_SUCCESS', 1);
define('ACCT_DIFFERENT_PASSWORD', 2);
define('ACCT_TRANSIENT_ERROR', 3);

// values of keyword.priority
//
define('KW_MINOR', 1);
define('KW_MAJOR', 2);

// values of project.status
//
define('PROJECT_STATUS_HIDE', 0);
define('PROJECT_STATUS_ON_DEMAND', 1);
define('PROJECT_STATUS_AUTO', 2);

class SUKeyword {
    static function insert($clause) {
        $db = BoincDb::get();
        $ret = $db->insert('su_keyword', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function lookup_id($id) {
        $db = BoincDb::get();
        return $db->lookup_id($id, 'su_keyword', 'SUKeyword');
    }
    static function enum($where_clause=null) {
        $db = BoincDb::get();
        return $db->enum('su_keyword', 'SUKeyword', $where_clause);
    }
    function delete() {
        $db = BoincDb::get();
        return $db->delete($this, 'su_keyword');
    }
    function update($clause) {
        $db = BoincDb::get();
        return $db->update($this, 'su_keyword', $clause);
    }
    static function lookup($clause) {
        $db = BoincDb::get();
        return $db->lookup('su_keyword', 'SUKeyword', $clause);
    }
}

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
    static function update($clause) {
        $db = BoincDb::get();
        return $db->update_aux('su_user_keyword', $clause);
    }
}

class SUAccount {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('su_account', $clause);
    }
    static function enum($where_clause=null) {
        $db = BoincDb::get();
        return $db->enum('su_account', 'SUAccount', $where_clause);
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
    static function last() {
        $db = BoincDb::get();
        $x = SUAccounting::enum("", "order by id desc limit 1");
        if ($x) {
            return $x[0];
        } else {
            return null;
        }
    }
    function update($clause) {
        $db = BoincDb::get();
        return $db->update($this, 'su_accounting', $clause);
    }
}

class SUAccountingProject {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('su_accounting_project', $clause);
    }
    static function enum($where_clause) {
        $db = BoincDb::get();
        return $db->enum('su_accounting_project', 'SUAccountingProject', $where_clause);
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
}

class SUAccountingUser {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('su_accounting_user', $clause);
    }
    static function enum($where_clause) {
        $db = BoincDb::get();
        return $db->enum('su_accounting_user', 'SUAccountingUser', $where_clause);
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
}
