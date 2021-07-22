<?php

class DatabasePostgresql extends DatabaseAbstract
{
    public function db_connect($data = null) {
        if (!boolval($this->connect)) {
            global $_STORAGE;

            if (is_null($data))
                $data = $this->config;

            $password = isset($_STORAGE['private_key']) ? DecryptStr($_STORAGE['password'], $_STORAGE['private_key']) : $data['password'];

            $this->connect = @pg_connect("host=$data[host] dbname=$data[dbname] port=$data[port] user=$data[user] password=$password");
        }
        return $this->connect;
    }

    public function db_get_pid() {
        $this->db_connect();
        return pg_get_pid($this->connect);
    }

    public function db_last_error() {
        return pg_last_error($this->connect);
    }

    public function db_fetch_array($result, $format) {
        return pg_fetch_array($result, NULL, $format);
    }

    public function db_free_result($result) {
        return pg_free_result($result);
    }

    public function db_query($query) {
        return pg_query($this->connect, $query);
    }

    public function db_close() {
        if (pg_close($this->connect))
            $this->connect = null;
    }

    public function db_escape_string($value) {
        $this->db_connect();
        return pg_escape_string($value);
    }

    public function db_escape_bytea($value) {
        return pg_escape_bytea($value);
    }

    public function db_type_compare($format) {
        if ($format != PGSQL_ASSOC && $format != PGSQL_NUM)
            throw new Exception("'$format' is unknown format!");
    }

    public function get_format($format) {
        return $format === 'object' ? PGSQL_ASSOC : PGSQL_NUM;
    }

    public function set_bytea_output($style = 'escape') {
        pg_query($this->connect, 'SET bytea_output = "' . $style . '";');
    }

    public function set_interval_style($style = 'iso_8601') {
        pg_query($this->connect, "SET intervalstyle = '$style';");
    }

    public function id_quote($identifier) {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    public function type($value, $type) {
        if (!$type) return "'$value'";
        return "'$value'::$type";
    }

    public function type_field($field, $type) {
        if (!$type) return "\"$field\"";
        return "\"$field\"::$type";
    }
}