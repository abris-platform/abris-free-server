<?php

interface DatabaseInterface
{
    public function db_connect($data = null);

    public function db_get_pid();

    public function db_last_error();

    public function db_fetch_array($result, $format);

    public function db_free_result($result);

    public function db_query($query);

    public function db_close();

    public function db_escape_string($value);

    public function db_escape_bytea($value);

    public function db_type_compare($format);

    public function db_get_count_affected_row($result);

    public function get_pids_database_query($dbname);

    public function get_name_current_database_query();

    public function kill_pid_query($pid);
}