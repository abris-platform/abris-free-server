<?php

class ConfigBase {
    protected $storageParams;

    public function __construct__() {
        $this->storageParams = array();
    }

    public function init() {
        $configPath = __DIR__ .'/configs/config_free.json';
        $this->storageParams = $this->getConfigContentFile($configPath);
    }

    protected function getConfigContentFile($path) {
        if (file_exists($path))
            return json_decode(file_get_contents($path), true);
        else
            throw new Exception("$path not loaded - file doesn't exist!");
    }

    public function __get($name) {
        if (isset($this->storageParams[$name]))
            return $this->storageParams[$name];
        else
            throw new Exception("property $name doesn't exist");
    }

    public function __set($name, $value = null) {
        $this->storageParams[$name] = $value;
    }

    public function __isset($name) {
        return isset($this->storageParams[$name]);
    }
}