/*
 * schema for Science United database.
 * These tables are added to a standard BOINC database.
 */

/*
 * represents a BOINC project
 */
create table su_project (
    id                      integer         not null auto_increment,
    create_time             double          not null,
    name                    varchar(254)    not null,
    url                     varchar(254)    not null,
    web_rpc_url_base        varchar(254)    not null,
    url_signature           varchar(1024)   not null,
    share                   double          not null,
    status                  tinyint         not null,
    avg_ec                  double          not null,
    avg_ec_adjusted         double          not null,
    web_url                 varchar(254)    not null,
    authenticator           varchar(254)    not null,
    nhosts                  integer         not null,
    primary key (id)
) engine=InnoDB;

/* an allocation to a project.  Not implemented yet */

create table su_allocation (
    id                      integer         not null auto_increment,
    project_id              integer         not null,
    init_alloc              double          not null,
    alloc_rate              double          not null,
    start_time              double          not null,
    duration                double          not null,
    status                  tinyint         not null,
        /* 0 init, 1 in progress, 2 over */
    primary key (id)
) engine=InnoDB;

create table su_user_keyword (
    user_id                 integer         not null,
    keyword_id              integer         not null,
    yesno                   smallint        not null,
        /* -1=no, 1=yes */
    unique(user_id, keyword_id)
) engine=InnoDB;

/*
 * a user account on a project.
 * NOTE: originally SU users had separate project accounts,
 * which had to be dynamically created by RPCs.
 * This has been replaced by a "single-account" model where
 * all SU users share a single project account.
 * We still have separate su_account records per user,
 * for accounting purposes; they'll have the same authenticator.
 */
create table su_account (
    user_id                 integer         not null,
    project_id              integer         not null,
    create_time             double          not null,
    authenticator           varchar(254)    not null,
    state                   smallint        not null,
    retry_time              double          not null default 0,
    cpu_ec                  double          not null default 0,
    cpu_time                double          not null default 0,
    gpu_ec                  double          not null default 0,
    gpu_time                double          not null default 0,
    njobs_success           integer         not null default 0,
    njobs_fail              integer         not null default 0,
    opt_out                 tinyint         not null default 0,
    email_addr              varchar(254)    not null default '',
    passwd_hash             varchar(254)    not null default '',
    name                    varchar(254)    not null default '',
    unique(user_id, project_id),
    index account_state (state)
) engine=InnoDB;

/*
 * Per (host, project) record. No history.
 * Stores totals at last AM RPC; used to compute deltas.
 */
create table su_host_project (
    host_id                 integer         not null,
    project_id              integer         not null,
    last_rpc                double          not null default 0,
        /* when host last made RPC to project */
    active                  tinyint         not null default 0,
        /* whether host is currently attached to project */
    requested               tinyint         not null default 0,
        /* what is this? */
    cpu_ec                  double          not null default 0,
    cpu_time                double          not null default 0,
    gpu_ec                  double          not null default 0,
    gpu_time                double          not null default 0,
    njobs_success           integer         not null default 0,
    njobs_fail              integer         not null default 0,
    unique(host_id, project_id)
) engine=InnoDB;

/*
 * historical accounting records, for
 * - total
 * - per project
 * - per user
 *
 * We could also have:
 * per-host: might be worth it
 * per-user-project, per-host-project etc.: probably not worth it.
 */

create table su_accounting (
    id                      integer         not null auto_increment,
    create_time             double          not null default 0,
    cpu_ec_delta            double          not null default 0,
    cpu_ec_total            double          not null default 0,
    gpu_ec_delta            double          not null default 0,
    gpu_ec_total            double          not null default 0,
    cpu_time_delta          double          not null default 0,
    cpu_time_total          double          not null default 0,
    gpu_time_delta          double          not null default 0,
    gpu_time_total          double          not null default 0,
    nactive_hosts           integer         not null default 0,
    nactive_hosts_gpu       integer         not null default 0,
    nactive_users           integer         not null default 0,
    njobs_success_delta     integer         not null default 0,
    njobs_success_total     integer         not null default 0,
    njobs_fail_delta        integer         not null default 0,
    njobs_fail_total        integer         not null default 0,
    primary key (id)
) engine=InnoDB;


create table su_accounting_project (
    id                      integer         not null auto_increment,
    create_time             double          not null default 0,
    project_id              integer         not null default 0,
    share                   double          not null default 0,
    cpu_ec_delta            double          not null default 0,
    cpu_ec_total            double          not null default 0,
    gpu_ec_delta            double          not null default 0,
    gpu_ec_total            double          not null default 0,
    cpu_time_delta          double          not null default 0,
    cpu_time_total          double          not null default 0,
    gpu_time_delta          double          not null default 0,
    gpu_time_total          double          not null default 0,
    njobs_success_delta     integer         not null default 0,
    njobs_success_total     integer         not null default 0,
    njobs_fail_delta        integer         not null default 0,
    njobs_fail_total        integer         not null default 0,
    nhosts                  integer         not null default 0,
    index (project_id),
    primary key (id)
) engine=InnoDB;

create table su_accounting_user (
    id                      integer         not null auto_increment,
    create_time             double          not null default 0,
    user_id                 integer         not null default 0,
    cpu_ec_delta            double          not null default 0,
    cpu_ec_total            double          not null default 0,
    gpu_ec_delta            double          not null default 0,
    gpu_ec_total            double          not null default 0,
    cpu_time_delta          double          not null default 0,
    cpu_time_total          double          not null default 0,
    gpu_time_delta          double          not null default 0,
    gpu_time_total          double          not null default 0,
    njobs_success_delta     integer         not null default 0,
    njobs_success_total     integer         not null default 0,
    njobs_fail_delta        integer         not null default 0,
    njobs_fail_total        integer         not null default 0,
    index (user_id),
    primary key (id)
) engine=InnoDB;

/* global allocation info (1 record) */

create table su_allocate (
    nprojects               integer         not null default 0,
    avg_ec_total            double          not null default 0,
    share_total             double          not null default 0
) engine=InnoDB;
