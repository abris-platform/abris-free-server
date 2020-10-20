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

if (file_exists(dirname(__FILE__) . '/methods.php'))
	require dirname(__FILE__) . '/methods.php';
else
	require dirname(__FILE__) . '/methods_base.php';

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
	global $dbname, $dbDefaultLanguage, $flag_astra;
	// ini_set('session.cookie_samesite', 'None');
	// ini_set('session.cookie_secure', 'On');
	$usename = '';
	$pid_count = '';
	
	if (!file_exists(dirname(__FILE__) . '/config.php'))
	  return  json_encode(array("jsonrpc" => "2.0", "result" => null, "fatal" => null, "error" => "No configuration file", "usename" => null, "pids" => null));

	if(!$flag_astra) {   
        session_start();

        if (isset($_SESSION['login']))
            $usename = $_SESSION['login'];

        if (isset($_SESSION["dbname"])) 
            $dbname = $_SESSION["dbname"];

        if (!isset($_SESSION['lang']))
            $_SESSION['lang'] = $dbDefaultLanguage;
    }
    else{
        $usename = methods::getShortEnvKRB5currentUser();
    }

	$pid_count = isset($_SESSION['pids']) ? count($_SESSION['pids']) : 0;

	if (!isset($_POST["method"])) {
		if (file_exists(dirname(__FILE__) . '/get_methods.php'))
			include dirname(__FILE__) . '/get_methods.php';
		return json_encode(array("jsonrpc" => "2.0", "result" => null, "error" => "method", "usename" => $usename, "pids" => $pid_count));
	} else {
		if (!isset($_POST["params"])) {
			return json_encode(array("jsonrpc" => "2.0", "result" => null, "error" => "params", "usename" => $usename, "pids" => $pid_count));
		} else {
			$method = $_POST["method"];
			$params = json_decode($_POST["params"], true);

			ob_start();
			$result = call_user_func_array((class_exists("methods") ? "methods::" : "methodsBase::") .  $method, $params);
			ob_end_clean();
			if (isset($_SESSION['login']))
				$usename = $_SESSION['login'];

			try {

				return json_encode(array("jsonrpc" => "2.0", "result" => $result, "error" => null, "usename" => $usename, "pids" => $pid_count));
			} catch (Exception $e) {
				return json_encode(array("jsonrpc" => "2.0", "result" => null, "error" => $e->getMessage(), "usename" => $usename, "pids" => $pid_count));
			}
		}
	}
}

try {
	echo request();
} catch (Exception $e) {
	$usename = '';
	if (isset($_SESSION['login']))
		$usename = $_SESSION['login'];
	echo json_encode(array("jsonrpc" => "2.0", "result" => null, "error" => $e->getMessage(), "usename" => $usename, "pids" => $pid_count));
}
