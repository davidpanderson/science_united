<?php

// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2017 University of California
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
// return XML for default prefs w/ given setting
//

// called when we're about to set or modify the prefs in the DB
//
function compute_prefs_xml($preset) {
    $now = time();
    return "<global_preferences>
    <mod_time>$now</mod_time>
    <preset>$preset</preset>
</global_preferences>
";
}

define('PREFS_MAX',
"<max_ncpus_pct>100</max_ncpus_pct>
<cpu_usage_limit>100</cpu_usage_limit>
<run_on_batteries>0</run_on_batteries>
<run_if_user_active>1</run_if_user_active>
<run_gpu_if_user_active>1</run_gpu_if_user_active>
<idle_time_to_run>3</idle_time_to_run>
<suspend_if_no_recent_input>0</suspend_if_no_recent_input>
<work_buf_min_days>0.1</work_buf_min_days>
<work_buf_additional_days>0.5</work_buf_additional_days>
<cpu_scheduling_period_minutes>60</cpu_scheduling_period_minutes>
<disk_interval>180</disk_interval>
<disk_max_used_gb>0</disk_max_used_gb>
<disk_min_free_gb>1</disk_min_free_gb>
<disk_max_used_pct>90</disk_max_used_pct>
<ram_max_used_busy_pct>70</ram_max_used_busy_pct>
<ram_max_used_idle_pct>90</ram_max_used_idle_pct>
<leave_apps_in_memory>1</leave_apps_in_memory>
<vm_max_used_pct>75</vm_max_used_pct>
");

define('PREFS_STANDARD',
"<max_ncpus_pct>50</max_ncpus_pct>
<cpu_usage_limit>100</cpu_usage_limit>
<run_on_batteries>0</run_on_batteries>
<run_if_user_active>1</run_if_user_active>
<run_gpu_if_user_active>0</run_gpu_if_user_active>
<idle_time_to_run>3</idle_time_to_run>
<suspend_if_no_recent_input>0</suspend_if_no_recent_input>
<suspend_cpu_usage>25</suspend_cpu_usage>
<work_buf_min_days>0.1</work_buf_min_days>
<work_buf_additional_days>0.5</work_buf_additional_days>
<cpu_scheduling_period_minutes>60</cpu_scheduling_period_minutes>
<disk_interval>180</disk_interval>
<disk_max_used_gb>0</disk_max_used_gb>
<disk_min_free_gb>1</disk_min_free_gb>
<disk_max_used_pct>90</disk_max_used_pct>
<ram_max_used_busy_pct>50</ram_max_used_busy_pct>
<ram_max_used_idle_pct>90</ram_max_used_idle_pct>
<leave_apps_in_memory>1</leave_apps_in_memory>
<vm_max_used_pct>75</vm_max_used_pct>
");

define('PREFS_GREEN',
"<max_ncpus_pct>25</max_ncpus_pct>
<cpu_usage_limit>100</cpu_usage_limit>
<run_on_batteries>0</run_on_batteries>
<run_if_user_active>1</run_if_user_active>
<run_gpu_if_user_active>0</run_gpu_if_user_active>
<idle_time_to_run>3</idle_time_to_run>
<suspend_if_no_recent_input>10</suspend_if_no_recent_input>
<suspend_cpu_usage>25</suspend_cpu_usage>
<work_buf_min_days>0.1</work_buf_min_days>
<work_buf_additional_days>0.5</work_buf_additional_days>
<cpu_scheduling_period_minutes>60</cpu_scheduling_period_minutes>
<disk_interval>180</disk_interval>
<disk_max_used_gb>0</disk_max_used_gb>
<disk_min_free_gb>1</disk_min_free_gb>
<disk_max_used_pct>90</disk_max_used_pct>
<ram_max_used_busy_pct>50</ram_max_used_busy_pct>
<ram_max_used_idle_pct>90</ram_max_used_idle_pct>
<leave_apps_in_memory>1</leave_apps_in_memory>
<vm_max_used_pct>75</vm_max_used_pct>
");

// expand the presets in a set of prefs
//
function expand_compute_prefs($prefs) {
    $prefs = str_replace("<preset>standard</preset>", PREFS_STANDARD, $prefs);
    $prefs = str_replace("<preset>green</preset>", PREFS_GREEN, $prefs);
    $prefs = str_replace("<preset>max</preset>", PREFS_MAX, $prefs);
    return $prefs;
}

?>
