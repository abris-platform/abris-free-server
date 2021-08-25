<?php

class DatabaseMysql extends DatabaseAbstract
{
    public function db_connect($data = null) {
        if(!boolval($this->connect) || !property_exists($this->connect, 'server_info')) {
            global $_STORAGE;

            if (is_null($data))
                $data = $this->config;

            $password = isset($_STORAGE['private_key']) ? DecryptStr($_STORAGE['password'], $_STORAGE['private_key']) : $data['password'];
            $this->connect = new mysqli($data['host'], $data['user'], $password, $data['dbname']);

            if($this->connect->connect_errno)
                throw new Exception("Connect failed: $this->connect->connect_error \n");
        }
        return $this->connect;
    }

    public function db_get_pid() {
        return '';
    }

    public function db_last_error() {
        return $this->connect->error;
    }

    public function db_fetch_array($result, $format) {
        return $result->fetch_array($format);
    }

    public function db_free_result($result) {
        $result->free_result();
    }

    public function db_query($query) {
        $this->db_connect();

        $result = $this->connect->query($query);
        if ($this->connect->connect_errno)
            throw new Exception($this->db_last_error());
        return $result;
    }

    public function db_close() {
        return $this->connect->close();
    }

    public function db_escape_string($value) {
        $this->db_connect();
        return $this->connect->real_escape_string($value);
    }

    public function db_escape_bytea($value) {
        return $value;
    }

    public function db_type_compare($format) {
        if ($format != MYSQLI_ASSOC && $format != MYSQLI_NUM)
            throw new Exception("'$format' is unknown format!");
    }

    public function db_query_user_description($username) {
        return "CALL meta.get_user_description(
                    if(locate('@', '$username') = 0, '$username', left('$username', locate('@', '$username')  - 1))
                );";
    }

    public function get_format($format) {
        return $format === 'object' ? MYSQLI_ASSOC : MYSQLI_NUM;
    }

    public function id_quote($identifier) {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public function type($value, $type) {
        if (!$type) return "'$value'";
        return "CONVERT('$value','$type')";
    }

    public function type_field($field, $type) {
        if (!$type) return "`$field`";
        return "CONVERT(`$field`,'$type')";
    }

    public function get_explain_query() {
        return 'EXPLAIN FORMAT=JSON';
    }

    public function get_total_cost_explain($answer) {
        $arr_explain = json_decode($answer['EXPLAIN'], true);
        return $arr_explain['query_block']['cost_info']['query_cost'];
    }

    public function numeric_trunc($numeric, $count = 0, $alias = false) {
        $cmd = "truncate($numeric, $count)";
        return $alias ? "$cmd AS truncate" : $cmd;
    }

    public function return_pkey_value($pkey_column) {
        return '; SELECT LAST_INSERT_ID()';
    }

    public function wrap_insert_values($str_values) {
        return "VALUES $str_values";
    }
    public function operator_like() {
        return 'LIKE';
    }

    public function format($columns_array, $format) {
        return implode('|||', $columns_array);
    }

    private function add_element(&$source, $element, &$findex) {
        $source[] = "'f$findex'";
        $source[] = "$element";
        $findex++;
    }

    public function row_to_json($columns_array) {
        $columns = array();

        $findex = 1;
        $separator = '|||'; // if output from function format.
        if (strpos($columns_array[0], '|||')) {
            foreach (explode($separator, $columns_array[0]) as $item) {
                $this->add_element($columns, $item, $findex);
            }
        }
        else {
            $this->add_element($columns, $columns_array[0], $findex);
        }

        for ($index = 1; $index < count($columns_array); $index++) {
            $this->add_element($columns, $columns_array[$index], $findex);
        }

        return '(json_object(' .implode(', ', $columns) .'))';
    }

    public function get_collate() {
        return 'utf8mb4_bin';
    }
}
