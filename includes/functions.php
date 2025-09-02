<?php
require_once 'config.php';

// Check if user is logged in
function checkLogin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

// Check specific user type
function checkUserType($required_type) {
    checkLogin();
    if ($_SESSION['user_type'] !== $required_type) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

// Logout function
function logout() {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Get current semester info
function getCurrentSemester($db) {
    $query = "SELECT current_semester, current_year, registration_open FROM config ORDER BY id DESC LIMIT 1";
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // Return null if no config found - no dummy data
    return [
        'current_semester' => null,
        'current_year' => null,
        'registration_open' => false,
        'configured' => false
    ];
}

// Format schedule display
function formatSchedule($days, $time) {
    return $days . ' ' . $time;
}

// Check if times conflict
function checkTimeConflict($new_days, $new_time, $existing_schedules) {
    foreach ($existing_schedules as $schedule) {
        // Check if days overlap
        $common_days = array_intersect(str_split($new_days), str_split($schedule['schedule_days']));
        if (!empty($common_days)) {
            // If days overlap, check time conflict
            if (timeRangesOverlap($new_time, $schedule['schedule_time'])) {
                return true;
            }
        }
    }
    return false;
}

// Check if two time ranges overlap
function timeRangesOverlap($time1, $time2) {
    // Parse time ranges (format: "10:00-11:20")
    list($start1, $end1) = explode('-', $time1);
    list($start2, $end2) = explode('-', $time2);
    
    $start1 = strtotime($start1);
    $end1 = strtotime($end1);
    $start2 = strtotime($start2);
    $end2 = strtotime($end2);
    
    return ($start1 < $end2) && ($end1 > $start2);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>
