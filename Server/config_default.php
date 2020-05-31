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

// Переменные для работоспособности модуля администрирования.
$adminSchema = 'admin';
$adminSessionTable = 'sessions';
$adminLogTable = 'log_query';
$anotherPrefLog = ''; // niisu в рамках АСУДТ

$metaSchema = 'meta';

// Переменные для работоспособности модуля почтовой рассылки.
$dbuserPost = 'rzd@post_email';
$dbuserPostPassword = 'post_email';

// Переменные, необходимые для регистрации
$dbRegFunction = 'create_user'; // функция создания пользователя, должна лежать в public

// Настройки локализации
$dbDefaultLanguage = 'ru';
$dbLanguageList = ['en', 'ru'];
	//$dbUnrollViews = array('"public"."test_v"');
	/*
	CREATE FUNCTION create_user(p_usename text, p_password text)
    RETURNS text
    LANGUAGE 'plpgsql'
    VOLATILE NOT LEAKPROOF SECURITY DEFINER 
	AS 
	$BODY$
		BEGIN
			INSERT INTO admin.v_users(usename, password) VALUES (p_usename, p_password);

			INSERT INTO admin.user2group(usesysid, grosysid)
				VALUES (
						(SELECT usesysid FROM admin.v_users WHERE usename = p_usename),
						(SELECT grosysid FROM admin.groups WHERE groname = 'fire@user')
					); 
		RETURN 'detail/my_account';
	END
	$BODY$;
	*/