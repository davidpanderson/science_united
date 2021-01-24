# Getting credit info for Science United users

Here’s how to get credit info for a Science United user, on all the projects they’ve contributed to.

## Get the user’s SU authenticator
This is a random string.
If you created the account using the create_account RPC, it was returned in the RPC reply.
If you have the account’s email address and password, you can get the authenticator using the lookup_account RPC.

## Get info on the user’s hosts

Do an HTTP GET to https://scienceunited.org/su_host_ids.php?auth=xxx, where xxx is the authenticator.
The reply will look like this:
```
<?xml version="1.0" encoding="ISO-8859-1" ?>
<hosts>
   <host>
      <create_time>1525317330</create_time>
      <rpc_time>1599511615</rpc_time>
      <rpc_seqno>510</rpc_seqno>
      <on_frac>0.573019</on_frac>
      <active_frac>0.875563</active_frac>
      <su_host_id>346</su_host_id>
      <projects>
         <project>
            <url>https://mindmodeling.org/</url>
            <host_id>137655</host_id>
         </project>
         <project>
            <url>http://www.rnaworld.de/rnaworld/</url>
            <host_id>85874</host_id>
         </project>
         <project>
            <url>https://www.gpugrid.net/</url>
            <host_id>476921</host_id>
         </project>
         <project>
            <url>http://asteroidsathome.net/boinc/</url>
            <host_id>678560</host_id>
         </project>
         ...
     </projects>
   </host>
</hosts>
```

This returns a list of the user’s hosts.  The elements are:

* Create_time: when the host first contacted SU (Unix time).
* Rpc_time: when the host last contacted SU.  Hosts normally contact SU once per day, so if time is more than a few days in the past, the host is probably not running BOINC.
* Rpc_seqno: the number of times the host has contacted SU.
* On_frac: the fraction of time the host is running BOINC, averaged over the last 14 days or so (see below for details).
* Active_frac: of the time the host is running BOINC, the fraction of time it’s allowed to compute.  The overall fraction of time computing is on_frac * active_frac.
* Su_host_id: the host’s ID in the SU database.  Probably not relevant to you.
* Projects: a list of projects the host has computed for.  For each one, the project URL and the host’s database ID on that project are given.

## Get the project credit files

Each project generates a set of XML files every day with their credit info.
We’ll use the one with per-info.
This can be found at the project URL followed by stats/host.gz.  For example:
```
https://www.gpugrid.net/stats/host.gz
```
Download and unzip this for each project.  It will contain lots of host elements.  For example, this is the one corresponding to the above host:
```
<host>
    <id>476921</id>
    <userid>528774</userid>
    <total_credit>1294222.656250</total_credit>
    <expavg_credit>245.492926</expavg_credit>
    <expavg_time>1598262904.033105</expavg_time>
    <p_vendor>GenuineIntel</p_vendor>
    <p_model>Intel(R) Core(TM) i7-9750H CPU @ 2.60GHz [Family 6 Model 158 Stepping 10]</p_model>
    <os_name>Microsoft Windows 10</os_name>
    <os_version>Professional x64 Edition, (10.00.18362.00)</os_version>
    <coprocs>[BOINC|7.17.0][CUDA|GeForce GTX 1650 with Max-Q Design|1|4095MB|44332]</coprocs>
  <create_time>1526329191</create_time>
  <rpc_time>1598322265</rpc_time>
  <timezone>-25200</timezone>
  <ncpus>12</ncpus>
  <p_fpops>3304659572.817706</p_fpops>
  <p_iops>3215837528.296430</p_iops>
  <p_membw>166666666.666667</p_membw>
  <m_nbytes>16901595136.000000</m_nbytes>
  <m_cache>262144.000000</m_cache>
  <m_swap>28459372544.000000</m_swap>
  <d_total>510769758208.000000</d_total>
  <d_free>212083933184.000000</d_free>
  <n_bwup>72727.400591</n_bwup>
  <n_bwdown>3638249.574377</n_bwdown>
  <avg_turnaround>107122.691359</avg_turnaround>
  <credit_per_cpu_sec>0.000000</credit_per_cpu_sec>
  <host_cpid>553697f9c3da72add46a55f75f8417b5</host_cpid>
</host>
```
Expavg_credit is the daily credit averaged exponentially with a half-life of a week.  Total_credit is the total credit.

# Collate
For a given SU user, you need to look up each of their hosts in the credit info file for each project.
The best way to do this is to parse the credit info files into a MySQL database, and index it on project and host ID.
I think there’s some code somewhere that does this.

Note that this approach puts negligible load on project servers.
You’re downloading a single file once per day, not doing a zillion Web RPCs that do DB accesses.

Getting the list of host IDs from Science United (using an RPC as above) could get burdensome on the SU server if the set of users becomes huge.
In that case we could do something more efficient, like have SU generate a single XML file you download once per day.

# Getting hours of computing per day per host

As described above, the su_host_ids RPC returns on_frac and active_frac:
recent averages of the fraction of time BOINC is running, and the fraction of that time it’s allowed to compute.

Each of these is a decaying exponential average. 
The client maintains the average and periodically updates it. 
The value at a time T (measured in days) is given by
```
Avg(T) = A*Avg(T0) + (1-A)*X
```
Where 

* T0 is the previous time the average was computed
* X is the usage fraction in the interval (T0, T)
* A = exp(-(T-T0)/10)

For example, if T and T0 are 1 day apart, then
```
Avg(T) = 0.0951163*X + 0.9048374*Avg(T0)
```
Note: 0.9048374 is e^(-1/10) and 0.0951163 is 1 minus that.

Note that the last 10 days has weight 1/e, and everything before that has weight 1-1/e.
So roughly speaking, it’s the average over the last 2 weeks.

If you know the values of Avg(T) and Avg(T0), you can figure out X by solving the above equation:
```
X = (Avg(T) - A*Avg(n-1))/(1-A)
```
For example, suppose that on_frac is 0.573019 at one time and 0.562020 a day later.
Then, during this day, BOINC was running
```
(0.562020 - 0.9048374*0.573019)/0.0951163
```
or 0.45766054703 of the time.
This makes sense: the computer was on less time than usual, so the average went down a little.

You can do the same calculation to figure out the fraction of time BOINC was able to compute,
and multiply these two numbers to get the fraction of computing time during that period.

Notes:
* Make sure you look at host’s rpc_time.
This should typically be in the past 24 hours; hosts normally contact Science United every 24 hours.  If it’s older than that, assume the computer hasn’t run BOINC in the past day.
* The values of rpc_time may be more or less than one day apart.   For example, if the measurements are X days apart use A = e^(-X/10).
