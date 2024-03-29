<?php

const RL_MARIADB_10 = 'mariadb10';
const RL_MYSQL_8 = 'mysql8';
const RL_PGSQL_14 = 'pgsql14';
const RL_SQLITE = 'sqlite';

const RL_DATABASES = [
    RL_SQLITE,
    RL_MARIADB_10,
    RL_MYSQL_8,
    RL_PGSQL_14,
];

dataset('databases', RL_DATABASES);

dataset('databases-supporting-length', [
    RL_MYSQL_8,
    RL_MARIADB_10,
    RL_PGSQL_14,
]);

dataset('databases-supporting-fulltext', [
    RL_MYSQL_8,
    RL_MARIADB_10,
    RL_PGSQL_14,
]);

dataset('databases-supporting-unsigned', [
    RL_MYSQL_8,
    RL_MARIADB_10,
]);
