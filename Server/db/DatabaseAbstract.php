<?php

abstract class DatabaseAbstract implements DatabaseInterface {
    protected $connect;

    public function set_bytea_output($style = 'escape'){
        return '';
    }

    public function set_interval_style($style = 'iso_8601'){
        return '';
    }
}