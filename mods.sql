alter table host
    add column p_features              varchar(4096)    not null default '',
    add column boinc_client_version    varchar(64)     not null default '',
    add column has_usable_gpu          tinyint         not null default 0,
    add column p_vm_extensions_disabled       tinyint         not null default 0,
    add column virtualbox_version      varchar(64)    not null default '',
    add column cpu_ec                  double          not null default 0,
    add column gpu_ec                  double          not null default 0,
    add column cpu_time                double          not null default 0,
    add column gpu_time                double          not null default 0,
    add column njobs_success           integer         not null default 0,
    add column njobs_fail              integer         not null default 0
;
