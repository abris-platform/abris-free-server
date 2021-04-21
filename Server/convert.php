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
        if(!$type) return "'$value'";
        if($db_mysql)
            return "CONVERT('$value','$type')";
        else
            return "'$value'::$type";
    }

    public static function typeField($field, $type)
    {
        global $db_mysql;
        if(!$type) return "\"$field\"";
        if($db_mysql)
            return "CONVERT('$field','$type')";
        else
            return "\"$field\"::$type";
    }

    public static function relation($schema, $table) {
        global $dbUnrollViews;
        $rel = self::id_quote($schema) . "." . self::id_quote($table);
        if (in_array($rel, $dbUnrollViews ?: array())) {
            $r = DBCaller::sql("select pg_get_viewdef(to_regclass('$rel'));");
            return "(" . trim($r[0]["pg_get_viewdef"], ';') . ")";
        } else
            return $rel;
    }

}