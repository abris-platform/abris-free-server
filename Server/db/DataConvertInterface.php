<?php

interface DataConvertInterface
{
    public function id_quote($identifier);

    public function type($value, $type);

    public function type_field($field, $type, $need_quote = false);

    public function get_format($format);

    public function set_bytea_output($style = 'escape');

    public function set_interval_style($style = 'iso_8601');

    public function db_query_user_description($username);

    public function get_explain_query();

    public function get_plan_row_explain($answer);

    public function get_total_cost_explain($answer);

    public function numeric_trunc($numeric, $count = 0, $alias = false);

    public function return_pkey_value($pkey_column);

    public function wrap_insert_values($str_values);

    public function operator_like();

    public function concat($array, $delimiter = ' ');

    public function row_to_json($columns_array);

    public function format($columns_array, $format);

    public function get_collate();

    public function distinct_on($distinctfields);

    public function desc();
}