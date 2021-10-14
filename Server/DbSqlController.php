<?php


class DbSqlController
{
    protected function GetObjectDatabase() {
        global $_STORAGE;

        if (isset($_STORAGE['database']))
            return $_STORAGE['database'];

        throw new Exception('Object database not found.');
    }

    protected function GetObjectSql($query, $options) {
        return new SQLBase($query, $options);
    }

    public function EscapeString($value) {
        return $this->GetObjectDatabase()->db_escape_string($value);
    }

    public function EscapeBytea($value) {
        return $this->GetObjectDatabase()->db_escape_bytea($value);
    }

    public function IdQuote($identifier) {
        return $this->GetObjectDatabase()->id_quote($identifier);
    }

    public function Connect($encrypt_password, $default_connection) {
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
                $dbconnect = $this->GetObjectDatabase()->db_connect(
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
        } else {
            $usename = empty($_STORAGE['login']) ? $_CONFIG->dbDefaultUser : $_STORAGE['login'];
            $pass = empty($_STORAGE['password']) ? $_CONFIG->dbDefaultPass : $_STORAGE['password'];
            $dbconnect = $this->GetObjectDatabase()->db_connect(
                array(
                    'host' => $_CONFIG->host, 'dbname' => $_CONFIG->dbname,
                    'port' => $_CONFIG->port, 'user' => $usename,
                    'password' => $pass
                )
            );
            if ($dbconnect)
                return $dbconnect;
        }

        // If returns not worked in previous stages, then connect not worked at all
        unset_auth_session();
        throw new Exception("Unable to connect by user $usename to system.");
    }

    public function Sql($query, $options = null) {
        global $_STORAGE;

        $databaseObject = $this->GetObjectDatabase();
        $sqlObject = $this->GetObjectSql($query, $options);

        $prepareResponse = $sqlObject->PrepareConnection();
        if (!is_null($prepareResponse))
            return $prepareResponse;

        $format = $databaseObject->get_format($sqlObject->GetOptions()->GetFormat());
        $databaseObject->db_type_compare($format);

        $this->Connect(
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

        $result = $this->QueryExec($query);

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
        return $response;
    }

    public function SqlCountEstimate($query, $options = null) {
        $sqlObject = $this->GetObjectSql($query, $options);
        $result =
            $this->Sql(
                $this->GetObjectDatabase()->get_explain_query() . " $query",
                $options
            );

        if (!$result)
            return array();

        return $sqlObject->ParseCountEstimateAnswer($result[0]);
    }

    public function QueryExec($query) {
        return $this->GetObjectDatabase()->db_query($query);
    }

    public function relation($schemaName, $entityName) {
        return $this->IdQuote($schemaName) . '.' . $this->IdQuote($entityName);
    }

    public function type($value, $type) {
        return $this->GetObjectDatabase()->type($value, $type);
    }

    public function typeField($field, $type, $need_quote = false) {
        return $this->GetObjectDatabase()->type_field($field, $type, $need_quote);
    }

    public function GetUserDescription($username) {
        return $this->Sql(
            $this->GetObjectDatabase()->db_query_user_description($username)
        );
    }

    public function NumericTruncate($numeric, $count = 0, $alias = false) {
        return $this->GetObjectDatabase()->numeric_trunc($numeric, $count, $alias);
    }

    public function ReturningPKey($pkey_column) {
        return $this->GetObjectDatabase()->return_pkey_value($pkey_column);
    }

    public function InsertValues($str_values) {
        return $this->GetObjectDatabase()->wrap_insert_values($str_values);
    }

    public function Like() {
        return $this->GetObjectDatabase()->operator_like();
    }

    public function Concat($array) {
        return $this->GetObjectDatabase()->concat($array);
    }

    public function FormatColumns($columns, $format) {
        return $this->GetObjectDatabase()->format($columns, $format);
    }

    public function RowToJson($columns) {
        return $this->GetObjectDatabase()->row_to_json($columns);
    }

    public function Collate() {
        return 'collate "' . $this->GetObjectDatabase()->get_collate() . '"';
    }

    public function GetDefaultOptions() {
        return new SQLParamBase();
    }

    public function GetAllDbPIDs($dbname = null) {
        $db = $this->GetObjectDatabase();

        if (empty($dbname))
            $dbname = $db->get_name_current_database_query();

        return $db->get_pids_database_query($dbname);
    }

    public function KillProcess($pid) {
        global $_STORAGE;
        $db = $this->GetObjectDatabase();

        if (isset($_STORAGE['pids'][$pid])) {
            return $this->Sql($db->kill_pid_query($pid));
        }

        return false;
    }

    public function distinct_on($distinctfields) {
        return $this->GetObjectDatabase()->distinct_on($distinctfields);
    }
}