<?php

class WebStorage extends ArrayObject
{
    private $_isSession = true;
    protected $_defaultValue = null;

    public function __construct($isSession = true) {
        if (is_null($isSession))
            $isSession = true;

        $this->_isSession = $isSession;

        $storage = array();
        if ($isSession)
            $storage = $this->startSession(true);

        parent::__construct($storage, self::STD_PROP_LIST);
    }

    public function startSession($fromConstruct = false) {
        $sStatus = session_status();

        if ($sStatus == PHP_SESSION_NONE) {
            session_start();

            if (!$fromConstruct)
                parent::exchangeArray($_SESSION);

            return $_SESSION;
        } elseif ($sStatus == PHP_SESSION_ACTIVE) {
            return $_SESSION;
        }

        return array();
    }

    public function offsetGet($key) {
        // Check isset
        if (parent::offsetExists($key))
            return parent::offsetGet($key);
        return $this->getDefault();
    }

    public function offsetSet($key, $value) {
        if ($this->_isSession) {
            $this->startSession();
            $_SESSION[$key] = $value;
        }

        parent::offsetSet($key, $value);
    }

    public function setDefault($default) {
        $this->_defaultValue = $default;
    }

    public function getDefault() {
        return $this->_defaultValue;
    }

    public function pauseSession() {
        if ($this->_isSession)
            session_commit();
    }

    public function killStorage() {
        if ($this->_isSession) {
            $_SESSION = array();

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }

            session_destroy();
        }

        parent::exchangeArray(array());
    }
}


