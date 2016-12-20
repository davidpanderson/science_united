create table keyword (
    id                      integer         not null auto_increment,
    word                    varchar(254)    not null,
    category                tinyint         not null,
    primary key (id)
) engine=InnoDB;

create table project (
    id                      integer         not null auto_increment,
    create_time             integer         not null,
    name                    varchar(254)    not null,
    url                     varchar(254)    not null,
    url_signature           varchar(1024)   not null,
    allocation              double          not null,
    primary key (id)
) engine=InnoDB;

create table project_keyword (
    project_id              integer         not null,
    keyword_id              integer         not null,
    unique(project_id, keyword_id)
) engine=InnoDB;

create table user_keyword (
    user_id                 integer         not null,
    keyword_id              integer         not null,
    type                    smallint        not null,
    unique(user_id, keyword_id)
) engine=InnoDB;

create table account (
    user_id                 integer         not null,
    project_id              integer         not null,
    state                   smallint        not null,
    unique(user_id, project_id),
    index account_state (state)
) engine=InnoDB;

create table project_attachment (
    host_id                 integer         not null,
    project_id              integer         not null,
    unique(host_id, project_id)
) engine=InnoDB;
