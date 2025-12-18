<?php
require_once './conn.php';

$conn = mysqli_connect($host, $log, $password_sql, $database);
if (!$conn) {
    echo 'Database connection failed';
    exit;
}

function set_token($login, $user_id) {
    
}