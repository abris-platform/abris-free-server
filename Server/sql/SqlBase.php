<?php

class SQLBase
{
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
        return null;
    }

    public function BeforeQuery($pid) {
        global $_STORAGE, $_CONFIG;

        if (!isset($_STORAGE['pids']))
            $_STORAGE['pids'] = array();
        else {
            $tmpPids = $_STORAGE['pids'];
            $tmpPids[$pid] = array(
                'query' => $this->query,
                'desc' => $this->GetOptions()->GetQueryDescription(),
                'timestamp' => date('Y-m-d H:i:s', time())
            );
            $_STORAGE['pids'] = $tmpPids;
        }

    }

    public function AfterQuery($result) {
        if (!$result) {
            // If an error has occurred from the side of the database, then try to push this into the query logging table (log_query).
            $lastError = $this->database->db_last_error();
            if($lastError)
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

    public static function ExistsScheme($schemaName, $options = null) {
        global $_STORAGE;
        // TODO move to database class.
        return $_STORAGE['Controller']->sql(
            "SELECT EXISTS(SELECT 1 FROM information_schema.schemata WHERE schema_name = '$schemaName');",
            $options
        )[0]['exists'];
    }

    public static function GetDefaultOptions() {
        return new SQLParamBase();
    }

    public function ParseCountEstimateAnswer($answer_json) {
        return array(
            'plan_rows' => $this->database->get_plan_row_explain($answer_json),
            'total_cost' => $this->database->get_total_cost_explain($answer_json)
        );
    }
}