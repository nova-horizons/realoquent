<?php

// Values should match the keys in the config/database.php file without the "rl_" prefix
const RL_MARIADB_LTS = 'mariadbLTS';
const RL_MARIADB_LATEST = 'mariadbLatest';
const RL_MYSQL_8 = 'mysql8';
const RL_PGSQL_16 = 'pgsql16';
const RL_SQLITE = 'sqlite';

const RL_DATABASES = [
    RL_SQLITE,
    RL_MARIADB_LTS,
    RL_MARIADB_LATEST,
    RL_MYSQL_8,
    RL_PGSQL_16,
];

dataset('databases', RL_DATABASES);

dataset('databases-supporting-length', [
    RL_MYSQL_8,
    RL_MARIADB_LTS,
    RL_MARIADB_LATEST,
    RL_PGSQL_16,
]);

dataset('databases-supporting-fulltext', [
    RL_MYSQL_8,
    RL_MARIADB_LTS,
    RL_MARIADB_LATEST,
    RL_PGSQL_16,
]);

dataset('databases-supporting-unsigned', [
    RL_MYSQL_8,
    RL_MARIADB_LTS,
    RL_MARIADB_LATEST,
]);
