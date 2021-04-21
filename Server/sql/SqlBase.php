<?php

class SQLBase
{
    protected static $logs;
    protected static $query;
    protected static $options;

    public static function PrepareConnection($format) {
        if (!(!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) {
            $response = self::sql_handler_test(self::$query, $format);
            if ($response != 'new_query_test')
                return $response;
        }
        return null;
    }

    public static function BeforeQuery($pid, $query_description) {
        global $_STORAGE, $_CONFIG;

        if (!isset($_STORAGE['pids']))
            $_STORAGE['pids'] = array();
        else
            $_STORAGE['pids'][$pid] = array(
                'query' => self::$query,
                'desc' => $query_description,
                'timestamp' => date('Y-m-d H:i:s', time())
            );

        if (self::$options->IsLogFile())
            file_put_contents('sql.log', date('Y-m-d H:i:s', time()) . '\t' . ($_SERVER['REMOTE_ADDR'] ?? 'cli') . '\t' . $pid . '\t' . self::$query . '\n', FILE_APPEND);

    }

    public static function AfterQuery($result) {
        global $_STORAGE;

        if (!$result) {
            // If an error has occurred from the side of the database, then try to push this into the query logging table (log_query).
            $lastError = self::db_last_error();
            throw new Exception($lastError);
        }
    }

    public static function ProcessResult(&$result) {
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

    public static function WriteTestsResult($response) {
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
    }
}