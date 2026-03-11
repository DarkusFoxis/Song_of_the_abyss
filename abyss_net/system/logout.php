<?php
require_once __DIR__ . '/../../template/auth.php';

auth_logout_user();
header('Location: core');
exit;
