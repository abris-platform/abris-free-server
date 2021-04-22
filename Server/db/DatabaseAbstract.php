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

}