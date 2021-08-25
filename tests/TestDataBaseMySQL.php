<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

$arr_conf = array(
    'host' => 'abris.site',
    'dbname' => 'bookings',
    'port' => '3306',
    'user' => '***',
    'password' => '********'
);

$database = new DatabaseMysql($arr_conf);

ApplicationInitBase::initStorage();
$_STORAGE['database'] = $database;
/*
$GLOBALS['_CONFIG']->databaseType = 'mysql';
ApplicationInitBase::initDatabase();
*/

class TestDataBaseMySQL extends TestCase {
    public function test_db_connect_sql() {
        global $database, $_STORAGE;

        $_STORAGE['database']->db_connect();
        $res = $_STORAGE['database']->db_query('select version();');
        $res2 = $_STORAGE['database']->db_fetch_array($res, 1);
        $this->assertNotNull($res2['version()']);
        $res2 = $_STORAGE['database']->db_free_result($res);
        $this->assertNull($res2);

        try {
            $res = $_STORAGE['database']->db_query('select1 version();');
        } catch (Exception $e) {
            return;
        }
    }

    public function test_db_format_sql() {
        global $database, $_STORAGE;

        $res = $_STORAGE['database']->db_escape_string("test");
        $_STORAGE['database']->db_type_compare(1);
        $res = $_STORAGE['database']->get_format("array");
        $this->assertNotNull($res);
        $res = $_STORAGE['database']->id_quote('test');
        $this->assertEquals($res, "`test`");
        $res = $_STORAGE['database']->type("111", "text");
        $this->assertEquals($res, "CONVERT('111','text')");
        $res = $_STORAGE['database']->type_field("number", "text");
        $this->assertEquals($res, "CONVERT(`number`,'text')");
        $res = $_STORAGE['database']->db_get_pid();
        $this->assertEquals($res, "");
        $_STORAGE['database']->set_bytea_output();
        $_STORAGE['database']->set_interval_style();

        try {
            $res = $_STORAGE['database']->db_type_compare(5);
        } catch (Exception $e) {
        }

        $res = $_STORAGE['database']->db_close();
        $this->assertEquals($res, true);
    }
}























