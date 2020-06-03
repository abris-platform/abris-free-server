<?php

/**
 * Abris - Web Application RAD Framework
 * @version v2.0.1
 * @license (c) TRO MOO AIO, Commercial Product
 * @date Sat Sep 17 2016 09:45:15
 */

$host = "localhost";
$port = "5432";
if (!isset($dbname)) {
	$dbname = "demo";
}
$dbuser = "guest";
$dbpass = "123456";

// Variables for the health of the administration module.
$adminSchema = 'admin';
$adminSessionTable = 'sessions';
$adminLogTable = 'log_query';
$anotherPrefLog = ''; 

$metaSchema = 'meta';

// Variables for the health of the mailing list module.
$dbuserPost = 'rzd@post_email';
$dbuserPostPassword = 'post_email';

// Variables required for registration
$dbRegFunction = 'create_user'; // user create function, must be in public

// Localization settings
$dbDefaultLanguage = 'en';
$dbLanguageList = ['en', 'ru'];

$cryptMethod = 'AES-256-CBC';
