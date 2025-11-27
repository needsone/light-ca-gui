<?php
session_name(SESSION_NAME);
session_start();

require_once 'config.php';

if (isset($_SESSION['username'])) {
    log_message('INFO', "User logged out", ['username' => $_SESSION['username']]);
}

session_unset();
session_destroy();

header('Location: login.php');
exit;
