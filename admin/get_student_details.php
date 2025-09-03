<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

checkUserType('admin');

header('Content-Type: application/json');

if (!isset($_GET['student_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Student ID required']);
    exit();
}

$student_id = (int)$_GET['student_id'];

// Get student details
$query = "SELECT * FROM students WHERE student_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Student not found']);
    exit();
}

$student = $result->fetch_assoc();

echo json_encode($student);
?>
