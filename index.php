<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_type']) {
        case 'student':
            header('Location: ' . BASE_URL . 'student/dashboard.php');
            break;
        case 'faculty':
            header('Location: ' . BASE_URL . 'faculty/dashboard.php');
            break;
        case 'admin':
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
            break;
        default:
            session_destroy();
            header('Location: ' . BASE_URL . 'login.php');
    }
    exit();
}

// Redirect to login page
header('Location: ' . BASE_URL . 'login.php');
exit();
?>
