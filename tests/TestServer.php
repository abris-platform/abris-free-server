<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . "/../Server/db/DatabasePostgresql.php";

class TestServer extends TestCase
{
    /*public function test_ApplicationInitBase() {
        global $GLOBALS;
        ApplicationInitBase::initStorage();
        ApplicationInitBase::initDatabase();
        $res = ApplicationInitBase::initConfigFree();
        $this->assertNull($res);
    }*/

    public function test_webstoragr(){
        global $GLOBALS;
        $res = $GLOBALS['_STORAGE']->startSession();
        $res = $GLOBALS['_STORAGE']->getDefault();
        $res = $GLOBALS['_STORAGE']->setDefault($res);
        $res = $GLOBALS['_STORAGE']->pauseSession();
        $this->assertNull($res);
    }

}