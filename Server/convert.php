<?php


class Convert
{
    public static function id_quote($identifier)
    {
        global $db_mysql;
        if($db_mysql)
            return '`' . str_replace('`', '``', $identifier) . '`';
        else
            return '"' . str_replace('"', '""', $identifier) . '"';
    }

    public static function type($value, $type)
    {
        global $db_mysql;
        if($db_mysql)
            return "CONVERT('$value','$type')";
        else
            return "'$value'::$type";
    }

}