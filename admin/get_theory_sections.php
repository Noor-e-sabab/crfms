<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

checkUserType('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$course_id = sanitizeInput($_POST['course_id'] ?? '');
$semester = sanitizeInput($_POST['semester'] ?? '');
$year = (int)($_POST['year'] ?? 0);

if (empty($course_id) || empty($semester) || $year <= 0) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

try {
    // Get theory sections for the specified course, semester, and year
    $query = "SELECT s.section_id, s.section_number, s.schedule_days, s.schedule_time, f.name as faculty_name
              FROM sections s
              JOIN faculty f ON s.faculty_id = f.faculty_id
              WHERE s.course_id = ? AND s.semester = ? AND s.year = ? AND s.section_type = 'theory'
              ORDER BY s.section_number";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param('ssi', $course_id, $semester, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = [
            'section_id' => $row['section_id'],
            'section_number' => $row['section_number'],
            'schedule_days' => $row['schedule_days'],
            'schedule_time' => $row['schedule_time'],
            'faculty_name' => $row['faculty_name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'sections' => $sections
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
