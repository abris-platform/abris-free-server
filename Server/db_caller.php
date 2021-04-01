<?php

$currentDir = dirname(__FILE__);

require_once "$currentDir/db_base.php";
if (file_exists("db.php"))
    require_once "db.php";


class DBCaller
{
    protected static function GetCurrentSQL() {
        return class_exists('SQL') ? 'SQL' : 'SQLBase';
    }

    public static function sql($query, $options = null) {
        $className = self::GetCurrentSQL();
        return call_user_func("$className::sql", $query, $options);
    }

    // TODO под удаление.
    public static function sql_old($query, $do_not_preprocess = false, $logDb = false, $format = 'object', $query_description = '', $encrypt_pass = true, $default_connection = false) {
        return sql(
            $query, $do_not_preprocess, $logDb,
            $format, $query_description, $encrypt_pass,
            $default_connection
        );
    }

    public static function GetDefaultOptions() {
        $className = self::GetCurrentSQL();
        return call_user_func("$className::GetDefaultOptions");
    }
}