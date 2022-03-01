<?php

function GenerateRandomString($length = 32) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=~№;%:?';
    $randomStr = '';

    for ($counter = 0; $counter < $length; $counter++) {
        $randomStr .= $chars[rand(0, iconv_strlen($chars) - 1)];
    }

    return $randomStr;
}

function EncryptStr($data, $key) {
    global $_CONFIG;

    $ivlen = openssl_cipher_iv_length($_CONFIG->cryptMethod);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertextRaw = openssl_encrypt($data, $_CONFIG->cryptMethod, $key, $options = OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertextRaw, $key, $as_binary = true);
    return base64_encode($iv . $hmac . $ciphertextRaw);
}

function DecryptStr($data, $key) {
    global $_CONFIG;

    $c = base64_decode($data);
    $ivlen = openssl_cipher_iv_length($_CONFIG->cryptMethod);
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, $sha2len = 32);
    $ciphertextRaw = substr($c, $ivlen + $sha2len);
    $plainText = openssl_decrypt($ciphertextRaw, $_CONFIG->cryptMethod, $key, $options = OPENSSL_RAW_DATA, $iv);
    $calcmac = hash_hmac('sha256', $ciphertextRaw, $key, $as_binary = true);

    return hash_equals($hmac, $calcmac) ? $plainText : false;
}

function GetClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if (isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function preprocess_data($data) {
    $data = trim($data);

    if ($data == 'true')
        return 't';
    if ($data == 'false')
        return 'f';

    return $data;
}

function unset_auth_session() {
    global $_STORAGE;

    if ((!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__')))
        $_STORAGE->killStorage();
    unset($_STORAGE);

    if (isset($_COOKIE['private_key']))
        setcookie('private_key', null, -1);
}

function dir_separator(array $paths): string {
    return implode(DIRECTORY_SEPARATOR, $paths);
}