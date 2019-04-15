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

// remove translations without a project-specific part

function main() {
    $compiled = scandir("../languages/compiled");
    $proj_spec = scandir("../languages/project_specific_translations");
    foreach ($compiled as $c) {
        if (substr($c, 1) == '.') continue;
        $x = explode('.', $c);
        $y = $x[0].'.'.$x[1];
        if (!in_array($y, $proj_spec)) {
            echo "deleting $c\n";
            unlink("../languages/compiled/$c");
        }
    }
}

main();

?>
