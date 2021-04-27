<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . "/../Server/db/DatabasePostgresql.php";

class TestWebStorage extends TestCase
{
    public function test(){
        global $GLOBALS;
        ApplicationInitBase::initStorage();
        $res = $GLOBALS['_STORAGE']->startSession();
        $res = $GLOBALS['_STORAGE']->getDefault();
        $res = $GLOBALS['_STORAGE']->setDefault($res);
        $res = $GLOBALS['_STORAGE']->pauseSession();
        $this->assertNull($res);
    }

}