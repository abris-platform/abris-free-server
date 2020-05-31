<?php

/**
 * Abris - Web Application RAD Framework
 * @version v2.0.1
 * @license (c) TRO MOO AIO, Commercial Product
 * @date Sat Sep 17 2016 09:45:15
 */
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
?>
<?php
require "db.php";
$dbconn = pg_connect("host=$host dbname=$dbname port=$port user=$dbuser password=$dbpass") or die('Could not connect: ' . pg_last_error());
if ($dbconn) echo "Database Ok";

?>