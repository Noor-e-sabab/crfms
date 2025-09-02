<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

checkUserType('student');

$student_id = $_SESSION['user_id'];
$current_semester_info = getCurrentSemester($db);
$message = '';
$error_message = '';

// Check if system is configured
if (!$current_semester_info['current_semester'] || !$current_semester_info['current_year']) {
    $system_configured = false;
} else {
    $system_configured = true;
}

// Handle registration/drop actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'];
        $section_id = (int)$_POST['section_id'];
        
        if ($action === 'register') {
            // Check if registration is open
            if (!$current_semester_info['registration_open']) {
                $error_message = 'Registration is currently closed.';
            } else {
                // Check section capacity
                $query = "SELECT capacity, 
                          (SELECT COUNT(*) FROM registration WHERE section_id = ? AND status = 'registered') as enrolled
                          FROM sections WHERE section_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param('ii', $section_id, $section_id);
                $stmt->execute();
                $capacity_info = $stmt->get_result()->fetch_assoc();
                
                if ($capacity_info['enrolled'] >= $capacity_info['capacity']) {
                    $error_message = 'This section is full.';
                } else {
                    // Check if already registered
                    $query = "SELECT * FROM registration WHERE student_id = ? AND section_id = ? AND status = 'registered'";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('ii', $student_id, $section_id);
                    $stmt->execute();
                    
                    if ($stmt->get_result()->num_rows > 0) {
                        $error_message = 'You are already registered for this section.';
                    } else {
                        // Get section details for validation
                        $query = "SELECT sec.*, c.course_id FROM sections sec 
                                  JOIN courses c ON sec.course_id = c.course_id 
                                  WHERE sec.section_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param('i', $section_id);
                        $stmt->execute();
                        $section_details = $stmt->get_result()->fetch_assoc();
                        
                        // Check time conflicts
                        $query = "SELECT sec.schedule_days, sec.schedule_time FROM registration r 
                                  JOIN sections sec ON r.section_id = sec.section_id 
                                  WHERE r.student_id = ? AND r.status = 'registered' 
                                  AND sec.semester = ? AND sec.year = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param('isi', $student_id, $current_semester_info['current_semester'], $current_semester_info['current_year']);
                        $stmt->execute();
                        $existing_schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        
                        if (checkTimeConflict($section_details['schedule_days'], $section_details['schedule_time'], $existing_schedules)) {
                            $error_message = 'Time conflict with your existing schedule.';
                        } else {
                            // Check prerequisites
                            $query = "SELECT p.prerequisite_course_id FROM prerequisites p 
                                      WHERE p.course_id = ?";
                            $stmt = $db->prepare($query);
                            $stmt->bind_param('s', $section_details['course_id']);
                            $stmt->execute();
                            $prerequisites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            $prereq_met = true;
                            foreach ($prerequisites as $prereq) {
                                $query = "SELECT grade FROM enrollments e 
                                          JOIN sections sec ON e.section_id = sec.section_id 
                                          WHERE e.student_id = ? AND sec.course_id = ? 
                                          AND grade IN ('A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D')";
                                $stmt = $db->prepare($query);
                                $stmt->bind_param('is', $student_id, $prereq['prerequisite_course_id']);
                                $stmt->execute();
                                
                                if ($stmt->get_result()->num_rows === 0) {
                                    $prereq_met = false;
                                    break;
                                }
                            }
                            
                            if (!$prereq_met) {
                                $error_message = 'Prerequisites not met for this course.';
                            } else {
                                // Register for the course
                                $query = "INSERT INTO registration (student_id, section_id) VALUES (?, ?)";
                                $stmt = $db->prepare($query);
                                $stmt->bind_param('ii', $student_id, $section_id);
                                
                                if ($stmt->execute()) {
                                    $message = 'Successfully registered for the course!';
                                } else {
                                    $error_message = 'Failed to register. Please try again.';
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($action === 'drop') {
            // Drop the course
            $query = "UPDATE registration SET status = 'dropped' 
                      WHERE student_id = ? AND section_id = ? AND status = 'registered'";
            $stmt = $db->prepare($query);
            $stmt->bind_param('ii', $student_id, $section_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message = 'Successfully dropped the course!';
            } else {
                $error_message = 'Failed to drop the course. Please try again.';
            }
        }
    }
}

// Get available sections for current semester
$query = "SELECT sec.*, c.title, c.credits, c.course_id, f.name as faculty_name, d.name as department_name,
          (SELECT COUNT(*) FROM registration WHERE section_id = sec.section_id AND status = 'registered') as enrolled,
          (SELECT COUNT(*) FROM registration WHERE section_id = sec.section_id AND student_id = ? AND status = 'registered') as is_registered,
          GROUP_CONCAT(DISTINCT prereq_courses.course_id) as prerequisites
          FROM sections sec 
          JOIN courses c ON sec.course_id = c.course_id 
          JOIN faculty f ON sec.faculty_id = f.faculty_id 
          JOIN departments d ON c.department_id = d.department_id
          LEFT JOIN prerequisites p ON c.course_id = p.course_id
          LEFT JOIN courses prereq_courses ON p.prerequisite_course_id = prereq_courses.course_id
          WHERE sec.semester = ? AND sec.year = ?
          GROUP BY sec.section_id
          ORDER BY c.course_id, sec.section_id";
$stmt = $db->prepare($query);
$stmt->bind_param('isi', $student_id, $current_semester_info['current_semester'], $current_semester_info['current_year']);
$stmt->execute();
$sections = $stmt->get_result();

$page_title = 'Available Courses';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Available Courses</h1>
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
        The academic semester has not been configured yet. Course registration is not available. Please contact the administration to set up the current semester.
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

<?php if (!$current_semester_info['registration_open']): ?>
    <div class="alert alert-warning alert-custom">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Registration Closed:</strong> Course registration is currently closed for this semester.
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if ($sections->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Title</th>
                            <th>Credits</th>
                            <th>Faculty</th>
                            <th>Schedule</th>
                            <th>Room</th>
                            <th>Capacity</th>
                            <th>Prerequisites</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($section = $sections->fetch_assoc()): ?>
                        <tr class="<?php echo $section['is_registered'] > 0 ? 'table-success' : ''; ?>">
                            <td><strong><?php echo sanitizeInput($section['course_id']); ?></strong></td>
                            <td><?php echo sanitizeInput($section['title']); ?></td>
                            <td><?php echo $section['credits']; ?></td>
                            <td><?php echo sanitizeInput($section['faculty_name']); ?></td>
                            <td>
                                <span class="schedule-time">
                                    <?php echo formatSchedule($section['schedule_days'], $section['schedule_time']); ?>
                                </span>
                            </td>
                            <td><?php echo sanitizeInput($section['room']); ?></td>
                            <td>
                                <span class="badge <?php echo $section['enrolled'] >= $section['capacity'] ? 'bg-danger' : 'bg-primary'; ?>">
                                    <?php echo $section['enrolled'] . '/' . $section['capacity']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($section['prerequisites'])): ?>
                                    <small class="text-muted"><?php echo sanitizeInput($section['prerequisites']); ?></small>
                                <?php else: ?>
                                    <small class="text-success">None</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($section['is_registered'] > 0): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="drop">
                                        <input type="hidden" name="section_id" value="<?php echo $section['section_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Are you sure you want to drop this course?')">
                                            <i class="fas fa-times me-1"></i>Drop
                                        </button>
                                    </form>
                                <?php elseif ($current_semester_info['registration_open']): ?>
                                    <?php if ($section['enrolled'] >= $section['capacity']): ?>
                                        <span class="badge bg-danger">Full</span>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="register">
                                            <input type="hidden" name="section_id" value="<?php echo $section['section_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-plus me-1"></i>Register
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Closed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No courses available</h5>
                <p class="text-muted">There are no course sections available for the current semester.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; // End system configuration check ?>

<?php require_once '../includes/footer.php'; ?>
