<?php

abstract class DatabaseAbstract implements DatabaseInterface, DataConvertInterface {
    protected $connect;

    public function set_bytea_output($style = 'escape') {
        return '';
    }

    public function set_interval_style($style = 'iso_8601') {
        return '';
    }

    protected function is_connected() {
        return boolval($this->connect);
    }
}