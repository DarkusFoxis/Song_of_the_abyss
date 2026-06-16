<?php
require_once __DIR__ . '/app_config.php';

$dbConfig = app_db_credentials();
$hosts = $dbConfig['host'];
$logn = $dbConfig['user'];
$passwords_sql = $dbConfig['pass'];
$dbase = $dbConfig['name'];
