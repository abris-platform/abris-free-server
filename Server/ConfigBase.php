<?php

class ConfigBase {
    protected $storageParams;
    protected $configFullPath = '';
    private $configFilename = '';

    public function __construct() {
        $this->storageParams = array();
        $this->configFilename = 'config_free.json';
    }

    public function init($filename = null) {
        if (is_null($filename))
            $filename = $this->configFilename;

        $this->configFullPath = __DIR__ ."/configs/$filename";
        $this->storageParams = $this->getConfigContentFile();
    }

    protected function getConfigContentFile($path = null) {
        if (is_null($path))
            $path = $this->configFullPath;

        if (file_exists($path))
            return json_decode(file_get_contents($path), true);
        else
            throw new Exception("$path not loaded - core config doesn't exist!");
    }

    public function __get($name) {
        if (isset($this->storageParams[$name]))
            return $this->storageParams[$name];
        else
            throw new Exception("property $name doesn't exist");
    }

    public function __set($name, $value = null) {
        $this->storageParams[$name] = $value;

        $this->SaveCurrentConfig();
    }

    protected function SaveCurrentConfig() {
        file_put_contents(
            $this->configFullPath,
            json_encode($this->storageParams, JSON_PRETTY_PRINT)
        );
    }

    public function __isset($name) {
        return isset($this->storageParams[$name]);
    }
}