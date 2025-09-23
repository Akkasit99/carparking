<?php
// Include security headers
require_once 'includes/security_headers.php';

session_start();
require_once 'includes/login_handler.php';
handleLogin();
?>