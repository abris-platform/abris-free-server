<?php

class DatabasePostgresql extends DatabaseAbstract
{
    public function db_connect($data)
    {
        $this->connect = @pg_connect("host=$data[host] dbname=$data[dbname] port=$data[port] user=$data[user] password=$data[password]");
        return boolval($this->connect);
    }

    public function db_get_pid()
    {
        return pg_get_pid($this->connect);
    }

    public function db_last_error()
    {
        return pg_last_error($this->connect);
    }

    public function db_fetch_array($result, $format)
    {
        return pg_fetch_array($result, NULL, $format);
    }

    public function db_free_result($result)
    {
        return pg_free_result($result);
    }

    public function db_query($query)
    {
        $result = @pg_query($this->connect, $query);
        if (!$result)
            throw new Exception($this->db_last_error());
        return $result;
    }

    public function db_close()
    {
        return pg_close($this->connect);
    }

    public function db_escape_string($value)
    {
        return pg_escape_string($value);
    }

    public function db_type_compare($format){
        if ($format != PGSQL_ASSOC && $format != PGSQL_NUM)
            throw new Exception("'$format' is unknown format!");
    }

    public function get_format($format){
        return $format === 'object' ? PGSQL_ASSOC : PGSQL_NUM;
    }

    public function set_bytea_output($style = 'escape'){
        return $this->db_query('SET bytea_output = "'.$style.'";');
    }

    public function set_interval_style($style = 'iso_8601'){
        return $this->db_query( "SET intervalstyle = '$style';");
    }

}