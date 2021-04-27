<?php


class ApplicationInitBase
{
    public static function initStorage() {
        $storage = new WebStorage();

        if (isset($_COOKIE['private_key']))
            if ($_COOKIE['private_key'] !== '')
                $storage['private_key'] = $_COOKIE['private_key'];

        $GLOBALS['_STORAGE'] = $storage;
    }

    public static function initDatabase() {
        if (!isset($GLOBALS['_STORAGE']) && !isset($GLOBALS['_CONFIG']))
            return;

        global $_STORAGE, $_CONFIG;
        $database = null;
        if (!isset($_STORAGE['login']))
            $_STORAGE['login'] = $_CONFIG->dbDefaultUser;

        if (!isset($_STORAGE['password']))
            $_STORAGE['password'] = $_CONFIG->dbDefaultPass;

        $arr_conf = array(
            'host' => $_CONFIG->host, 'dbname' => $_CONFIG->dbname,
            'port' => $_CONFIG->port, 'user' => $_STORAGE['login'],
            'password' => $_STORAGE['password']
        );

        switch ($GLOBALS['_CONFIG']->databaseType) {
            case 'pgsql':
                $database = new DatabasePostgresql($arr_conf);
                break;
            case 'mysql':
                $database = new DatabaseMysql($arr_conf);
                break;
            case 'sqlite':
                break;
            default:
                $database = new DatabasePostgresql($arr_conf);
        }

        $_STORAGE['database'] = $database;
    }

    public static function initConfigFree() {
        $config = new ConfigBase();
        $config->init();
        $GLOBALS['_CONFIG'] = $config;
    }

    protected static function getNameClassMethods() {
        return 'methodsBase';
    }

    protected static function callSpecialMethods() {
    }

    public static function cors() {
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

    public static function request() {
        static::cors();
        global $_STORAGE, $_CONFIG;

        $usename = '';
        $pid_count = '';

        if (!$_STORAGE->IsSession()) {
            $usename = strval($_STORAGE['login']);
            $_CONFIG->dbname = $_STORAGE['dbname'] ?? $_CONFIG->dbname;

            if (!isset($_STORAGE['lang']))
                $_STORAGE['lang'] = $_CONFIG->dbDefaultLanguage;
        } else {
            $usename = call_user_func(static::getNameClassMethods() . '::' . 'getAnotherUsername');
        }

        $pid_count = isset($_STORAGE['pids']) ? count($_STORAGE['pids']) : 0;

        if (!isset($_POST['method'])) {
            static::callSpecialMethods();
            return json_encode(array('jsonrpc' => '2.0', 'result' => null, 'error' => 'method', 'usename' => $usename, 'pids' => $pid_count));
        }

        if (!isset($_POST['params'])) {
            return json_encode(array('jsonrpc' => '2.0', 'result' => null, 'error' => 'params', 'usename' => $usename, 'pids' => $pid_count));
        }

        $params = json_decode($_POST['params'], true);

        ob_start();
        $result = call_user_func_array(static::getNameClassMethods() . '::' . $_POST['method'], $params);
        ob_end_clean();

        $_STORAGE['login'] = $_STORAGE['login'] == 'guest' ? '' : $_STORAGE['login'];

        return json_encode(array('jsonrpc' => '2.0', 'result' => $result, 'error' => null, 'usename' => strval($_STORAGE['login']), 'pids' => $pid_count));
    }
}