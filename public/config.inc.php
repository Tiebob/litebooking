<?php
// enable output_buffer, session
ob_start();
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'litelibrary');
define('DB_USER', 'DB_username');
define('DB_PASS', 'DB_password');
define('DB_TYPE', 'mysqli');

define('APP_MAIN_TITLE', 'LiteLibrary' );
define('APP_SUB_TITLE', '樹小簡易圖書借閱模組' );


date_default_timezone_set('Asia/Taipei');

session_cache_expire(60);