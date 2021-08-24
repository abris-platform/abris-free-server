<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TestServicesMethods extends TestCase
{
    public function test_EncryptData() {
        $data = 'test_string';
        $secret_key = '45adedd7-0222-4826-aa1b-a6f113c5421a';

        $this->assertTrue(
            strpos(
                EncryptStr($data, $secret_key),
                '==') !== false
        );
    }

    public function test_DecryptData() {
        $data = 'test_string';
        $secret_key = '45adedd7-0222-4826-aa1b-a6f113c5421a';
        $secret_data = EncryptStr($data, $secret_key);

        $this->assertEquals(
            $data,
            DecryptStr($secret_data, $secret_key)
        );
    }

    public function test_GetClientIP() {
        $_SERVER = array();

        $this->assertEquals('UNKNOWN', GetClientIP());

        $_SERVER['REMOTE_ADDR'] = '0.0.0.0';
        $this->assertEquals(
            $_SERVER['REMOTE_ADDR'], GetClientIP()
        );

        $_SERVER['HTTP_FORWARDED'] = '1.1.1.1';
        $this->assertEquals(
            $_SERVER['HTTP_FORWARDED'], GetClientIP()
        );

        $_SERVER['HTTP_FORWARDED_FOR'] = '2.2.2.2';
        $this->assertEquals(
            $_SERVER['HTTP_FORWARDED_FOR'], GetClientIP()
        );

        $_SERVER['HTTP_X_FORWARDED'] = '3.3.3.3';
        $this->assertEquals(
            $_SERVER['HTTP_X_FORWARDED'], GetClientIP()
        );

        $_SERVER['HTTP_X_FORWARDED_FOR'] = '4.4.4.4';
        $this->assertEquals(
            $_SERVER['HTTP_X_FORWARDED_FOR'], GetClientIP()
        );

        $_SERVER['HTTP_CLIENT_IP'] = '5.5.5.5';
        $this->assertEquals(
            $_SERVER['HTTP_CLIENT_IP'], GetClientIP()
        );
    }
}