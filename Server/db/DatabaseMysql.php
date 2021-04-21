<?php

class DatabaseMysql extends DatabaseAbstract
{
    public function db_connect($data)
    {
        $this->connect = @mysqli_connect($data['host'], $data['user'], $data['password'], $data['dbname']);
        return boolval($this->connect);
    }

    public function db_get_pid()
    {
        return '';
    }

    public function db_last_error()
    {
        return mysqli_error($this->connect);
    }

    public function db_fetch_array($result, $format)
    {
        return mysqli_fetch_array($result, $format);
    }

    public function db_free_result($result)
    {
        mysqli_free_result($result);
    }

    public function db_query($query)
    {
        $result = @mysqli_query($this->connect, $query);
        if (!$result)
            throw new Exception($this->db_last_error());
        return $result;
    }

    public function db_close()
    {
        return mysqli_close($this->connect);
    }

    public function db_escape_string($value)
    {
        return mysqli_real_escape_string($this->connect, $value);
    }

    public function db_type_compare($format){
        if ($format != MYSQLI_ASSOC && $format != MYSQLI_NUM)
            throw new Exception("'$format' is unknown format!");
    }

    public function get_format($format){
        return $format === 'object' ? MYSQLI_ASSOC :  MYSQLI_NUM;
    }

}
