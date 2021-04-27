<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
require_once dirname(__FILE__)."/../Server/db/DatabasePostgresql.php";

class TestDataBase extends TestCase
{
    public function test_db_onnect(){
        global $_STORAGE;
        $res = $_STORAGE['database']->db_connect();
        $this->assertNotNull($res);
    }

    public function test_db_get_pid(){
        global $_STORAGE;
        $res = $_STORAGE['database']->db_get_pid();
        $this->assertNotNull($res);
    }

    public function test_db_query(){
        global $_STORAGE;
        $query = 'select version()';
        $res = $_STORAGE['database']->db_query($query);
        $this->assertNotNull($res);
        $res2 = $_STORAGE['database']->db_fetch_array($res, 1);
        $this->assertNotNull($res2['version']);
        $res2 = $_STORAGE['database']->db_free_result($res);
        $this->assertNotNull($res2);


    }

    public function test_db_last_error(){
        global $_STORAGE;
        $res = $_STORAGE['database']->db_last_error();
        $this->assertEquals($res,"");
    }

    public function test_set(){
        global $_STORAGE;
        $res = $_STORAGE['database']->set_bytea_output();
        $this->assertNull($res);
        $res = $_STORAGE['database']->set_interval_style();
        $this->assertNull($res);

    }

    public function test_db_format(){
        global $_STORAGE;
        $format = 'object';
        $res = $_STORAGE['database']->get_format($format);
        $this->assertEquals($res, 1);
        $res = $_STORAGE['database']->db_type_compare($res);
        $this->assertNull($res);
        try {
            $res = $_STORAGE['database']->db_type_compare(5);
        } catch (Exception $e) {return;}
    }

    public function test_db_close(){
        global $_STORAGE;
        $res = $_STORAGE['database']->db_close();
        $this->assertNull($res);
    }

}