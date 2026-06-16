<?php
require_once __DIR__ . '/app_config.php';

$dbConfig = app_db_credentials();
$host = $dbConfig['host'];
$log = $dbConfig['user'];
$password_sql = $dbConfig['pass'];
$database = $dbConfig['name'];
