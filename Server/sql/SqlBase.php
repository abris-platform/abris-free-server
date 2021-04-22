<?php

class SQLBase
{
    protected $logs;
    protected $query;
    protected $options;
    protected $database;

    public function __construct($query, $options = null) {
        global $_STORAGE;

        if (is_null($options))
            $options = static::GetDefaultOptions();

        $this->options = $options;
        $this->query = $query;

        if (isset($_STORAGE['database']))
            $this->database = $_STORAGE['database'];
        else
            throw new Exception('Database object not found!');
    }

    public function GetOptions() {
        return $this->options;
    }

    public function PrepareConnection() {
        $format = $this->options->GetFormat();

        if (!(!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) {
            $response = $this->sql_handler_test($this->query, $format);
            if ($response != 'new_query_test')
                return $response;
        }
        return null;
    }

    public function BeforeQuery($pid) {
        global $_STORAGE, $_CONFIG;

        if (!isset($_STORAGE['pids']))
            $_STORAGE['pids'] = array();
        else
            $_STORAGE['pids'][$pid] = array(
                'query' => $this->query,
                'desc' => $this->GetOptions()->GetQueryDescription(),
                'timestamp' => date('Y-m-d H:i:s', time())
            );

        if ($this->options->IsLogFile())
            file_put_contents("$_CONFIG->databaseType.log", date('Y-m-d H:i:s', time()) . '\t' . ($_SERVER['REMOTE_ADDR'] ?? 'cli') . '\t' . $pid . '\t' . $this->query . '\n', FILE_APPEND);

    }

    public function AfterQuery($result) {
        if (!$result) {
            // If an error has occurred from the side of the database, then try to push this into the query logging table (log_query).
            $lastError = $this->database->db_last_error();
            throw new Exception($lastError);
        }
    }

    public function ProcessResult($result, $format) {
        $response = array();

        while ($line = $this->database->db_fetch_array($result, $format)) {
            if (!$this->options->GetPreprocessData())
                $response[] = array_map('preprocess_data', $line);
            else
                $response[] = $line;
        }

        $this->database->db_free_result($result);
        return $response;
    }

    public function WriteTestsResult($response) {
        if (!(!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__'))) {
            $fp = fopen('test_query_response_json.txt', 'a+');
            $json = [
                'query' => str_replace(array("\r\n", "\r", "\n"), ' ', $this->query),
                'format' => $this->options->GetFormat(),
                'response' => $response,

            ];
            fwrite($fp, json_encode($json) . PHP_EOL);
            fclose($fp);
        }
    }

    public function sql_handler_test($query, $format) {
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

    public function ExistsScheme($schemaName, $options = null) {
        return DbSqlController::sql(
            "SELECT EXISTS(SELECT 1 FROM information_schema.schemata WHERE schema_name = '$schemaName');",
            $options
        )[0]['exists'];
    }
}