<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

checkUserType('admin');

header('Content-Type: application/json');

if (!isset($_GET['course_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Course ID required']);
    exit();
}

$course_id = $_GET['course_id'];

// Get course details
$query = "SELECT c.*, GROUP_CONCAT(p.prerequisite_course_id) as prerequisites
          FROM courses c 
          LEFT JOIN prerequisites p ON c.course_id = p.course_id
          WHERE c.course_id = ?
          GROUP BY c.course_id";
$stmt = $db->prepare($query);
$stmt->bind_param('s', $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Course not found']);
    exit();
}

$course = $result->fetch_assoc();
$course['prerequisites'] = !empty($course['prerequisites']) ? explode(',', $course['prerequisites']) : [];

echo json_encode($course);
?>
