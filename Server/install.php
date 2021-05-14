<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'autoload.php';

$dbuser = "guest";
$dbpass = "123456";
$metaSchema = "meta";

function disable_ob() {
    // Turn off output buffering
    ini_set('output_buffering', 'off');

    // Turn off output compression
    ini_set('zlib.output_compression', false);

    // Clear output buffers
    ini_set('implicit_flush', true);
    ob_implicit_flush(true);

    while (ob_get_level() > 0) {
        // Get current output level
        $level = ob_get_level();
        // Finish Buffering
        ob_end_clean();
        // Abort if the current level has not changed (a new line has not appeared)
        if (ob_get_level() == $level) break;
    }

    // Disable buffering and compression for Apache
    if (function_exists('apache_setenv')) {
        apache_setenv('no-gzip', '1');
        apache_setenv('dont-vary', '1');
    }
}

function CreateConfig($host, $port, $dbname) {
    $config = new ConfigBase();
    $config->init();

    $config->host = $host;
    $config->port = $port;
    $config->dbname = $dbname;
}

function InstallFree($host, $port, $dbname, $username, $password) {
    echo "Installation of free abris version";
    $command = "PGPASSWORD=$password psql -h $host -p $port -d $dbname -U $username -f '" . __DIR__ . "/sql_install/pg_abris_free.sql' 2>&1";
    echo "<pre>";
    system($command);
    echo "</pre>";
}

function CreateDemo($host, $port, $dbname, $username, $password) {
    echo "Demo info creation";
    $command = "PGPASSWORD=$password psql -h $host -p $port -d $dbname -U $username -f '" . __DIR__ . "/sql_install/abris-free-demo-recreate.sql' 2>&1";
    echo "<pre>";
    system($command);
    echo "</pre>";
}

function CreateDatabase($host, $port, $dbname, $username, $password) {
    echo "Demo info creation";
    $command = "PGPASSWORD=$password psql -h $host -p $port -d postgres -U $username -c 'CREATE DATABASE $dbname;'";
    echo "<pre>";
    system($command);
    echo "</pre>";
}

function CreateMenu($host, $port, $dbname, $username, $password) {
    echo "Demo info creation";
    $command = "PGPASSWORD=$password psql -h $host -p $port -d $dbname -U $username -c 'select meta.create_menu()'";
    echo "<pre>";
    system($command);
    echo "</pre>";
}

function TestConnection($host, $port, $dbname, $username, $password) {
    $dbconn = pg_connect("host=$host dbname=$dbname port=$port user=$username password=$password") or die('FAIL');
    die("OK");
}

function StartInstall() {
    if (
        isset($_REQUEST["address"]) && isset($_REQUEST["port"]) && isset($_REQUEST["database"])
        && isset($_REQUEST["username"]) && isset($_REQUEST["userpas"])
    ) {
        if (
            $_REQUEST["address"] != '' && $_REQUEST["port"] != '' && $_REQUEST["database"] != ''
            && $_REQUEST["username"] != '' && $_REQUEST["userpas"] != ''
        ) {
            if(isset($_REQUEST["test"])){
                TestConnection($_REQUEST["address"], $_REQUEST["port"], $_REQUEST["database"], $_REQUEST["username"], $_REQUEST["userpas"]);
            }

            // connection of the "terminal"
            disable_ob();

            CreateConfig($_REQUEST["address"], $_REQUEST["port"], $_REQUEST["database"]);

            if (isset($_REQUEST['create'])) {
                CreateDatabase($_REQUEST["address"], $_REQUEST["port"], $_REQUEST["database"], $_REQUEST["username"], $_REQUEST["userpas"]);
            }

            InstallFree($_REQUEST["address"], $_REQUEST["port"], $_REQUEST["database"], $_REQUEST["username"], $_REQUEST["userpas"]);
            if (isset($_REQUEST['demo'])) {
                CreateDemo($_REQUEST["address"], $_REQUEST["port"], $_REQUEST["database"], $_REQUEST["username"], $_REQUEST["userpas"]);
            }
            if (isset($_REQUEST['menu'])) {
                CreateMenu($_REQUEST["address"], $_REQUEST["port"], $_REQUEST["database"], $_REQUEST["username"], $_REQUEST["userpas"]);
            }

            $installFilename = __DIR__ .'/install_abris';
            if (file_exists($installFilename))
                unlink($installFilename);

            echo "Install completed";
        } else {
            echo "<p>Fill in the input fields</p>";
        }
    }
}

StartInstall();