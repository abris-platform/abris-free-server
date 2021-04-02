<?php

/**
 * Abris - Web Application RAD Framework
 * @version v2.0.1
 * @license (c) TRO MOO AIO, Commercial Product
 * @date Sat Sep 17 2016 09:45:15
 */

$host = 'localhost';
$port = '5432';
if (!isset($dbname)) {
	$dbname = 'demo';
}
$dbuser = 'guest';
$dbpass = '123456';

$metaSchema = 'meta';

// Variables required for registration
$dbRegFunction = 'create_user'; // user create function, must be in public

// Localization settings
$dbDefaultLanguage = 'en';
$dbLanguageList = ['en', 'ru'];

$cryptMethod = 'AES-256-CBC';