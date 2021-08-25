<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TestWebStorage extends TestCase {
    protected static function getProtectedMethod($name) {
        $object = new ReflectionClass('WebStorage');
        $method = $object->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function test_construct_null_session() {
        $storage = new WebStorage(null);

        $this->assertTrue($storage->IsSession());
        $this->assertEquals($storage['login'], $_SESSION['login']);
        $this->assertEquals($storage['password'], $_SESSION['password']);
    }

    public function test_CheckAndStartSession() {
        $method = self::getProtectedMethod('CheckAndStartSession');
        $object = new WebStorage();

        $object['SESSION_DISABLED'] = true;
        $this->assertFalse(
            $method->invokeArgs($object, array())
        );

        unset($object['SESSION_DISABLED']);
        $object['SESSION_ACTIVE'] = true;
        $this->assertTrue(
            $method->invokeArgs($object, array())
        );

        unset($object['SESSION_ACTIVE']);
        $object['WITHOUT_SESSION'] = true;
        $this->assertFalse(
            $method->invokeArgs($object, array())
        );

        unset($object['WITHOUT_SESSION']);
        $object['SESSION_NONE'] = true;
        session_destroy();
        $this->expectExceptionMessage('Failed start session!');
        $method->invokeArgs($object, array());

    }

    public function test_killStorage() {
        $storage = new WebStorage();
        $this->assertTrue(
            $storage->killStorage()
        );
    }

    public function test_setDefault() {
        $storage = new WebStorage();
        $def = 'test_default';

        $storage->setDefault($def);
        $this->assertEquals($def, $storage->getDefault());
    }

    public function test_offsetGet_default_value() {
        $storage = new WebStorage();
        $def = 'test_default';
        $storage->setDefault($def);

        $this->assertEquals($def, $storage['test']);
    }

    public function test_pauseSession() {
        $storage = new WebStorage();
        $storage->pauseSession();

        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }
}