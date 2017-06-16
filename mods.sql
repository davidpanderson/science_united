alter table host
    add column p_features              varchar(512)    not null,
    add column boinc_client_version    varchar(64)     not null,
    add column n_usable_coprocs        tinyint         not null,
    add column p_vm_extensions_disabled       tinyint         not null,
    add column virtualbox_version      varchar(64)    not null,
    add column cpu_ec                  double          not null,
    add column gpu_ec                  double          not null,
    add column cpu_time                double          not null,
    add column gpu_time                double          not null,
    add column njobs_success           integer         not null,
    add column njobs_fail              integer         not null
;
