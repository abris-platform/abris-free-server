<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . "/../Server/db/DatabasePostgresql.php";

class TestSQL extends TestCase
{
    public function test_sql(){
        global $_STORAGE;
        $query = 'select version()';
        $sql  = new SQLBase($query);
        $this->assertNotNull($sql);
        $opt = $sql->GetOptions();
        $this->assertNotNull($opt);
        $sql->BeforeQuery("12345");
        $this->assertNotNull($_STORAGE['pids']);
        $sql->AfterQuery(0);
        $res = $sql->ExistsScheme("meta");
        $this->assertNotNull($res);
        $res = $sql->ProcessResult($_STORAGE['database']->db_query($query), 1);
        $this->assertNotNull($res[0]['version']);

    }
}