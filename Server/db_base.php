<?php

class SQLBase
{
    protected static $dbconn;
    protected static $logs;
    protected static $query;
    protected static $options;


    protected static function db_connect($data){
        global $db_mysql;
        if($db_mysql)
            $connect = @mysqli_connect($data['host'], $data['user'], $data['password'], $data['dbname']);
        else
            $connect = @pg_connect("host=$data[host] dbname=$data[dbname] port=$data[port] user=$data[user] password=$data[password]");
        return $connect;
    }

    protected static function db_get_pid(){
        global $db_mysql;
        if(!$db_mysql)
            $pid = pg_get_pid(self::$dbconn);
        else
            $pid = "";
        return $pid;
    }

    protected static function db_last_error(){
        global $db_mysql;
        if(!$db_mysql)
            return pg_last_error(self::$dbconn);
        else
            return mysqli_error(self::$dbconn);
    }

    protected static function db_fetch_array($result, $format){
        global $db_mysql;
        if(!$db_mysql)
            return pg_fetch_array($result, NULL, $format);
        else
            return mysqli_fetch_array($result, $format);
    }

    protected static function db_free_result($result){
        global $db_mysql;
        if(!$db_mysql)
            return pg_free_result($result);
        else
            return mysqli_free_result($result);
    }

    protected static function db_query($query){
        global $db_mysql;
        if(!$db_mysql)
            return pg_query(self::$dbconn, $query);
        else
            return mysqli_query(self::$dbconn, $query);
    }

    protected static function db_close(){
        global $db_mysql;
        if(!$db_mysql)
            return pg_close(self::$dbconn);
        else
            return mysqli_close(self::$dbconn);
    }


    public static function db_escape_string($value){
        global $db_mysql;
        $options = static::GetDefaultOptions();
       
        if(!$db_mysql)
            return pg_escape_string($value);
        else{
            self::$dbconn = static::custom_pg_connect($options->GetEncryptPassword(), $options->GetDefaultConnection());
            $res =  mysqli_real_escape_string(self::$dbconn, $value);
            self::db_close();
            self::$dbconn = null;
            return $res;
        }

    }




    protected static function PrepareConnection($format) {
        if (!(!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) {
            $response = self::sql_handler_test(self::$query, $format);
            if ($response != 'new_query_test')
                return $response;
        }
        return null;
    }

    protected static function custom_pg_connect($encrypt_password, $default_connection) {
        global $_STORAGE, $_CONFIG;
        $anotherPrefLog = ''; // TODO move param to config-free.json
        $usename = '';
        $dbname = $_STORAGE['dbname'] ?? $_CONFIG->dbname;

        if ((isset($_STORAGE['login']) || isset($_STORAGE['full_usename'])) && isset($_STORAGE['password']) && !$default_connection) {
            $privateKey = '';
            if (isset($_COOKIE['private_key']))
                $privateKey = $_COOKIE['private_key'];

            $password = $encrypt_password ? DecryptStr($_STORAGE['password'], $privateKey) : $_STORAGE['password'];
            if (!$password) {
                unset_auth_session();
                throw new Exception('Invalid password detected! Password can be changed!');
            }

            $session_usename = $_STORAGE['full_usename'] ?? $_STORAGE['login'];
            $variants_login = array(
                $session_usename,
                "$anotherPrefLog@$session_usename",
                "$dbname@$session_usename"
            );

            foreach ($variants_login as $login) {
                $usename = $login;
                $dbconnect = self::db_connect(array('host'=>$_CONFIG->host, 'dbname' => $_CONFIG->dbname, 'port'=>$_CONFIG->port, 'user'=>$login, 'password'=>$password));
                if ($dbconnect) {
                    $_STORAGE['full_usename'] = $login;
                    return $dbconnect;
                }
            }

            $usename = $session_usename;
        }
        else {
            $usename = $_CONFIG->dbDefaultUser;
            $dbconnect = self::db_connect(array('host'=>$_CONFIG->host, 'dbname'=>$_CONFIG->dbname, 'port'=>$_CONFIG->port,'user'=>$usename, 'password'=>$_CONFIG->dbDefaultPass));
            if ($dbconnect)
                return $dbconnect;
        }

        // If returns not worked in previous stages, then connect not worked at all
        unset_auth_session();
        throw new Exception("Unable to connect by user $usename to system.");
    }

    protected static function BeforeQuery($pid, $query_description) {
        global $_STORAGE, $_CONFIG;
        global $db_mysql;
        if($db_mysql) return;

        if (!isset($_STORAGE['pids']))
            $_STORAGE['pids'] = array();
        else
            $_STORAGE['pids'][$pid] = array('query' => self::$query, 'desc' => $query_description, 'timestamp' => date('Y-m-d H:i:s', time()));

        if (self::$options->IsLogFile())
            file_put_contents('sql.log', date('Y-m-d H:i:s', time()) . '\t' . ($_SERVER['REMOTE_ADDR'] ?? 'cli') . '\t' . $pid . '\t' . self::$query . '\n', FILE_APPEND);

        $result = self::QueryExec('SET bytea_output = "escape"; SET intervalstyle = \'iso_8601\';');
        if (!$result)
            throw new Exception(self::db_last_error());

        $lang = $_STORAGE['language'] ?? $_CONFIG->dbDefaultLanguage;
        $result = self::QueryExec("set abris.language = '$lang'");
        if (!$result)
            throw new Exception(self::db_last_error());
    }

    protected static function AfterQuery($result) {
        global $_STORAGE;

        if (!$result) {
            // If an error has occurred from the side of the database, then try to push this into the query logging table (log_query).
            $lastError = self::db_last_error();
            throw new Exception($lastError);
        }
    }

    protected static function QueryExec($query) {
        return self::db_query($query);
    }

    protected static function ProcessResult(&$result) {
        $response = array();

        while ($line = self::db_fetch_array($result, self::$options->GetFormat())) {
            if (!self::$options->GetPreprocessData())
                $response[] = array_map('preprocess_data', $line);
            else
                $response[] = $line;
        }

        self::db_free_result($result);
        return $response;
    }

    protected static function WriteTestsResult($response) {
        if (!(!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) {
            $fp = fopen('test_query_response_json.txt', 'a+');
            $json = [
                'query' => str_replace(array("\r\n", "\r", "\n"), ' ', self::$query),
                'format' => self::$options->GetFormat(),
                'response' => $response,

            ];
            fwrite($fp, json_encode($json) . PHP_EOL);
            fclose($fp);
        }
    }

    public static function sql_handler_test($query, $format) {
        global $_STORAGE;

        if (!isset($_STORAGE['pids']))
            $_STORAGE['pids'] = array();

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

    public static function GetDefaultOptions() {
        return new SQLParamBase();
    }

    public static function ExistsScheme($schemaName, $options = null) {
        return DBCaller::sql(
            "SELECT EXISTS(SELECT 1 FROM information_schema.schemata WHERE schema_name = '$schemaName');",
            $options
        )[0]['exists'];
    }

    public static function sql($query, $options = null) {
        global $_STORAGE;
        self::$logs = array();
        self::$query = $query;

        if (is_null($options))
            $options = static::GetDefaultOptions();

        self::$options = $options;

        $prepareResponse = static::PrepareConnection($options->GetFormat());
        if (!is_null($prepareResponse))
            return $prepareResponse;

        $format = $options->GetFormat();
        if ($format != PGSQL_ASSOC && $format != PGSQL_NUM)
            throw new Exception("'$format' is unknown format!");

        self::$dbconn = static::custom_pg_connect($options->GetEncryptPassword(), $options->GetDefaultConnection());
        $pid = self::db_get_pid(self::$dbconn);

        static::BeforeQuery($pid, $options->GetQueryDescription());

        if (!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__')) {
            // Close session because next query into database can be very long and other queries not execute.
            $_STORAGE->pauseSession();
        }

        $result = static::QueryExec($query);

        if (!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__') && (session_status() == PHP_SESSION_NONE)) {
            // Reopen session after close.
            $_STORAGE->startSession();

        }

        unset($_STORAGE['pids'][$pid]);

        static::AfterQuery($result);
        $response = static::ProcessResult($result);
        self::db_close();
        static::WriteTestsResult($response);

        self::$dbconn = null;
        self::$query = '';

        return $response;
    }
}