<?php


class DBCaller
{
    protected static function GetCurrentSQL() {
        return class_exists('SQL') ? 'SQL' : 'SQLBase';
    }

    public static function sql($query, $options = null) {
        $className = self::GetCurrentSQL();
        return call_user_func("$className::sql", $query, $options);
    }

    public static function GetDefaultOptions() {
        $className = self::GetCurrentSQL();
        return call_user_func("$className::GetDefaultOptions");
    }
}