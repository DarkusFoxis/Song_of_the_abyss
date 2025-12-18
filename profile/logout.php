<?php
session_start();
unset($_SESSION['user']);
unset($_SESSION['username']);
session_destroy();
header("location:main");
session_write_close();