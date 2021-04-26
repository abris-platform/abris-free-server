<?php

class ConfigBase {
    protected $storageParams;
    private $configFilename = '';

    public function __construct() {
        $this->storageParams = array();
        $this->configFilename = 'config_free.json';
    }

    public function init($filename = null) {
        if (is_null($filename))
            $filename = $this->configFilename;

        $configPath = __DIR__ ."/configs/$filename";
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