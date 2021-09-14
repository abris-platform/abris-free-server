<?php


class SQLParamBase
{
    protected $preprocessData;
    protected $format;
    protected $encryptPassword;
    protected $defaultConnection;
    protected $isLogFile;
    protected $queryDescription;

    public function __construct() {
        $this->SetPreprocessData();
        $this->SetFormat();
        $this->SetEncryptPassword();
        $this->SetDefaultConnection();
        $this->SetFileLog();
    }

    ////////////////////////////////////////////////////////////////////
    public function SetPreprocessData($value = false) {
        $this->preprocessData = $value;
    }

    public function SetFormat($value = 'object') {
        $this->format = $value;
    }

    public function SetEncryptPassword($value = true) {
        $this->encryptPassword = $value;
    }

    public function SetDefaultConnection($value = false) {
        $this->defaultConnection = $value;
    }

    private function SetFileLog() {
        global $_CONFIG;
        $this->isLogFile = isset($_CONFIG->logFile);
    }

    public function SetQueryDescription($value = '') {
        $this->queryDescription = $value;
    }

    ////////////////////////////////////////////////////////////////////
    public function GetPreprocessData() {
        return $this->preprocessData;
    }

    public function GetFormat() {
        return $this->format;
    }

    public function GetEncryptPassword() {
        return $this->encryptPassword;
    }

    public function GetDefaultConnection() {
        return $this->defaultConnection;
    }

    public function IsLogFile() {
        return $this->isLogFile;
    }

    public function GetQueryDescription() {
        return $this->queryDescription;
    }
}