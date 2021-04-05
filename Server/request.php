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

$_STORAGE = new WebStorage();

function cors() {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }

    if (isset($_SERVER['HTTPS'])) {
        ini_set('session.cookie_samesite', 'None');
        ini_set('session.cookie_secure', 'On');
    }

}

function normalizeKey($key) {
    $key = strtolower($key);
    $key = str_replace(array('-', '_'), ' ', $key);
    $key = preg_replace('#^http #', '', $key);
    $key = ucwords($key);
    $key = str_replace(' ', '-', $key);

    return $key;
}

function request() {
    cors();
    global $dbname, $dbDefaultLanguage, $_STORAGE;

    $usename = '';
    $pid_count = '';

    if (!file_exists(dirname(__FILE__) . '/configs/config.php'))
        return json_encode(array('jsonrpc' => '2.0', 'result' => null, 'fatal' => true, 'error' => 'No configuration file', 'usename' => null, 'pids' => null));

    if (!$_STORAGE->IsSession()) {
        $usename = strval($_STORAGE['login']);
        $dbname = isset($_STORAGE['dbname']) ? $_STORAGE['dbname'] : $dbname;

        if (!isset($_STORAGE['lang']))
            $_STORAGE['lang'] = $dbDefaultLanguage;
    } else {
        $usename = call_user_func((class_exists('methods') ? 'methods::' : 'methodsBase::') . 'getAnotherUsername');
    }

    $pid_count = isset($_STORAGE['pids']) ? count($_STORAGE['pids']) : 0;

    if (!isset($_POST['method'])) {
        // TODO get_methods переделать в статический класс и звать оттуда методы.
        $current_dir_path = dirname(__FILE__);
        $main_server_path = str_replace('/abris-free-server/Server', '', $current_dir_path);

        if ((stripos($current_dir_path, 'abris-free-server') !== false) && (file_exists("$main_server_path/get_methods.php"))) {
            include_once "$main_server_path/methods.php";
            include_once "$main_server_path/get_methods.php";
        } elseif (file_exists("$current_dir_path/get_methods.php")) {
            include "$current_dir_path/get_methods.php";
        }

        return json_encode(array('jsonrpc' => '2.0', 'result' => null, 'error' => 'method', 'usename' => $usename, 'pids' => $pid_count));
    } else {
        if (!isset($_POST['params'])) {
            return json_encode(array('jsonrpc' => '2.0', 'result' => null, 'error' => 'params', 'usename' => $usename, 'pids' => $pid_count));
        } else {
            $method = $_POST['method'];
            $params = json_decode($_POST['params'], true);

            ob_start();
            $result = call_user_func_array((class_exists('methods') ? 'methods::' : 'methodsBase::') . $method, $params);
            ob_end_clean();

            $usename = strval($_STORAGE['login']);

            try {
                return json_encode(array('jsonrpc' => '2.0', 'result' => $result, 'error' => null, 'usename' => $usename, 'pids' => $pid_count));
            } catch (Exception $e) {
                return json_encode(array('jsonrpc' => "2.0", 'result' => null, 'error' => $e->getMessage(), 'usename' => $usename, 'pids' => $pid_count));
            }
        }
    }
}

try {
    echo request();
} catch (Exception $e) {
    global $data_result;
    $usename = strval($_STORAGE['login']);
    $pid_count = isset($_STORAGE['pids']) ? count($_STORAGE['pids']) : 0;

    echo json_encode(array('jsonrpc' => "2.0", 'result' => $data_result, 'error' => $e->getMessage(), 'usename' => $usename, 'pids' => $pid_count));
}