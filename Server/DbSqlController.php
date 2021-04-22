<?php


class DbSqlController {
    protected static function GetObjectDatabase() {
        global $_STORAGE;

        if (isset($_STORAGE['database']))
            return $_STORAGE['database'];

        throw new Exception('Object database not found.');
    }

    public static function EscapeString($value) {
        return self::GetObjectDatabase()->db_escape_string($value);
    }

    public static function Connect($encrypt_password, $default_connection) {
        global $_STORAGE, $_CONFIG;
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
                "$_CONFIG->anotherPrefLog@$session_usename",
                "$dbname@$session_usename"
            );

            foreach ($variants_login as $login) {
                $usename = $login;
                $dbconnect = self::GetObjectDatabase()->db_connect(
                    array(
                        'host' => $_CONFIG->host, 'dbname' => $_CONFIG->dbname,
                        'port' => $_CONFIG->port, 'user' => $login,
                        'password' => $password
                    )
                );
                if ($dbconnect) {
                    $_STORAGE['full_usename'] = $login;
                    return $dbconnect;
                }
            }

            $usename = $session_usename;
        }
        else {
            $usename = $_CONFIG->dbDefaultUser;
            $dbconnect = self::GetObjectDatabase()->db_connect(
                array(
                    'host' => $_CONFIG->host, 'dbname' => $_CONFIG->dbname,
                    'port' => $_CONFIG->port, 'user' => $usename,
                    'password' => $_CONFIG->dbDefaultPass
                )
            );
            if ($dbconnect)
                return $dbconnect;
        }

        // If returns not worked in previous stages, then connect not worked at all
        unset_auth_session();
        throw new Exception("Unable to connect by user $usename to system.");
    }

    protected static function GetObjectSql($options) {
        return new SQLBase($options);
    }

    public static function Sql($query, $options = null) {
        global $_STORAGE;
        $logs = array();
        $databaseObject = self::GetObjectDatabase();
        $sqlObject = static::GetObjectSql($options);


        $prepareResponse = $sqlObject->PrepareConnection();
        if (!is_null($prepareResponse))
            return $prepareResponse;

        $format = $databaseObject->get_format($sqlObject->GetOptions()->GetFormat());
        $databaseObject->db_type_compare($format);

        self::Connect(
            $sqlObject->GetOptions()->GetEncryptPassword(),
            $sqlObject->GetOptions()->GetDefaultConnection()
        );
        $pid = $databaseObject->db_get_pid();

        $databaseObject->set_bytea_output();
        $databaseObject->set_interval_style();

        $sqlObject->BeforeQuery($pid);

        if (!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__')) {
            // Close session because next query into database can be very long and other queries not execute.
            $_STORAGE->pauseSession();
        }

        $result = self::QueryExec($query);

        if (!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__') && (session_status() == PHP_SESSION_NONE)) {
            // Reopen session after close.
            $_STORAGE->startSession();

        }

        unset($_STORAGE['pids'][$pid]);

        $sqlObject->AfterQuery($result);
        $response = $sqlObject->ProcessResult($result);

        $databaseObject->db_close();
        $sqlObject->WriteTestsResult($response);

        return $response;
    }

    public static function QueryExec($query) {
        return self::GetObjectDatabase()->db_query($query);
    }
}