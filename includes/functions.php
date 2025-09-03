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

// Check if times conflict with enhanced overlap detection
function checkTimeConflict($new_days, $new_time, $existing_schedules) {
    foreach ($existing_schedules as $schedule) {
        // Check if days overlap using enhanced day parsing
        if (daysOverlap($new_days, $schedule['schedule_days'])) {
            // If days overlap, check time conflict with buffer
            if (timeRangesOverlapWithBuffer($new_time, $schedule['schedule_time'])) {
                return true;
            }
        }
    }
    return false;
}

// Enhanced day overlap detection
function daysOverlap($days1, $days2) {
    $dayMap = [
        'M' => 'Monday', 'T' => 'Tuesday', 'W' => 'Wednesday', 
        'TH' => 'Thursday', 'F' => 'Friday', 'S' => 'Saturday', 'SU' => 'Sunday'
    ];
    
    // Handle special cases like 'TH' and 'TTH'
    $parsedDays1 = parseDayString($days1);
    $parsedDays2 = parseDayString($days2);
    
    // Check for any common days
    return !empty(array_intersect($parsedDays1, $parsedDays2));
}

// Parse day string to handle multi-character days like 'TH'
function parseDayString($dayStr) {
    $days = [];
    $i = 0;
    $len = strlen($dayStr);
    
    while ($i < $len) {
        // Check for 'TH' first (two characters)
        if ($i < $len - 1 && substr($dayStr, $i, 2) === 'TH') {
            $days[] = 'TH';
            $i += 2;
        }
        // Check for 'SU' (Sunday)
        elseif ($i < $len - 1 && substr($dayStr, $i, 2) === 'SU') {
            $days[] = 'SU';
            $i += 2;
        }
        // Single character days
        else {
            $days[] = $dayStr[$i];
            $i++;
        }
    }
    
    return $days;
}

// Enhanced time overlap detection with buffer time
function timeRangesOverlapWithBuffer($time1, $time2, $bufferMinutes = 10) {
    // Parse time ranges (format: "10:00-11:20")
    $range1 = parseTimeRange($time1);
    $range2 = parseTimeRange($time2);
    
    if (!$range1 || !$range2) {
        return false; // Invalid time format
    }
    
    // Add buffer time to prevent back-to-back classes without break
    $start1 = $range1['start'] - ($bufferMinutes * 60);
    $end1 = $range1['end'] + ($bufferMinutes * 60);
    $start2 = $range2['start'];
    $end2 = $range2['end'];
    
    return ($start1 < $end2) && ($end1 > $start2);
}

// Parse time range with better error handling
function parseTimeRange($timeStr) {
    if (!preg_match('/^(\d{1,2}):(\d{2})-(\d{1,2}):(\d{2})$/', $timeStr, $matches)) {
        return false;
    }
    
    $startHour = (int)$matches[1];
    $startMin = (int)$matches[2];
    $endHour = (int)$matches[3];
    $endMin = (int)$matches[4];
    
    // Convert to seconds since midnight for easier comparison
    $start = ($startHour * 3600) + ($startMin * 60);
    $end = ($endHour * 3600) + ($endMin * 60);
    
    // Handle overnight classes (rare but possible)
    if ($end <= $start) {
        $end += 24 * 3600; // Add 24 hours
    }
    
    return [
        'start' => $start,
        'end' => $end,
        'duration' => $end - $start
    ];
}

// Check if two time ranges overlap (original function kept for backward compatibility)
function timeRangesOverlap($time1, $time2) {
    return timeRangesOverlapWithBuffer($time1, $time2, 0);
}

// Generate next available section number
function generateSectionNumber($db, $course_id, $section_type, $semester, $year) {
    // Get existing section numbers for this course in current semester
    $query = "SELECT section_number FROM sections 
              WHERE course_id = ? AND semester = ? AND year = ? AND section_type = ?
              ORDER BY section_number";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ssis', $course_id, $semester, $year, $section_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $existing_numbers = [];
    while ($row = $result->fetch_assoc()) {
        $existing_numbers[] = $row['section_number'];
    }
    
    // Generate section number based on type
    $prefix = ($section_type === 'lab') ? 'L' : '';
    $counter = 1;
    
    do {
        $section_number = $prefix . $counter;
        $counter++;
    } while (in_array($section_number, $existing_numbers));
    
    return $section_number;
}

// Format schedule display with better formatting
function formatScheduleDetailed($days, $time, $room_number = '', $building = '') {
    $formatted_days = formatDaysDisplay($days);
    $formatted_time = formatTimeDisplay($time);
    $location = '';
    
    if ($room_number) {
        $location = " in {$room_number}";
        if ($building) {
            $location .= " ({$building})";
        }
    }
    
    return "{$formatted_days} {$formatted_time}{$location}";
}

// Format days for better display
function formatDaysDisplay($days) {
    $dayMap = [
        'M' => 'Mon', 'T' => 'Tue', 'W' => 'Wed', 
        'TH' => 'Thu', 'F' => 'Fri', 'S' => 'Sat', 'SU' => 'Sun'
    ];
    
    $parsed = parseDayString($days);
    $formatted = [];
    
    foreach ($parsed as $day) {
        $formatted[] = $dayMap[$day] ?? $day;
    }
    
    return implode(', ', $formatted);
}

// Format time for better display
function formatTimeDisplay($time) {
    $range = parseTimeRange($time);
    if (!$range) {
        return $time; // Return original if can't parse
    }
    
    $start_hour = floor($range['start'] / 3600);
    $start_min = floor(($range['start'] % 3600) / 60);
    $end_hour = floor($range['end'] / 3600);
    $end_min = floor(($range['end'] % 3600) / 60);
    
    // Format with AM/PM
    $start_ampm = $start_hour >= 12 ? 'PM' : 'AM';
    $end_ampm = $end_hour >= 12 ? 'PM' : 'AM';
    
    $start_12h = $start_hour > 12 ? $start_hour - 12 : ($start_hour == 0 ? 12 : $start_hour);
    $end_12h = $end_hour > 12 ? $end_hour - 12 : ($end_hour == 0 ? 12 : $end_hour);
    
    return sprintf('%d:%02d %s - %d:%02d %s', 
                   $start_12h, $start_min, $start_ampm,
                   $end_12h, $end_min, $end_ampm);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Calculate actual credits for a student's registration considering theory-lab pairing
 * Returns the correct credit count (combined for theory+lab, individual for standalone)
 */
function calculateStudentCredits($db, $student_id, $semester, $year) {
    $query = "SELECT s.section_id, s.section_type, s.parent_section_id, 
              c.course_id, c.theory_credits, c.lab_credits, c.total_credits,
              -- Check if this theory section has a paired lab that student is also registered for
              (CASE 
                WHEN s.section_type = 'theory' 
                THEN (SELECT COUNT(*) FROM registration r2 
                      JOIN sections s2 ON r2.section_id = s2.section_id 
                      WHERE r2.student_id = ? AND r2.status = 'registered' 
                      AND s2.parent_section_id = s.section_id AND s2.section_type = 'lab')
                ELSE 0
              END) as has_paired_lab_registered
              FROM registration r
              JOIN sections s ON r.section_id = s.section_id
              JOIN courses c ON s.course_id = c.course_id
              WHERE r.student_id = ? AND r.status = 'registered' 
              AND s.semester = ? AND s.year = ?
              ORDER BY c.course_id, s.section_type";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param('iisi', $student_id, $student_id, $semester, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_credits = 0;
    $processed_courses = [];
    
    while ($row = $result->fetch_assoc()) {
        $course_id = $row['course_id'];
        $section_type = $row['section_type'];
        
        if ($section_type === 'theory') {
            if ($row['has_paired_lab_registered'] > 0) {
                // Student has both theory and lab - count combined credits only once
                if (!in_array($course_id, $processed_courses)) {
                    $total_credits += ($row['theory_credits'] + $row['lab_credits']);
                    $processed_courses[] = $course_id;
                }
            } else {
                // Theory only - count theory credits
                $total_credits += $row['theory_credits'];
            }
        } elseif ($section_type === 'lab') {
            // Check if we already counted this as part of theory+lab combo
            if (!in_array($course_id, $processed_courses)) {
                // Standalone lab registration (shouldn't happen in normal flow, but handle it)
                $total_credits += $row['lab_credits'];
            }
        }
    }
    
    return $total_credits;
}

/**
 * Get detailed credit breakdown for a student
 */
function getStudentCreditBreakdown($db, $student_id, $semester, $year) {
    $query = "SELECT s.section_id, s.section_type, s.section_number, s.parent_section_id, 
              c.course_id, c.title, c.theory_credits, c.lab_credits, c.total_credits, c.has_lab,
              -- Check if this theory section has a paired lab that student is also registered for
              (CASE 
                WHEN s.section_type = 'theory' 
                THEN (SELECT s2.section_number FROM registration r2 
                      JOIN sections s2 ON r2.section_id = s2.section_id 
                      WHERE r2.student_id = ? AND r2.status = 'registered' 
                      AND s2.parent_section_id = s.section_id AND s2.section_type = 'lab'
                      LIMIT 1)
                ELSE NULL
              END) as paired_lab_section
              FROM registration r
              JOIN sections s ON r.section_id = s.section_id
              JOIN courses c ON s.course_id = c.course_id
              WHERE r.student_id = ? AND r.status = 'registered' 
              AND s.semester = ? AND s.year = ?
              ORDER BY c.course_id, s.section_type";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param('iisi', $student_id, $student_id, $semester, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    $total_credits = 0;
    
    while ($row = $result->fetch_assoc()) {
        $course_id = $row['course_id'];
        
        if ($row['section_type'] === 'theory') {
            $credits = $row['paired_lab_section'] ? 
                ($row['theory_credits'] + $row['lab_credits']) : $row['theory_credits'];
            
            $courses[] = [
                'course_id' => $course_id,
                'title' => $row['title'],
                'section_number' => $row['section_number'],
                'section_type' => $row['paired_lab_section'] ? 'theory+lab' : 'theory',
                'lab_section' => $row['paired_lab_section'],
                'credits' => $credits,
                'theory_credits' => $row['theory_credits'],
                'lab_credits' => $row['lab_credits']
            ];
            
            $total_credits += $credits;
        } elseif ($row['section_type'] === 'lab' && !$row['parent_section_id']) {
            // Standalone lab (shouldn't happen in normal flow)
            $courses[] = [
                'course_id' => $course_id,
                'title' => $row['title'],
                'section_number' => $row['section_number'],
                'section_type' => 'lab',
                'lab_section' => null,
                'credits' => $row['lab_credits'],
                'theory_credits' => 0,
                'lab_credits' => $row['lab_credits']
            ];
            
            $total_credits += $row['lab_credits'];
        }
    }
    
    return [
        'courses' => $courses,
        'total_credits' => $total_credits
    ];
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
