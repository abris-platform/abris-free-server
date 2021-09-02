<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TestDbSqlController extends TestCase
{
    protected function SetParamsConnect($password = null) {
        global $_STORAGE, $_CONFIG;

        $private_key = '12345678';
        $encrypt_password = EncryptStr($password, $private_key);

        $_STORAGE['private_key'] = $private_key;
        $_STORAGE['password'] = $encrypt_password;

    }

    public function test_GetObjectDatabase_exception() {
        global $_STORAGE;

        unset($_STORAGE['database']);
        $this->expectExceptionMessage('Object database not found.');
        $esc = DbSqlController::IdQuote('test');
    }

    public function test_Connect_with_private_key_success_connect() {
        global $_STORAGE, $_CONFIG;
        $this->SetParamsConnect($_CONFIG->dbDefaultPass);

        $connect = DbSqlController::Connect($_STORAGE['password'], false);

        $this->assertNotFalse($connect);
    }

    public function test_Connect_with_private_key_without_password() {
        global $_STORAGE;
        $this->SetParamsConnect();

        $this->expectExceptionMessage('Invalid password detected! Password can be changed!');
        DbSqlController::Connect($_STORAGE['password'], false);
    }

    public function test_Connect_with_private_key_unseccess_connect() {
        global $_STORAGE;
        $this->SetParamsConnect('bad_password');

        $this->expectExceptionMessage('Unable to connect by user');
        DbSqlController::Connect($_STORAGE['password'], false);
    }

    public function test_Connect_default_connection() {
        $this->assertNotFalse(
            DbSqlController::Connect('', true)
        );
    }
}
