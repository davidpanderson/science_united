create table bp_credit_claim (
    id                  integer         not null auto_increment,
    user_id             integer         not null default 0,
    create_time         double          not null default 0,
    project_id          integer         not null default 0,
    email_addr          varchar(254)    not null default '',
    user_cpid           varchar(254)    not null default '',
    code                varchar(254)    not null default '',
    status              integer         not null default 0,
        /*
           * 0 = init
           * 1 = email confirmed
           * 2 = credit claimed
        */
    total_credit        double          not null default 0,
    primary key(id),
    unique (email_addr, user_id)
) engine = InnoDB;

create table bp_credit_claim_project (
    credit_claim_id     integer         not null default 0,
    project_id          integer         not null default 0,
    credit              double          not null default 0,
    project_user_id     integer         not null default 0,
    index (project_id, project_user_id)
) engine = InnoDB;
