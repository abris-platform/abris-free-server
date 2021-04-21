<?php

interface DatabaseInterface
{
    public function db_connect($data);

    public function db_get_pid();

    public function db_last_error();

    public function db_fetch_array($result, $format);

    public function db_free_result($result);

    public function db_query($query);

    public function db_close();

    public function db_escape_string($value);

    public function db_type_compare($format);

    public function get_format($format);

    public function set_bytea_output($style = 'escape');

    public function  set_interval_style($style = 'iso_8601');
}