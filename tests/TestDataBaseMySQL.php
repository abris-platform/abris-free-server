<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . "/../Server/db/DatabasePostgresql.php";
$arr_conf = array(
    'host' => 'abris.site',
    'dbname' => 'bookings',
    'port' => '3306',
    'user' => 'testuser',
    'password' => '159357qwerty'
);

$database = new DatabaseMysql($arr_conf);

class TestDataBaseMySQL extends TestCase{
    public function test_db_connect_sql(){
        global $database;

        $database->db_connect();
        $res = $database->db_query('select version();');
        $res2 = $database->db_fetch_array($res, 1);
        $this->assertNotNull($res2['version()']);
        $res2 = $database->db_free_result($res);
        $this->assertNull($res2);

        try {
            $res =$database->db_query('select1 version();');
        } catch (Exception $e) {return;}
    }

    public function test_db_format_sql(){
        global $database;
        $res = $database->db_escape_string("test");
        $database->db_type_compare(1);
        $res = $database->get_format("array");
        $this->assertNotNull($res);
        $res = $database->id_quote('test');
        $this->assertEquals($res,"`test`");
        $res = $database->type("111","text");
        $this->assertEquals($res,"CONVERT('111','text')");
        $res = $database->type_field("number","text");
        $this->assertEquals($res,"CONVERT(`number`,'text')");
        $res = $database->db_get_pid();
        $this->assertEquals($res,"");
        $database->set_bytea_output();
        $database->set_interval_style();

        try {
            $res = $database->db_type_compare(5);
        } catch (Exception $e) {}

        $res = $database->db_close();
        $this->assertEquals($res,true);



    }




}























