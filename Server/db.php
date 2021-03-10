<?php

/**
 * Abris - Web Application RAD Framework
 * @version v2.0.1
 * @license (c) TRO MOO AIO, Commercial Product
 * @date Sat Sep 17 2016 09:45:15
 */

include 'config_default.php';
include 'services_methods.php';
include 'web_storage.php';
if (file_exists(dirname(__FILE__) . '/configs/config.php'))
    include dirname(__FILE__) . '/configs/config.php';


function exception_error_handler($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}

function id_quote($identifier) {
    return '"' . str_replace('"', '""', $identifier) . '"';
}

// The function checks the availability of the administration scheme and sets the corresponding flag to the variable $ _SESSION ["enable_admin"]
function checkSchemaAdmin() {
    global $adminSchema, $_STORAGE;
    if (!isset($_STORAGE['enable_admin'])) {
        $_STORAGE['enable_admin'] = sql(
            "SELECT EXISTS(SELECT 1 FROM information_schema.schemata WHERE schema_name = '$adminSchema');",
            false, false, 'object',
            '', false, true
        )[0]['exists'];
    }
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
    global $_STORAGE;

    if (!isset($_STORAGE['pids']))
        $_STORAGE['pids'] = array();
    if (!isset($_STORAGE['enable_admin']))
        $_STORAGE['enable_admin'] = 'f';

    $query = str_replace(array("\r\n", "\r", "\n"), ' ', $query);
    $query_test_array = file('test_query_response_json.txt', FILE_IGNORE_NEW_LINES);
    foreach ($query_test_array as $line_num => $line) {
        $json_response = json_decode($line, true);
        if (isset($json_response['query']) && isset($json_response['format']))
            if (($json_response['query'] == $query) && ($json_response['format'] == $format))
                return $json_response['response'];
    }

    return 'new_query_test';
}

function unset_auth_session() {
    global $_STORAGE;

    if ((!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) 
        $_STORAGE->killStorage();
    unset($_STORAGE);

    if (isset($_COOKIE['private_key']))
        setcookie('private_key', null, -1);
}

function custom_pg_connect($encrypt_password, $default_connection) {
    global $_STORAGE, $host, $dbname, $port, $dbuser, $dbpass, $flag_astra, $anotherPrefLog;
    $usename = '';
    $dbname = isset($_STORAGE['dbname']) ? $_STORAGE['dbname'] : $dbname;

    if ((isset($_STORAGE['login']) || isset($_STORAGE['full_usename'])) && isset($_STORAGE['password']) && !$default_connection) {
        $privateKey = '';
        if (isset($_COOKIE['private_key']))
            $privateKey = $_COOKIE['private_key'];

        $password = $encrypt_password ? DecryptStr($_STORAGE['password'], $privateKey) : $_STORAGE['password'];
        if (!$password) {
            unset_auth_session();
            throw new Exception('Invalid password detected! Password can be changed!');
        }

        checkSchemaAdmin();

        $session_usename = isset($_STORAGE['full_usename']) ? $_STORAGE['full_usename'] : $_STORAGE['login'];
        $variants_login = array(
            $session_usename,
            "$anotherPrefLog@$session_usename",
            "$dbname@$session_usename"
        );

        foreach ($variants_login as $login) {
            $usename = $login;
            $dbconnect = @pg_connect("host=$host dbname=$dbname port=$port user=$login password=$password");
            if ($dbconnect) {
                $_STORAGE['full_usename'] = $login;
                return $dbconnect;
            }
        }

        $usename = $session_usename;
    } elseif ($flag_astra && (isset($_STORAGE['login']) || isset($_STORAGE['full_usename']))) {
        $session_usename = isset($_STORAGE['full_usename']) ? $_STORAGE['full_usename'] : $_STORAGE['login'];
        $usename = $session_usename;

        $dbconnect = @pg_connect("host=$host dbname=$dbname port=$port user=$session_usename");
        if ($dbconnect)
            return $dbconnect;
    } else {
        $usename = $dbuser;
        $dbconnect = @pg_connect("host=$host dbname=$dbname port=$port user=$dbuser password=$dbpass");
        if ($dbconnect)
            return $dbconnect;
    }

    // If returns not worked in previous stages, then connect not worked at all
    unset_auth_session();
    throw new Exception("Unable to connect by user $usename to system.");
}

function sql($query, $do_not_preprocess = false, $logDb = false, $format = 'object', $query_description = '', $encrypt_pass = true, $default_connection = false) {
    global $adminSchema, $adminLogTable, $dbDefaultLanguage, $flag_astra, $_STORAGE;

    if ($flag_astra) {
        $_STORAGE['login'] = methodsBase::getShortEnvKRB5currentUser();

        if (isset($_SERVER['PHP_AUTH_PW'])) {
            $_STORAGE['password'] = $_SERVER['PHP_AUTH_PW'];
        }
    }

    if (!(!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) {
        $response = sql_handler_test($query, $format);
        if ($response != 'new_query_test') return $response;
    }


    if ($format != 'object' && $format != 'array')
        throw new Exception("'$format' is unknown format!");

    $dbconn = custom_pg_connect($encrypt_pass, $default_connection);

    $pid = pg_get_pid($dbconn);
    if (!isset($_STORAGE['pids']))
        $_STORAGE['pids'] = array();
    else
        $_STORAGE['pids'][$pid] = array('query' => $query, 'desc' => $query_description, 'timestamp' => date('Y-m-d H:i:s', time()));


    file_put_contents("sql.log", date('Y-m-d H:i:s', time()) . "\t" . (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : 'cli') . "\t" . $pid . "\t" . $query . "\n", FILE_APPEND);

    // Update the release date in the table (sessions)
    if (isset($_STORAGE['login']) && isset($_STORAGE['password']))
        if (($_STORAGE['login'] <> '') and ($_STORAGE['password'] <> '') and ($_STORAGE["enable_admin"] == 't')) {
            $ipAddr = GetClientIP();
            $updDateExit = pg_query($dbconn, "SELECT $adminSchema.update_session('$_STORAGE[login]', '$ipAddr', '$_COOKIE[PHPSESSID]');");
        }

    // Add to user actions in the table (log).
    $logs = array();
    if ($logDb and ($_STORAGE['enable_admin'] == 't')) {
        $log = pg_query($dbconn, "INSERT INTO $adminSchema.$adminLogTable(query) VALUES ('" . pg_escape_string($query) . "') RETURNING key;");

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
    $lang = isset($_STORAGE['language']) ? $_STORAGE['language'] : $dbDefaultLanguage;

    $result = pg_query($dbconn, "set abris.language = '$lang'");
    if (!$result)
        throw new Exception(pg_last_error());
    /* eof language setup */

    if (!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__')) {
        // Close session because next query into database can be very long and other queries not execute.
        $_STORAGE->pauseSession();
    }

    $result = pg_query($dbconn, $query);
    if (!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__') && (session_status() == PHP_SESSION_NONE)) {
        // Reopen session after close.
        $_STORAGE->startSession();
    }

    unset($_STORAGE['pids'][$pid]);

    if (!$result) {
        // If an error has occurred from the side of the database, then try to push this into the query logging table (log_query).
        $lastError = pg_last_error();
        if ($_STORAGE['enable_admin'] == 't') {
            if (array_key_exists('key', $logs[0]))
                pg_query($dbconn, "UPDATE $adminSchema.$adminLogTable SET error = '" . pg_escape_string($lastError) . "' WHERE key = '$logs[0][key]';");
            else
                pg_query($dbconn, "INSERT INTO $adminSchema.$adminLogTable(query, error) VALUES ('" . pg_escape_string($query) . "', '" . pg_escape_string($lastError) . "');");
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
    if (!(!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) {
        $fp = fopen('test_query_response_json.txt', 'a+');
        $json = [
            'query' => str_replace(array("\r\n", "\r", "\n"), ' ', $query),
            'format' => $format,
            'response' => $response,

        ];
        fwrite($fp, json_encode($json) . PHP_EOL);
        fclose($fp);
    }
    // -------------------------------------------


    return $response;
}