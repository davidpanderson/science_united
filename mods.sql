alter table user add column
    has_usable_gpu          tinyint         not null,
    cpu_ec                  double          not null,
    gpu_ec                  double          not null,
    cpu_time                double          not null,
    gpu_time                double          not null,
    njobs_success           integer         not null,
    njobs_fail              integer         not null
;
