<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TestApplicationInitBase extends TestCase
{
    public function test_initStorage() {
        ApplicationInitBase::initStorage();
        $this->assertEquals($GLOBALS['_STORAGE'], new WebStorage());
        unset($GLOBALS['_STORAGE']);

        $private_key = 'test_private_key_123321';
        $_COOKIE['private_key'] = $private_key;
        ApplicationInitBase::initStorage();
        global $_STORAGE;
        $this->assertEquals($_STORAGE['private_key'], $private_key);
    }

    public function test_initDatabase() {
        unset($GLOBALS['_STORAGE']);
        unset($GLOBALS['_CONFIG']);
        ApplicationInitBase::initDatabase();
        $this->assertArrayNotHasKey('_STORAGE', $GLOBALS);

        ApplicationInitBase::initStorage();
        ApplicationInitBase::initConfigFree();
        global $_CONFIG, $_STORAGE;
        unset($_STORAGE['login']);
        unset($_STORAGE['password']);

        ApplicationInitBase::initDatabase();
        $this->assertEquals($_STORAGE['login'], $_CONFIG->dbDefaultUser);
        $this->assertEquals($_STORAGE['password'], $_CONFIG->dbDefaultPass);

        $this->testTypeDatabase('', DatabasePostgresql::class);
        $this->testTypeDatabase('pgsql', DatabasePostgresql::class);
        $this->testTypeDatabase('mysql', DatabaseMysql::class);
        $this->testTypeDatabase('sqlite', DatabaseAbstract::class);
    }

    public function test_checkNeedInstallFree_Exception() {
        $testFile = __DIR__ . '/../Server/install_abris';
        file_put_contents($testFile, 'test_abris_free');

        $this->expectExceptionMessage('Need install Abris-Core');
        ApplicationInitBase::checkNeedInstallFree();
    }

    public function test_checkNeedInstallFree_WithoutException() {
        $testFile = __DIR__ . '/../Server/install_abris';
        if (file_exists($testFile)) {
            unlink($testFile);
            clearstatcache();
        }

        $this->assertFalse(ApplicationInitBase::checkNeedInstallFree());
    }

    public function test_getNameClassMethods() {
        $mtd = self::getStaticProtectedMethod('getNameClassMethods');
        $object = new ApplicationInitBase();
        $this->assertEquals('methodsBase', $mtd->invokeArgs($object, array()));
    }

    /*
     * @runInSeparateProcess
     */
    public function test_cors() {
        $_SERVER['HTTP_ORIGIN'] = 'origin';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'HTTP_ACCESS_CONTROL_REQUEST_METHOD';
        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] = 'HTTP_ACCESS_CONTROL_REQUEST_HEADERS';

        $this->assertNull(ApplicationInitBase::cors());

        $_SERVER['REQUEST_METHOD'] = '';
        ApplicationInitBase::cors();
    }

    public function test_request() {
        $_SERVER['REQUEST_METHOD'] = '';
        $storage = new WebStorage(false);
        $GLOBALS['_STORAGE'] = $storage;

        $this->assertEquals(
            '{"jsonrpc":"2.0","result":null,"error":"method","usename":"","pids":0}',
            ApplicationInitBase::request()
        );

        ApplicationInitBase::initStorage();
        $_POST['method'] = '';
        $this->assertEquals(
            '{"jsonrpc":"2.0","result":null,"error":"params","usename":"postgres","pids":0}',
            ApplicationInitBase::request()
        );

        global $_STORAGE;
        $_POST['params'] = '{ "test_param" : "test_value" }';
        $_POST['method'] = 'getAnotherUsername';
        $_STORAGE['login'] = 'test_postgres';
        $this->assertEquals(
            '{"jsonrpc":"2.0","result":"' . $_STORAGE['login'] . '","error":null,"usename":"' . $_STORAGE['login'] . '","pids":0}',
            ApplicationInitBase::request()
        );
    }

    protected static function getStaticProtectedMethod($name) {
        $object = new ReflectionClass('ApplicationInitBase');
        $method = $object->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    private function testTypeDatabase($type, $equal_class) {
        global $_CONFIG;
        $_CONFIG->databaseType = $type;
        ApplicationInitBase::initDatabase();

        global $_STORAGE;
        if ($type == 'sqlite')
            $this->assertArrayNotHasKey('database', $_STORAGE);
        else
            $this->assertInstanceOf($equal_class, $_STORAGE['database']);
    }
}