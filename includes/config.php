<?php
// Global configuration file
define('BASE_URL', 'http://localhost/crfms/');
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ewu_registration');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
session_start();

// Timezone
date_default_timezone_set('Asia/Dhaka');
?>
