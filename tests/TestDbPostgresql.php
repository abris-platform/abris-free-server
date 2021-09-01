<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TestDatabasePostgresql extends TestCase
{
    protected static function GetConfigFree() {
        if (!isset($GLOBALS['_CONFIG']))
            ApplicationInitBase::initConfigFree();

        return $GLOBALS['_CONFIG'];
    }

    protected static function GetDBObject($params = null) {
        if (is_null($params)) {
            $cfg = TestDatabasePostgresql::GetConfigFree();

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
        }

        return new DatabasePostgresql($params);
    }

    protected function QueryExec(&$database, $query) {
        $database->db_connect();

        return $database->db_fetch_array(
            $database->db_query($query),
            PGSQL_ASSOC
        );
    }

    protected function ExceptionDebug($object) {
        throw new Exception(print_r($object, true));
    }

    public function test_db_connect_without_existing_connect() {
        ApplicationInitBase::initStorage();
        global $_STORAGE;

        $_STORAGE['private_key'] = GenerateRandomString();
        $_STORAGE['password'] = EncryptStr($_STORAGE['password'], $_STORAGE['private_key']);
        $db = TestDatabasePostgresql::GetDBObject(null);

        $this->assertNotNull($db->db_connect(null));
    }

    public function test_db_connect_with_existing_connect() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $exist_connect = $db->db_connect();
        $this->assertEquals($exist_connect, $db->db_connect());
    }

    public function test_db_get_pid() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $this->assertEquals(
            pg_get_pid($db->db_connect()),
            $db->db_get_pid()
        );
    }

    public function test_db_last_error() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $text_exception = 'Test Exception';
        @$db->db_query("     
                    DO
                    $$
                        BEGIN
                            RAISE EXCEPTION '$text_exception';
                        END;
                    $$
            ");

        $this->assertTrue(
            strpos($db->db_last_error(), $text_exception) !== false
        );
    }

    public function test_db_fetch_array() {
        $db = TestDatabasePostgresql::GetDBObject(null);
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
        $db = TestDatabasePostgresql::GetDBObject(null);

        $this->assertTrue(
            $db->db_free_result(
                $db->db_query('SELECT now();')
            )
        );
    }

    public function test_db_query() {
        $db = TestDatabasePostgresql::GetDBObject(null);

        $this->assertNotFalse(
            $db->db_query('SELECT now();')
        );
    }

    public function test_db_close() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $db->db_connect();

        $this->assertTrue(
            $db->db_close()
        );
    }

    public function test_db_escape_string() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $input_string = "test 'string";

        $this->assertEquals(
            pg_escape_string($input_string),
            $db->db_escape_string($input_string)
        );
    }

    public function test_db_escape_bytea() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $byte = '\xffd8';

        $this->assertEquals(
            pg_escape_bytea($byte),
            $db->db_escape_bytea($byte)
        );
    }

    public function test_db_type_compare_call_exception() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $format = PGSQL_BOTH;
        $this->expectExceptionMessage("'$format' is unknown format!");
        $db->db_type_compare($format);
    }

    public function test_get_format() {
        $db = TestDatabasePostgresql::GetDBObject(null);

        $this->assertEquals(
            PGSQL_ASSOC,
            $db->get_format('object')
        );
        $this->assertEquals(
            PGSQL_NUM,
            $db->get_format('')
        );
    }

    public function test_set_bytea_output() {
        $db = TestDatabasePostgresql::GetDBObject(null);

        $db->db_connect();
        $db->set_bytea_output();
        $query = "SELECT '\\134'::bytea";

        $this->assertEquals(
            '\\\\',
            $this->QueryExec($db, $query)['bytea']
        );
    }

    public function test_set_interval_style() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $db->db_connect();

        $source_interval = '2 month 20 days';
        $query = "SELECT '$source_interval'::interval AS i";

        $db->set_interval_style('sql_standard');
        $this->assertEquals(
            '+0-2 +20 +0:00:00',
            $this->QueryExec($db, $query)['i']
        );

        $db->set_interval_style('iso_8601');
        $this->assertEquals(
            'P2M20D',
            $this->QueryExec($db, $query)['i']
        );

        $db->set_interval_style('postgres');
        $this->assertEquals(
            '2 mons 20 days',
            $this->QueryExec($db, $query)['i']
        );
    }

    public function test_id_qoute() {
        $db = TestDatabasePostgresql::GetDBObject(null);

        $this->assertTrue(
            strpos(
                $db->id_quote('"test_indetifier"'),
                '"""'
            ) !== false
        );
    }

    public function test_type() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $value = 'test_value';
        $type = 'text';

        $this->assertEquals(
            "'$value'",
            $db->type($value, false)
        );

        $this->assertEquals(
            "'$value'::$type",
            $db->type($value, $type)
        );
    }

    public function test_type_field() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $field = 'field';
        $type = 'text';

        $this->assertEquals(
            '"' . $field . '"',
            $db->type_field($field, false, true)
        );

        $this->assertEquals(
            "\"$field\"::$type",
            $db->type_field($field, $type, true)
        );

        $this->assertEquals(
            $field,
            $db->type_field($field, false)
        );

        $this->assertEquals(
            "$field::$type",
            $db->type_field($field, $type)
        );
    }

    public function test_get_explain_query() {
        $db = TestDatabasePostgresql::GetDBObject(null);

        $this->assertEquals(
            'EXPLAIN (format json)',
            $db->get_explain_query()
        );
    }

    public function test_get_total_row_cost_explain() {
        $db = TestDatabasePostgresql::GetDBObject(null);

        $cost = 2000;
        $rows = 100;
        $input = array(
            'QUERY PLAN' => "[{ \"Plan\": { \"Total Cost\": $cost, \"Plan Rows\": $rows }}]"
        );

        $this->assertEquals(
            $rows,
            $db->get_plan_row_explain($input)
        );

        $this->assertEquals(
            $cost,
            $db->get_total_cost_explain($input)
        );
    }

    public function test_numeric_trunc() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $num = '100.5';
        $count = 0;

        $this->assertEquals(
            "trunc($num, $count)",
            $db->numeric_trunc($num, $count)
        );

        $this->assertEquals(
            "trunc($num, $count) AS trunc",
            $db->numeric_trunc($num, $count, true)
        );
    }

    public function test_return_pkey_value() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $pkey = 'column_pkey';

        $this->assertEquals(
            "RETURNING $pkey",
            $db->return_pkey_value($pkey)
        );
    }

    public function test_wrap_insert_values() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $column = 'one_column';

        $this->assertEquals(
            "SELECT $column",
            $db->wrap_insert_values($column)
        );
    }

    public function test_operator_ilike() {
        $db = TestDatabasePostgresql::GetDBObject(null);

        $this->assertEquals(
            'ILIKE',
            $db->operator_like()
        );
    }

    public function test_format() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $format = '%s-%s_%s';
        $columns = array('one', 'two', 'three');

        $this->assertEquals(
            "format('$format', " .implode(', ', $columns) .")",
            $db->format($columns, $format)
        );
    }

    public function test_row_to_json() {
        $db = TestDatabasePostgresql::GetDBObject(null);
        $columns = array('one', 'two', 'three');

        $this->assertEquals(
            '(row_to_json(row(' .implode(', ', $columns) .'::text))::text)',
            $db->row_to_json($columns)
        );
    }

    public function test_get_collate() {
        $db = TestDatabasePostgresql::GetDBObject(null);

        $this->assertEquals(
            'C',
            $db->get_collate()
        );
    }
}