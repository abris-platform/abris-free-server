<?php

interface DataConvertInterface {
    public function id_quote($identifier);

    public function type($value, $type);

    public function type_field($field, $type);

    public function get_format($format);

    public function set_bytea_output($style = 'escape');

    public function set_interval_style($style = 'iso_8601');
}