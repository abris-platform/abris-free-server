<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;


class TestDataBaseMySQL extends TestCase
{
    private function debug_object($object) {
        throw new Exception(print_r($object, true));
    }

    protected static function GetConfigFree() {
        $cfg = new ConfigBase();
        $cfg->init(
            __DIR__ . DIRECTORY_SEPARATOR . 'config_free_mysql.json',
            true
        );

        return $cfg;
    }

    protected static function GetDBObject() {
        $cfg = TestDataBaseMySQL::GetConfigFree();

        $params = array(
            'host' => $cfg->host, 'dbname' => $cfg->dbname,
            'port' => $cfg->port, 'user' => $cfg->dbDefaultUser,
            'password' => $cfg->dbDefaultPass
        );

        if (!isset($GLOBALS['_STORAGE'])) {
            ApplicationInitBase::initStorage();
            global $_STORAGE;
            $_STORAGE['password'] = $params['password'];
        }

        return new DatabaseMysql($params);
    }

    protected static function QueryExec(&$database, $query) {
        $database->db_connect();

        return $database->db_fetch_array(
            $database->db_query($query),
            MYSQLI_ASSOC
        );
    }

    public function test_db_connect_success_connect() {
        ApplicationInitBase::initStorage();

        $db = TestDataBaseMySQL::GetDBObject();

        $this->assertNotNull($db->db_connect(null));
    }

    public function test_db_connect_error_exception() {
        $db = TestDataBaseMySQL::GetDBObject();
        $cfg = TestDataBaseMySQL::GetConfigFree();
        $dbname = 'test_demo';

        $this->expectExceptionMessage("Unknown database '$dbname'");
        $db->db_connect(
            array(
                'host' => $cfg->host, 'dbname' => $dbname,
                'port' => $cfg->port, 'user' => $cfg->dbDefaultUser,
                'password' => $cfg->dbDefaultPass
            )
        );
    }

    public function test_db_get_pid() {
        $db = TestDataBaseMySQL::GetDBObject();
        $this->assertGreaterThan(0, $db->db_get_pid());
    }

    public function test_db_last_error() {
        $db = TestDataBaseMySQL::GetDBObject();
        $query = 'SELECT version(()'; // <- knowingly erroneous query

        $this->expectExceptionMessage('You have an error in your SQL syntax;');
        $this->debug_object($db->db_query($query));
    }

    public function test_db_fetch_array() {
        $db = TestDataBaseMySQL::GetDBObject();
        $result = array('o' => 'one_column', 't' => 'two_column');
        $query = 'SELECT ' . implode(', ',
                array_map(
                    function ($value, $key) {
                        return "'$value' AS $key";
                    },
                    $result,
                    array_keys($result)
                )
            ) . ';';

        $this->assertEquals(
            $result,
            $this->QueryExec($db, $query)
        );
    }

    public function test_db_free_result() {
        $db = TestDataBaseMySQL::GetDBObject();

        $this->assertTrue(
            $db->db_free_result(
                $db->db_query('SELECT version();')
            )
        );
    }

    public function test_db_query() {
        $db = TestDataBaseMySQL::GetDBObject();

        $this->assertNotFalse(
            $db->db_query('SELECT version();')
        );
    }

    public function test_db_close() {
        $db = TestDataBaseMySQL::GetDBObject(null);
        $db->db_connect();

        $this->assertTrue(
            $db->db_close()
        );
    }

    public function test_db_escape_string() {
        $db = TestDataBaseMySQL::GetDBObject(null);
        $input_string = "test 'string";

        $this->assertEquals(
            mysqli_escape_string($db->db_connect(), $input_string),
            $db->db_escape_string($input_string)
        );
    }


    public function test_db_escape_bytea() {
        $db = TestDataBaseMySQL::GetDBObject(null);
        $byte = '\xffd8';

        $this->assertEquals(
            mysqli_escape_string($db->db_connect(), $byte),
            $db->db_escape_bytea($byte)
        );
    }

    public function test_db_type_compare() {
        $db = TestDataBaseMySQL::GetDBObject(null);
        $format = MYSQLI_BOTH;
        $this->expectExceptionMessage("'$format' is unknown format!");
        $db->db_type_compare($format);
    }

    public function test_db_query_user_description() {
        $db = TestDataBaseMySQL::GetDBObject(null);
        $usename = 'mysql';

        $this->assertEquals(
            $usename,
            self::QueryExec(
                $db,
                $db->db_query_user_description($usename)
            )['user']
        );
    }

    public function test_get_format() {
        $db = TestDataBaseMySQL::GetDBObject(null);

        $this->assertEquals(
            MYSQLI_ASSOC,
            $db->get_format('object')
        );
        $this->assertEquals(
            MYSQLI_NUM,
            $db->get_format('')
        );
    }

    public function test_id_quote() {
        $db = TestDataBaseMySQL::GetDBObject(null);

        $this->assertTrue(
            strpos(
                $db->id_quote('`test_indetifier`'),
                '``'
            ) !== false
        );
    }

    public function test_db_type() {
        $db = TestDataBaseMySQL::GetDBObject(null);

        $value = 'test_value';
        $type = 'integer';

        $this->assertEquals(
            "'$value'",
            $db->type($value, false)
        );

        $this->assertEquals(
            "CONVERT('$value', $type)",
            $db->type($value, $type)
        );
    }

    public function test_db_type_field() {
        $db = TestDataBaseMySQL::GetDBObject(null);
        $field = 'field';
        $type = 'integer';

        $this->assertEquals(
            "`$field`",
            $db->type_field($field, false, true)
        );

        $this->assertEquals(
            "CONVERT(`$field`, $type)",
            $db->type_field($field, $type, true)
        );

        $this->assertEquals(
            "`$field`",
            $db->type_field($field, false)
        );

        $this->assertEquals(
            "CONVERT($field, $type)",
            $db->type_field($field, $type)
        );
    }

    public function test_get_explain_query() {
        $db = TestDataBaseMySQL::GetDBObject(null);

        $this->assertEquals(
            'EXPLAIN FORMAT=JSON',
            $db->get_explain_query()
        );
    }

    public function test_numeric_trunc() {
        $db = TestDataBaseMySQL::GetDBObject(null);
        $num = '100.5';
        $count = 0;

        $this->assertEquals(
            "truncate($num, $count)",
            $db->numeric_trunc($num, $count)
        );

        $this->assertEquals(
            "truncate($num, $count) AS truncate",
            $db->numeric_trunc($num, $count, true)
        );
    }

    public function test_format() {
        $db = TestDataBaseMySQL::GetDBObject(null);
        $format = '%s-%s_%s';
        $columns = array('one', 'two', 'three');

        $this->assertEquals(
            "CONCAT('', '', one, '', '-', '', two, '', '_', '', three)",
            $db->format($columns, $format)
        );
    }

    public function test_row_to_json() {
        $db = TestDataBaseMySQL::GetDBObject(null);
        $columns = array('one', 'two', 'three');

        $this->assertEquals(
            $db->row_to_json($columns),
            "(json_object('f1', one, 'f2', two, 'f3', three))"
        );

        $this->assertEquals(
            $db->row_to_json(array(implode('|||', $columns))),
            "(json_object('f1', one, 'f2', two, 'f3', three))"
        );
    }

    public function test_operator_ilike() {
        $db = TestDataBaseMySQL::GetDBObject(null);

        $this->assertEquals(
            'LIKE',
            $db->operator_like()
        );
    }

    public function test_get_collate() {
        $db = TestDataBaseMySQL::GetDBObject(null);

        $this->assertEquals(
            'utf8mb4_bin',
            $db->get_collate()
        );
    }

    /*public function test_db_connect_sql() {
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
    }*/
}























