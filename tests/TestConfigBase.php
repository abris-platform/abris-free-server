<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TestConfigBase extends TestCase {
    public function test_getConfigContentFile_exception_without_config() {
        global $_CONFIG;

        $false_path = 'tmp/config.tmp';
        $this->expectExceptionMessage("$false_path not loaded - core config doesn't exist!");
        $_CONFIG->init($false_path);
    }

    public function test__get_unknown_param() {
        global $_CONFIG;

        $property = 'not_exist_param';
        $this->expectExceptionMessage("property $property doesn't exist");
        $a = $_CONFIG->{$property};
    }

    public function test__isset() {
        global $_CONFIG;

        $this->assertTrue(
            isset($_CONFIG->host)
        );
    }
}