<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

checkUserType('faculty');

$faculty_id = $_SESSION['user_id'];
$current_semester_info = getCurrentSemester($db);

// Check if system is configured
if (!$current_semester_info['current_semester'] || !$current_semester_info['current_year']) {
    $system_configured = false;
} else {
    $system_configured = true;
}
$message = '';
$error_message = '';

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grades'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $section_id = (int)$_POST['section_id'];
        $grades = $_POST['grades'];
        
        // Verify this section belongs to current faculty
        $query = "SELECT * FROM sections WHERE section_id = ? AND faculty_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('ii', $section_id, $faculty_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $success_count = 0;
            foreach ($grades as $student_id => $grade) {
                if (!empty(trim($grade))) {
                    $grade = trim($grade);
                    
                    // Check if enrollment exists
                    $query = "SELECT * FROM enrollments WHERE student_id = ? AND section_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('ii', $student_id, $section_id);
                    $stmt->execute();
                    
                    if ($stmt->get_result()->num_rows > 0) {
                        // Update existing enrollment
                        $query = "UPDATE enrollments SET grade = ?, updated_at = CURRENT_TIMESTAMP 
                                  WHERE student_id = ? AND section_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param('sii', $grade, $student_id, $section_id);
                    } else {
                        // Insert new enrollment
                        $query = "INSERT INTO enrollments (student_id, section_id, grade) VALUES (?, ?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param('iis', $student_id, $section_id, $grade);
                    }
                    
                    if ($stmt->execute()) {
                        $success_count++;
                    }
                }
            }
            
            if ($success_count > 0) {
                $message = "Successfully updated grades for $success_count students.";
            } else {
                $error_message = "No grades were updated.";
            }
        } else {
            $error_message = "You don't have permission to grade this section.";
        }
    }
}

// Get faculty sections for current semester
$query = "SELECT sec.*, c.title, c.course_id, c.total_credits as credits, sec.section_number,
          r.room_number as room, r.building,
          (SELECT COUNT(*) FROM registration WHERE section_id = sec.section_id AND status = 'registered') as enrolled
          FROM sections sec 
          JOIN courses c ON sec.course_id = c.course_id
          LEFT JOIN rooms r ON sec.room_id = r.room_id
          WHERE sec.faculty_id = ? AND sec.semester = ? AND sec.year = ?
          ORDER BY sec.section_id";
$stmt = $db->prepare($query);
$stmt->bind_param('isi', $faculty_id, $current_semester_info['current_semester'], $current_semester_info['current_year']);
$stmt->execute();
$sections = $stmt->get_result();

// Get selected section details if viewing a specific section
$selected_section = null;
$students_in_section = [];
if (isset($_GET['section_id'])) {
    $section_id = (int)$_GET['section_id'];
    
    // Get section details
    $query = "SELECT sec.*, c.title, c.course_id, c.total_credits as credits, sec.section_number,
              r.room_number as room, r.building
              FROM sections sec 
              JOIN courses c ON sec.course_id = c.course_id
              LEFT JOIN rooms r ON sec.room_id = r.room_id
              WHERE sec.section_id = ? AND sec.faculty_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ii', $section_id, $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selected_section = $result->fetch_assoc();
        
        // Get students in this section
        $query = "SELECT s.student_id, s.name, s.email, r.registration_date,
                  e.grade, e.updated_at as grade_updated
                  FROM registration r 
                  JOIN students s ON r.student_id = s.student_id
                  LEFT JOIN enrollments e ON s.student_id = e.student_id AND e.section_id = r.section_id
                  WHERE r.section_id = ? AND r.status = 'registered'
                  ORDER BY s.name";
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $section_id);
        $stmt->execute();
        $students_in_section = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$page_title = 'My Sections';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Sections</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($system_configured): ?>
            <span class="badge bg-info fs-6">
                <?php echo $current_semester_info['current_semester'] . ' ' . $current_semester_info['current_year']; ?>
            </span>
        <?php else: ?>
            <span class="badge bg-warning fs-6">System Not Configured</span>
        <?php endif; ?>
    </div>
</div>

<?php if (!$system_configured): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>System Not Configured</strong><br>
        The academic semester has not been configured yet. Section information is not available. Please contact the administration to set up the current semester.
    </div>
<?php else: ?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$selected_section): ?>
<!-- Sections List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-layer-group me-2"></i>Current Semester Sections
        </h5>
    </div>
    <div class="card-body">
        <?php if ($sections->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Section</th>
                            <th>Title</th>
                            <th>Credits</th>
                            <th>Schedule</th>
                            <th>Room</th>
                            <th>Enrolled</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sections->data_seek(0); // Reset pointer
                        while ($section = $sections->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><strong><?php echo sanitizeInput($section['course_id']); ?></strong></td>
                            <td>
                                <span class="badge bg-primary">
                                    <?php echo sanitizeInput($section['section_number'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td><?php echo sanitizeInput($section['title']); ?></td>
                            <td><?php echo $section['credits']; ?></td>
                            <td>
                                <span class="schedule-time">
                                    <?php echo formatSchedule($section['schedule_days'], $section['schedule_time']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($section['room'])): ?>
                                    <?php echo sanitizeInput($section['room']); ?>
                                    <?php if (!empty($section['building'])): ?>
                                        <br><small class="text-muted"><?php echo sanitizeInput($section['building']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-primary">
                                    <?php echo $section['enrolled'] . '/' . $section['capacity']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="?section_id=<?php echo $section['section_id']; ?>" 
                                   class="btn btn-sm btn-primary me-1">
                                    <i class="fas fa-users me-1"></i>View Students
                                </a>
                                <a href="?section_id=<?php echo $section['section_id']; ?>#grading" 
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-clipboard-list me-1"></i>Grade
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-chalkboard fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No sections assigned</h5>
                <p class="text-muted">You don't have any sections assigned for the current semester.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Section Details -->
<div class="mb-3">
    <a href="sections.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Sections
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-info-circle me-2"></i>Section Details
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Course:</strong><br>
                <?php echo sanitizeInput($selected_section['course_id']); ?>
            </div>
            <div class="col-md-2">
                <strong>Section:</strong><br>
                <span class="badge bg-primary">
                    <?php echo sanitizeInput($selected_section['section_number'] ?? 'N/A'); ?>
                </span>
            </div>
            <div class="col-md-3">
                <strong>Title:</strong><br>
                <?php echo sanitizeInput($selected_section['title']); ?>
            </div>
            <div class="col-md-3">
                <strong>Schedule:</strong><br>
                <?php echo formatSchedule($selected_section['schedule_days'], $selected_section['schedule_time']); ?>
            </div>
            <div class="col-md-3">
                <strong>Room:</strong><br>
                <?php if (!empty($selected_section['room'])): ?>
                    <?php echo sanitizeInput($selected_section['room']); ?>
                    <?php if (!empty($selected_section['building'])): ?>
                        <br><small class="text-muted"><?php echo sanitizeInput($selected_section['building']); ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted">Not assigned</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Students List and Grading -->
<div class="card" id="grading">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-users me-2"></i>Enrolled Students & Grades
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($students_in_section)): ?>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="section_id" value="<?php echo $selected_section['section_id']; ?>">
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                                <th>Current Grade</th>
                                <th>New Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students_in_section as $student): ?>
                            <tr>
                                <td><?php echo sanitizeInput($student['name']); ?></td>
                                <td><?php echo sanitizeInput($student['email']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($student['registration_date'])); ?></td>
                                <td>
                                    <?php if ($student['grade']): ?>
                                        <span class="badge bg-success">
                                            <?php echo sanitizeInput($student['grade']); ?>
                                        </span>
                                        <br><small class="text-muted">
                                            Updated: <?php echo date('M j, Y', strtotime($student['grade_updated'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not graded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <select name="grades[<?php echo $student['student_id']; ?>]" 
                                            class="form-select form-select-sm" style="width: 100px;">
                                        <option value="">Select</option>
                                        <option value="A+" <?php echo $student['grade'] === 'A+' ? 'selected' : ''; ?>>A+</option>
                                        <option value="A" <?php echo $student['grade'] === 'A' ? 'selected' : ''; ?>>A</option>
                                        <option value="A-" <?php echo $student['grade'] === 'A-' ? 'selected' : ''; ?>>A-</option>
                                        <option value="B+" <?php echo $student['grade'] === 'B+' ? 'selected' : ''; ?>>B+</option>
                                        <option value="B" <?php echo $student['grade'] === 'B' ? 'selected' : ''; ?>>B</option>
                                        <option value="B-" <?php echo $student['grade'] === 'B-' ? 'selected' : ''; ?>>B-</option>
                                        <option value="C+" <?php echo $student['grade'] === 'C+' ? 'selected' : ''; ?>>C+</option>
                                        <option value="C" <?php echo $student['grade'] === 'C' ? 'selected' : ''; ?>>C</option>
                                        <option value="C-" <?php echo $student['grade'] === 'C-' ? 'selected' : ''; ?>>C-</option>
                                        <option value="D+" <?php echo $student['grade'] === 'D+' ? 'selected' : ''; ?>>D+</option>
                                        <option value="D" <?php echo $student['grade'] === 'D' ? 'selected' : ''; ?>>D</option>
                                        <option value="F" <?php echo $student['grade'] === 'F' ? 'selected' : ''; ?>>F</option>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <button type="submit" name="submit_grades" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Submit Grades
                    </button>
                    <small class="text-muted ms-3">
                        Only students with selected grades will be updated.
                    </small>
                </div>
            </form>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-user-times fa-3x text-muted mb-3"></i>
                <p class="text-muted">No students enrolled in this section.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; // End system configuration check ?>

<?php require_once '../includes/footer.php'; ?>
