<?php

/**
 * Abris - Web Application RAD Framework
 * @version v2.0.1
 * @license (c) TRO MOO AIO, Commercial Product
 * @date Sat Sep 17 2016 09:45:15
 */

include 'config_default.php';
if (file_exists(dirname(__FILE__) . '/config.php'))
    include dirname(__FILE__) . '/config.php';

function exception_error_handler($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}

function _get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if (isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function unpackJSON($text) {
    if ($j = json_decode($text)) {
        if (is_object($j)) {
            if (property_exists($j, 'f1'))
                return $j->{'f1'};
            if (property_exists($j, 'name'))
                return $j->{'name'};
            return '';
        }
        return $text;
    }
    return $text;
}


function id_quote($identifier) {
    return '"' . str_replace('"', '""', $identifier) . '"';
}

// The function checks the availability of the administration scheme and sets the corresponding flag to the variable $ _SESSION ["enable_admin"]
function checkSchemaAdmin() {
    global $adminSchema;
    $_SESSION["enable_admin"] = sql_s("SELECT EXISTS(SELECT 1 FROM information_schema.schemata WHERE schema_name = '" . $adminSchema . "');")[0]["exists"];
}

function preprocess_data($data) {
    $data = trim($data);

    if ($data == 'true')
        return 't';
    if ($data == 'false')
        return 'f';

    return $data;
}

function sql_handler_test($query, $format) {

    if (!isset($_SESSION['pids']))
        $_SESSION['pids'] = array();
    if (!isset($_SESSION["enable_admin"]))
        $_SESSION["enable_admin"] = "f";

    $query = str_replace(array("\r\n", "\r", "\n"), ' ', $query);
    $query_test_array = file("test_query_response_json.txt", FILE_IGNORE_NEW_LINES);
    foreach ($query_test_array as $line_num => $line) {
        $json_response = json_decode($line, true);
        if (($json_response["query"] == $query) && ($json_response["format"] == $format))
            return $json_response["response"];
    }

    return "new_query_test";
}

function sql($query, $do_not_preprocess = false, $logDb = false, $format = 'object', $query_description = '', $encrypt_pass = true)
{
    global $host, $dbname, $port, $dbuser, $dbpass, $adminSchema, $adminLogTable, $adminSessionTable, $dbDefaultLanguage, $anotherPrefLog;


    if (!(!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) {  // если тест
        $response = sql_handler_test($query, $format);
        if ($response != "new_query_test") return $response;
    }


    if (isset($_SESSION['dbname']))
        $dbname = $_SESSION['dbname'];

    if ($format != 'object' && $format != 'array')
        throw new Exception("'$format' is unknown format!");

    if (isset($_SESSION['login']) and isset($_SESSION['password'])) {
        $log = $_SESSION['login'];
        $pwd = $encrypt_pass ? methodsBase::DecryptStr($_SESSION['password'], $_COOKIE['PHPSESSID']) : $_SESSION['password'];
        checkSchemaAdmin();

        $dbconn = @pg_connect("host=$host dbname=$dbname port=$port user=$log password=$pwd");
        if (!$dbconn) {
            $dbconn = @pg_connect("host=$host dbname=$dbname port=$port user=$dbname@$log password=$pwd");
            if (!$dbconn) {
                $dbconn = @pg_connect("host=$host dbname=$dbname port=$port user=$dbname@$log password=$pwd");
                if (!$dbconn) {
                    $ipAddr = _get_client_ip();
                    if ($_SESSION["enable_admin"] == "t")
                        sql_s("INSERT INTO " . $adminSchema . "." . $adminSessionTable . "(usename, ipaddress, success, php_session) values('" . $_SESSION['login'] . "', '" . $ipAddr . "', false, '" . $_COOKIE['PHPSESSID'] . "');");

                    unset($_SESSION['login']);
                    unset($_SESSION['password']);
                    throw new Exception("Невозможно выполнить подключение пользователем $log. Для подробностей обратитесь к администратору.");
                }
            }
        }
    } else {
        $dbconn = pg_connect("host=$host dbname=$dbname port=$port user=$dbuser password=$dbpass");
        if (!$dbconn)
            throw new Exception("Could not connect to database for user guest");
    }

    $pid = pg_get_pid($dbconn);
    if (!isset($_SESSION['pids']))
        $_SESSION['pids'] = array();
    else
        $_SESSION['pids'][$pid] = array('query' => $query, 'desc' => $query_description, 'timestamp' => date('Y-m-d H:i:s', time()));


    file_put_contents("sql.log", date('Y-m-d H:i:s', time()) . "\t" . (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : 'cli') . "\t" . $pid . "\t" . $query . "\n", FILE_APPEND);

// Update the release date in the table (sessions)
    if (isset($_SESSION['login']) && isset($_SESSION['password']))
        if (($_SESSION['login'] <> '') and ($_SESSION['password'] <> '') and ($_SESSION["enable_admin"] == "t")) {
            $ipAddr = _get_client_ip();
            $updDateExit = pg_query($dbconn, "SELECT " . $adminSchema . ".update_session('" . $_SESSION['login'] . "', '" . $ipAddr . "', '" . $_COOKIE['PHPSESSID'] . "');");
        }

// Add to user actions in the table (log).
    $logs = array();
    if ($logDb and ($_SESSION["enable_admin"] == "t")) {
        $log = pg_query($dbconn, "INSERT INTO " . $adminSchema . "." . $adminLogTable . "(query) VALUES (" . "'" . pg_escape_string($query) . "') RETURNING key;");

        while ($line = pg_fetch_array($log, null, PGSQL_ASSOC)) {
            if (!$do_not_preprocess)
                $logs[] = array_map('preprocess_data', $line);
            else
                $logs[] = $line;
        }
    }

    $result = pg_query($dbconn, 'SET bytea_output = "escape"; SET intervalstyle = \'iso_8601\';');
    if (!$result) {
        throw new Exception(pg_last_error());
    }

    /* setup language */
    if (isset($_SESSION['language']))
        $lang = $_SESSION['language'];
    else $lang = $dbDefaultLanguage;
    $result = pg_query($dbconn, "set abris.language = '$lang'");
    if (!$result)
        throw new Exception(pg_last_error());
    /* eof language setup */
    if (!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__')) {
        session_commit();
    }

    $result = pg_query($dbconn, $query);
    if (!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__')) {
        session_start();
    }

    unset($_SESSION['pids'][$pid]);

    if (!$result) {
        // If an error has occurred from the side of the database, then try to push this into the query logging table (log_query).
        $lastError = pg_last_error();
        if ($_SESSION["enable_admin"] == "t") {
            if (array_key_exists('key', $logs[0]))
                pg_query($dbconn, "UPDATE " . $adminSchema . "." . $adminLogTable . " SET error = '" . pg_escape_string($lastError) . "' WHERE key = '" . $logs[0]['key'] . "';");
            else
                pg_query($dbconn, "INSERT INTO " . $adminSchema . "." . $adminLogTable . "(query, error) VALUES ('" . pg_escape_string($query) . "', '" . pg_escape_string($lastError) . "');");
        }
        throw new Exception($lastError);
    }

    $response = array();


    while ($line = pg_fetch_array($result, null, ($format == 'object') ? PGSQL_ASSOC : PGSQL_NUM)) {
        if (!$do_not_preprocess)
            $response[] = array_map('preprocess_data', $line);
        else
            $response[] = $line;
    }

    pg_free_result($result);
    pg_close($dbconn);

    // test_write -------------------------------
    if (!(!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) {  // если тест
        $fp = fopen("test_query_response_json.txt", "a+");
        $json = [
            "query" => str_replace(array("\r\n", "\r", "\n"), ' ', $query),
            "format" => $format,
            "response" => $response,

        ];
        fwrite($fp, json_encode($json) . PHP_EOL);
        fclose($fp);
    }
    // -------------------------------------------


    return $response;
}



function sql_s($query) {

    if (!(!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) {  // если тест
        $response = sql_handler_test($query, "");
        if ($response != "new_query_test") return $response;
    }

    global $host, $dbname, $port, $dbuser, $dbpass;
    $dbconn = pg_connect("host=$host dbname=$dbname port=$port user=$dbuser password=$dbpass") or die('Could not connect: ' . pg_last_error());
    $result = pg_query($dbconn, $query);
    if (!$result) {
        throw new Exception(pg_last_error());
    }

    $response = array();
    while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
        $response[] = array_map('trim', $line);
    }
    pg_free_result($result);
    pg_close($dbconn);

    // test_write -------------------------------
    if (!(!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) {  // если тест
        $fp = fopen("test_query_response_json.txt", "a+");
        $json = [
            "query" => str_replace(array("\r\n", "\r", "\n"), ' ', $query),
            "format" => "",
            "response" => $response,

        ];
        fwrite($fp, json_encode($json) . PHP_EOL);
        fclose($fp);
    }
    // -------------------------------------------

    return $response;
}

function sql_auth($query) {
    global $host, $dbname, $port, $dbuser, $dbpass;
    $dbconn = pg_connect("host=$host dbname=$dbname port=$port user=$dbuser password=$dbpass") or die('Could not connect: ' . pg_last_error());
    $result = pg_query($dbconn, "select * from sql_auth('" . pg_escape_string($query) . "', '" . @$_SESSION['key'] . "')");
    if (!$result) {
        throw new Exception(pg_last_error());
    }

    $response = array();
    while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
        $response[] = array_map('trim', $line);
    }
    pg_free_result($result);
    pg_close($dbconn);
    return $response;
}


function sql_img($query) {
    global $host, $dbname, $port, $dbuser, $dbpass;
    $dbconn = pg_connect("host=$host dbname=$dbname port=$port user=$dbuser password=$dbpass") or die('Could not connect: ' . pg_last_error());

    $result = pg_query($dbconn, $query);
    if (!$result) {
        throw new Exception(pg_last_error());
    }

    $response = pg_unescape_bytea(pg_fetch_result($result, 0));

    pg_free_result($result);
    pg_close($dbconn);
    return $response;
}
