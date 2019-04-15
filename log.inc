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

// logging for PHP scripts
// - for things run from config.xml, just call log_write()
// - for RPC handlers, call log_open() first

$log_file = null;
$log_disabled = false;

function log_open($path) {
    global $log_file;
    $log_file = fopen($path, 'a');
}

function log_write($x) {
    global $log_file, $log_disabled;
    if ($log_disabled) {
        return;
    }
    if (!$log_file) {
        $log_file = fopen('php://stdout', 'w');
    }
    fwrite($log_file,  date(DATE_RFC822).": $x\n");
    fflush($log_file);
}

?>
