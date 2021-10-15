<?php

abstract class DatabaseAbstract implements DatabaseInterface, DataConvertInterface {
    protected $connect;
    protected $config;

    public function __construct($config){
        $this->config = $config;
    }

    public function set_bytea_output($style = 'escape') {
        return '';
    }

    public function set_interval_style($style = 'iso_8601') {
        return '';
    }

    public function db_query($query) {
        $this->db_connect();
    }

    public function get_explain_query() {
        return '';
    }

    public function get_plan_row_explain($answer) {
        return 0;
    }

    public function get_total_cost_explain($answer) {
        return 0;
    }

    public function concat($array, $delimiter = "' '") {
        $res = array();

        foreach ($array as $value) {
            $res[] = $value;
            $res[] = $delimiter;
        }
        unset($res[count($res) - 1]);

        return 'CONCAT('. implode(', ', $res) .')';
    }

    public function desc() {
        return 'DESC';
    }
}