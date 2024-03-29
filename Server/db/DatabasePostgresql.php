<?php

class DatabasePostgresql extends DatabaseAbstract
{
    public function db_connect($data = null) {
        if (!boolval($this->connect)) {
            global $_STORAGE;

            if (is_null($data))
                $data = $this->config;
                        
            $password = $data['password'];
            $user = $data['user'];
                
            if (isset($_STORAGE['private_key'])) {
                $password = DecryptStr($_STORAGE['password'], $_STORAGE['private_key']);
                $user = $_STORAGE['login'];
            }
            
            $this->connect = @pg_connect("host=$data[host] dbname=$data[dbname] port=$data[port] user=$user password=$password");
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
        $this->db_connect();
        return pg_query($this->connect, $query);
    }

    public function db_close() {
        if (pg_close($this->connect)) {
            $this->connect = null;
            return true;
        }
        return false;
    }

    public function db_escape_string($value) {
        $this->db_connect();
        return pg_escape_string($value);
    }

    public function db_escape_bytea($value) {
        return pg_escape_bytea($value);
    }

    public function db_unescape_bytea($value) {
        $this->db_connect();
        return pg_unescape_bytea($value);
    }

    public function db_type_compare($format) {
        if ($format != PGSQL_ASSOC && $format != PGSQL_NUM)
            throw new Exception("'$format' is unknown format!");
    }

    public function db_query_user_description($username) {
        return "SELECT rolname AS user,description AS comment
                    FROM pg_roles r
                    JOIN pg_shdescription c ON c.objoid = r.oid 
                WHERE r.rolname = '$username';";
    }

    public function db_get_count_affected_row($result) {
        return pg_affected_rows($result);
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

    public function set_datestyle($style = 'GERMAN'){
        pg_query($this->connect, "SET datestyle TO '$style';");
    }

    public function id_quote($identifier) {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    public function type($value, $type) {
        if (!$type) return "'$value'";
        return "'$value'::$type";
    }

    public function type_field($field, $type, $need_quote = false) {
        if (!$type)
            return $need_quote ? "\"$field\"" : $field;
        return $need_quote ? "\"$field\"::$type" : "$field::$type";
    }

    public function get_explain_query() {
        return 'EXPLAIN (format json)';
    }

    public function get_plan_row_explain($answer) {
        $arr_explain = json_decode($answer['QUERY PLAN'], true)[0];
        return $arr_explain['Plan']['Plan Rows'];
    }

    public function get_total_cost_explain($answer) {
        $arr_explain = json_decode($answer['QUERY PLAN'], true)[0];
        return $arr_explain['Plan']['Total Cost'];
    }

    public function numeric_trunc($numeric, $count = 0, $alias = false) {
        $cmd = "trunc($numeric, $count)";
        return $alias ? "$cmd AS trunc" : $cmd;
    }

    public function return_pkey_value($pkey_column) {
        return "RETURNING $pkey_column";
    }

    public function wrap_insert_values($str_values) {
        return "SELECT $str_values";
    }

    public function operator_like() {
        return 'ILIKE';
    }

    public function format($columns_array, $format) {
        return "format('$format', " .implode(', ', $columns_array) .")";
    }

    public function row_to_json($columns_array) {
        return '(row_to_json(row(' .implode(', ', $columns_array) .'::text))::text)';
    }

    public function get_collate() {
        return 'C';
    }

    public function get_name_current_database_query() {
        return "current_database()";
    }

    public function get_pids_database_query($dbname = '') {
        $where_cond = '';
        if ($dbname)
            $where_cond = "WHERE datname = $dbname";
        return "SELECT * FROM pg_stat_activity $where_cond;";
    }

    public function kill_pid_query($pid) {
        return "SELECT pg_terminate_backend($pid);";
    }

    public function distinct_on($distinctfields){
        return 'DISTINCT ON (' . $distinctfields . ')';
    }

    public function desc() {
        return parent::desc() .' NULLS LAST';
    }
}
