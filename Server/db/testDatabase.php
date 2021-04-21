<?php

require_once 'DatabaseInterface.php';
require_once 'DatabaseMysql.php';
require_once 'DatabasePostgresql.php';


class Adapter {

    public static function showConnect() {
        global $CONNECT;
        return $CONNECT["class"]->connect("dsdsds");
    }
}

$CONNECT = array();
$p = new DatabasePostgresql();
$m = new DatabaseMysql();
$CONNECT["class"] = $p;


$c = 'DatabasePostgresql';

$c::connect();

print(Adapter::showConnect());
print('</br>');
print(Adapter::showConnect());