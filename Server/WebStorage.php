<?php

class WebStorage implements ArrayAccess
{
    private $_isSession = false;
    private $_data = array();
    protected $_defaultValue = null;

    public function __construct($isSession = true) {
        if (is_null($isSession))
            $isSession = true;

        if ($isSession) {
            $this->_isSession = $this->CheckAndStartSession();
            $this->startSession();
        }
    }

    protected function session_status() {
        if (!defined('PHPUNIT_COMPOSER_INSTALL') &&
                !defined('__PHPUNIT_PHAR__'))
            return session_status();

        if (isset($this['SESSION_DISABLED']))
            return PHP_SESSION_DISABLED;
        if (isset($this['SESSION_NONE']))
            return PHP_SESSION_NONE;
        if (isset($this['WITHOUT_SESSION']))
            return false;

        return PHP_SESSION_ACTIVE;
    }

    protected function CheckAndStartSession($failed = false) {
        switch ($this->session_status()) {
            case PHP_SESSION_ACTIVE:
                return true;
            case PHP_SESSION_NONE:
                if ($failed)
                    throw new Exception('Failed start session!');

                session_start();
                return $this->CheckAndStartSession(true);
            default:
                return false;
        }
    }

    public function startSession() {
        if ($this->IsSession())
            if ($this->CheckAndStartSession())
                $this->_data = $_SESSION;
    }

    public function pauseSession() {
        if ($this->IsSession())
            session_commit();
    }

    public function killStorage() {
        if ($this->IsSession()) {
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

        $this->_data = null;
        return true;
    }

    public function setDefault($default) {
        $this->_defaultValue = $default;
    }

    public function &getDefault() {
        return $this->_defaultValue;
    }

    public function IsSession() {
        return $this->_isSession;
    }

    public function offsetExists($offset) {
        return isset($this->_data[$offset]);
    }

    public function &offsetGet($offset) {
        if (!isset($this->_data[$offset]))
            return $this->getDefault();

        return $this->_data[$offset];
    }

    public function offsetSet($offset, $value) {
        if ($this->IsSession())
            if ($this->CheckAndStartSession())
                $_SESSION[$offset] = $value;

        $this->_data[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
    }
}