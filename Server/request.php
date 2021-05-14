<?php

/**
 * Abris - Web Application RAD Framework
 * @version v2.0.1
 * @license (c) TRO MOO AIO, Commercial Product
 * @date Sat Sep 17 2016 09:45:15
 */
/*
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
*/

require_once 'autoload.php';


try {
    try {
        ApplicationInitBase::checkNeedInstallFree();
        ApplicationInitBase::initConfigFree();
    } catch (Exception $e) {
        echo json_encode(
            array(
                'jsonrpc' => '2.0', 'result' => null,
                'fatal' => true, 'error' => $e->getMessage(),
                'usename' => null, 'pids' => null
            ));
        die;
    }

    ApplicationInitBase::initStorage();
    ApplicationInitBase::initDatabase();

    echo ApplicationInitBase::request();
} catch (Exception $e) {
    global $data_result, $_STORAGE;
    $usename = strval($_STORAGE['login']);
    $pid_count = isset($_STORAGE['pids']) ? count($_STORAGE['pids']) : 0;

    echo json_encode(array('jsonrpc' => '2.0', 'result' => $data_result, 'error' => $e->getMessage(), 'usename' => $usename, 'pids' => $pid_count));
}