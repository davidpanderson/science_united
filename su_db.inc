<?php

$dir = getcwd();
chdir('/mydisks/a/users/boincadm/projects/test2/html/user');
require_once("../inc/boinc_db.inc");
chdir($dir);

// values for keyword category
//
define('SCIENCE', 0);
define('LOCATION', 1);

// user keyword type
//
define('KW_YES', 1);
define('KW_MAYBE', 0);
define('KW_NO', -1);

// values for account state
//
define('INIT', 0);
define('SUCCESS', 1);
define('DIFFERENT_PASSWD', 2);
define('TRANSIENT_ERROR', 3);

class SUKeyword {
    static function insert($clause) {
        $db = BoincDb::get();
        $ret = $db->insert('keyword', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function lookup_id($id) {
        $db = BoincDb::get();
        return $db->lookup_id($id, 'keyword', 'SUKeyword');
    }
    static function enum() {
        $db = BoincDb::get();
        return $db->enum('keyword', 'SUKeyword');
    }
}

class SUProject {
    static function insert($clause) {
        $db = BoincDb::get();
        $ret = $db->insert('project', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function lookup_id($id) {
        $db = BoincDb::get();
        return $db->lookup_id($id, 'project', 'SUProject');
    }
    function update($clause) {
        $db = BoincDb::get();
        return $db->update($this, 'project', $clause);
    }
    function delete() {
        $db = BoincDb::get();
        return $db->delete($this, 'project');
    }
    static function enum() {
        $db = BoincDb::get();
        return $db->enum('project', 'SUProject');
    }
}

class SUProjectKeyword {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('project_keyword', $clause);
    }
    static function enum() {
        $db = BoincDb::get();
        return $db->enum('project_keyword', 'SUProjectKeyword');
    }
    static function delete($clause) {
        $db = BoincDb::get();
        return $db->delete_aux('project_keyword', $clause);
    }
}

class SUUserKeyword {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('user_keyword', $clause);
    }
    static function enum() {
        $db = BoincDb::get();
        return $db->enum('user_keyword', 'SUUserKeyword');
    }
    static function delete($clause) {
        $db = BoincDb::get();
        return $db->delete_aux('user_keyword', $clause);
    }
    static function update($clause) {
        $db = BoincDb::get();
        return $db->update_aux('user_keyword', $clause);
    }
}

class SUAccount {
    static function insert($clause) {
        $db = BoincDb::get();
        return $db->insert('account', $clause);
    }
    static function enum($where_clause) {
        $db = BoincDb::get();
        return $db->enum('account', 'SUAccount', $where_clause);
    }
    static function lookup($where_clause) {
        $db = BoincDb::get();
        return $db->lookup('account', 'SUAccount', $where_clause);
    }
}
