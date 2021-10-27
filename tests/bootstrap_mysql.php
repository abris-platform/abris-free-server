<?php

session_start();

if (!file_exists('Server/files'))
    mkdir('Server/files');
require_once "Server/autoload.php";

// Init config for mysql (similarly ApplicationInitBase::initConfigFree())
$cfg = new ConfigBase();
$cfg->init(
    __DIR__ . DIRECTORY_SEPARATOR . 'config_free_mysql.json',
    true
);
$GLOBALS['_CONFIG'] = $cfg;

ApplicationInitBase::initStorage();
global $_STORAGE;

// Init mysql database object (similarly ApplicationInitBase::initDatabase())
$_STORAGE['database'] = new DatabaseMysql(
    array(
        'host' => $cfg->host, 'dbname' => $cfg->dbname,
        'port' => $cfg->port, 'user' => $cfg->dbDefaultUser,
        'password' => $cfg->dbDefaultPass
    )
);

ApplicationInitBase::initDbSqlController();