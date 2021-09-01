<?php


class DbSqlController {
    protected static function GetObjectDatabase() {
        global $_STORAGE;

        if (isset($_STORAGE['database']))
            return $_STORAGE['database'];

        throw new Exception('Object database not found.');
    }

    protected static function GetObjectSql($query, $options) {
        return new SQLBase($query, $options);
    }

    public static function EscapeString($value) {
        return self::GetObjectDatabase()->db_escape_string($value);
    }

    public static function EscapeBytea($value) {
        return self::GetObjectDatabase()->db_escape_bytea($value);
    }

    public static function IdQuote($identifier) {
        return self::GetObjectDatabase()->id_quote($identifier);
    }

    public static function Connect($encrypt_password, $default_connection) {
        global $_STORAGE, $_CONFIG;
        $usename = '';
        $dbname = $_STORAGE['dbname'] ?? $_CONFIG->dbname;

        if ((isset($_COOKIE['private_key']) || isset($_STORAGE['private_key']))
                && !$default_connection) {
            $privateKey = $_COOKIE['private_key'] ?? $_STORAGE['private_key'];

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

    public static function Sql($query, $options = null) {
        global $_STORAGE;

        $databaseObject = self::GetObjectDatabase();
        $sqlObject = static::GetObjectSql($query, $options);

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

        $unstor = $_STORAGE['pids'];
        unset($unstor[$pid]);
        $_STORAGE['pids'] = $unstor;

        $sqlObject->AfterQuery($result);
        $response = $sqlObject->ProcessResult($result, $format);

        $databaseObject->db_close();
        $sqlObject->WriteTestsResult($response);

        return $response;
    }

    public static function SqlCountEstimate($query, $options = null) {
        $sqlObject = static::GetObjectSql($query, $options);
        $result =
            self::Sql(
                self::GetObjectDatabase()->get_explain_query() . " $query",
                $options
            );

        if (!$result)
            return array();

        return $sqlObject->ParseCountEstimateAnswer($result[0]);
    }

    public static function QueryExec($query) {
        return self::GetObjectDatabase()->db_query($query);
    }

    public static function relation($schemaName, $entityName) {
        return self::IdQuote($schemaName). '.' .self::IdQuote($entityName);
    }

    public static function type($value, $type) {
        return self::GetObjectDatabase()->type($value, $type);
    }

    public static function typeField($field, $type, $need_quote = false) {
        return self::GetObjectDatabase()->type_field($field, $type, $need_quote);
    }

    public static function GetUserDescription($username) {
        return self::Sql(
            self::GetObjectDatabase()->db_query_user_description($username)
        );
    }

    public static function NumericTruncate($numeric, $count = 0, $alias = false) {
        return self::GetObjectDatabase()->numeric_trunc($numeric, $count, $alias);
    }

    public static function ReturningPKey($pkey_column) {
        return self::GetObjectDatabase()->return_pkey_value($pkey_column);
    }

    public static function InsertValues($str_values) {
        return self::GetObjectDatabase()->wrap_insert_values($str_values);
    }

    public static function Like() {
        return self::GetObjectDatabase()->operator_like();
    }

    public static function Concat($array) {
        return self::GetObjectDatabase()->concat($array);
    }

    public static function FormatColumns($columns, $format) {
        return self::GetObjectDatabase()->format($columns, $format);
    }

    public static function RowToJson($columns) {
        return self::GetObjectDatabase()->row_to_json($columns);
    }

    public static function Collate() {
        return 'collate "' .self::GetObjectDatabase()->get_collate() .'"';
    }
}