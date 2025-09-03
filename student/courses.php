<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';
require_once '../includes/validation_helper.php';

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
                        $query = "SELECT sec.*, c.course_id, c.has_lab, c.theory_credits, c.lab_credits FROM sections sec 
                                  JOIN courses c ON sec.course_id = c.course_id 
                                  WHERE sec.section_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param('i', $section_id);
                        $stmt->execute();
                        $section_details = $stmt->get_result()->fetch_assoc();
                        
                        if (!$section_details) {
                            $error_message = 'Section not found.';
                        } else {
                            // Validate course registration logic (duplicates, etc.)
                            $registration_validation = validateCourseRegistrationLogic($db, $student_id, $section_details['course_id'], $section_details['section_type'], $current_semester_info['current_semester'], $current_semester_info['current_year']);
                            
                            if (!$registration_validation['valid']) {
                                $error_message = $registration_validation['error'];
                            } else {
                                // Validate section type against course requirements
                                $course_validation = validateCourseLabComponent($db, $section_details['course_id'], $section_details['section_type']);
                                
                                if (!$course_validation['valid']) {
                                    $error_message = $course_validation['error'];
                                } else {
                                    // Check credit load limit
                                    $section_credits = ($section_details['section_type'] === 'theory') ? 
                                                      $section_details['theory_credits'] : $section_details['lab_credits'];
                                    
                                    $credit_validation = validateStudentCreditLoad($db, $student_id, $current_semester_info['current_semester'], $current_semester_info['current_year'], $section_credits);
                                    
                                    if (!$credit_validation['valid']) {
                                        $error_message = $credit_validation['error'];
                                    } else {
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
                                                    $success_message = 'Successfully registered for the course!';
                                                    
                                                    // Auto-register for paired lab if this is a theory section
                                                    if ($section_details['section_type'] === 'theory') {
                                                        $lab_result = autoRegisterPairedLab($db, $student_id, $section_id);
                                                        if ($lab_result['success'] && !empty($lab_result['message'])) {
                                                            $success_message .= '<br>' . $lab_result['message'];
                                                        } elseif (!$lab_result['success']) {
                                                            $success_message .= '<br><span class="text-warning">Warning: ' . $lab_result['error'] . '</span>';
                                                        }
                                                    }
                                                    
                                                    $message = $success_message;
                                                } else {
                                                    $error_message = 'Failed to register. Please try again.';
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } elseif ($action === 'drop') {
            // Get section details to check if it's a theory section
            $query = "SELECT section_type, course_id FROM sections WHERE section_id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('i', $section_id);
            $stmt->execute();
            $section_details = $stmt->get_result()->fetch_assoc();
            
            // Drop the main course
            $query = "UPDATE registration SET status = 'dropped' 
                      WHERE student_id = ? AND section_id = ? AND status = 'registered'";
            $stmt = $db->prepare($query);
            $stmt->bind_param('ii', $student_id, $section_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success_message = 'Successfully dropped the course!';
                
                // Auto-drop paired lab if this is a theory section
                if ($section_details && $section_details['section_type'] === 'theory') {
                    $lab_drop_result = autoDropPairedLab($db, $student_id, $section_id);
                    if ($lab_drop_result['success'] && !empty($lab_drop_result['message'])) {
                        $success_message .= '<br>' . $lab_drop_result['message'];
                    }
                }
                
                $message = $success_message;
            } else {
                $error_message = 'Failed to drop the course. Please try again.';
            }
        }
    }
}

// Get available theory sections for current semester with lab information
$query = "SELECT sec.*, c.title, c.total_credits as credits, c.course_id, c.has_lab, c.theory_credits, c.lab_credits,
          f.name as faculty_name, d.name as department_name,
          r.room_number, r.building, r.room_type,
          (SELECT COUNT(*) FROM registration WHERE section_id = sec.section_id AND status = 'registered') as enrolled,
          (SELECT COUNT(*) FROM registration WHERE section_id = sec.section_id AND student_id = ? AND status = 'registered') as is_registered,
          GROUP_CONCAT(DISTINCT prereq_courses.course_id) as prerequisites,
          -- Lab section information
          lab_sec.section_id as lab_section_id,
          lab_sec.section_number as lab_section_number,
          lab_sec.schedule_days as lab_schedule_days,
          lab_sec.schedule_time as lab_schedule_time,
          lab_sec.capacity as lab_capacity,
          lab_faculty.name as lab_faculty_name,
          lab_room.room_number as lab_room_number,
          lab_room.building as lab_building,
          (SELECT COUNT(*) FROM registration WHERE section_id = lab_sec.section_id AND status = 'registered') as lab_enrolled,
          (SELECT COUNT(*) FROM registration WHERE section_id = lab_sec.section_id AND student_id = ? AND status = 'registered') as lab_is_registered
          FROM sections sec 
          JOIN courses c ON sec.course_id = c.course_id 
          JOIN faculty f ON sec.faculty_id = f.faculty_id 
          JOIN departments d ON c.department_id = d.department_id
          JOIN rooms r ON sec.room_id = r.room_id
          LEFT JOIN prerequisites p ON c.course_id = p.course_id
          LEFT JOIN courses prereq_courses ON p.prerequisite_course_id = prereq_courses.course_id
          -- Join with paired lab sections
          LEFT JOIN sections lab_sec ON (lab_sec.parent_section_id = sec.section_id AND lab_sec.section_type = 'lab')
          LEFT JOIN faculty lab_faculty ON lab_sec.faculty_id = lab_faculty.faculty_id
          LEFT JOIN rooms lab_room ON lab_sec.room_id = lab_room.room_id
          WHERE sec.semester = ? AND sec.year = ? AND sec.section_type = 'theory'
          GROUP BY sec.section_id
          ORDER BY c.course_id, sec.section_number";
$stmt = $db->prepare($query);
$stmt->bind_param('iisi', $student_id, $student_id, $current_semester_info['current_semester'], $current_semester_info['current_year']);
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
                            <th>Section</th>
                            <th>Course</th>
                            <th>Title</th>
                            <th>Credits</th>
                            <th>Faculty</th>
                            <th>Theory Schedule</th>
                            <th>Lab Information</th>
                            <th>Room</th>
                            <th>Capacity</th>
                            <th>Prerequisites</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($section = $sections->fetch_assoc()): 
                            // Calculate combined credits for display
                            $display_credits = $section['has_lab'] && $section['lab_section_id'] ? 
                                ($section['theory_credits'] + $section['lab_credits']) : $section['theory_credits'];
                        ?>
                        <tr class="<?php echo $section['is_registered'] > 0 ? 'table-success' : ''; ?>">
                            <td>
                                <strong><?php echo sanitizeInput($section['course_id']) . '-' . sanitizeInput($section['section_number']); ?></strong>
                                <small class="text-muted d-block">Theory Section</small>
                                <?php if ($section['has_lab'] && $section['lab_section_id']): ?>
                                    <small class="text-info d-block">+ Lab: <?php echo sanitizeInput($section['course_id']) . '-' . sanitizeInput($section['lab_section_number']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo sanitizeInput($section['course_id']); ?></strong></td>
                            <td>
                                <?php echo sanitizeInput($section['title']); ?>
                                <?php if ($section['has_lab']): ?>
                                    <span class="badge bg-info ms-2">Has Lab</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo $display_credits; ?> credits</strong>
                                <?php if ($section['has_lab'] && $section['lab_section_id']): ?>
                                    <small class="text-muted d-block">
                                        Theory: <?php echo $section['theory_credits']; ?> + Lab: <?php echo $section['lab_credits']; ?>
                                    </small>
                                <?php elseif ($section['has_lab'] && !$section['lab_section_id']): ?>
                                    <small class="text-warning d-block">
                                        Lab section not available
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo sanitizeInput($section['faculty_name']); ?></strong>
                                <?php if ($section['has_lab'] && $section['lab_section_id'] && $section['lab_faculty_name']): ?>
                                    <small class="text-muted d-block">Lab: <?php echo sanitizeInput($section['lab_faculty_name']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo formatDaysDisplay($section['schedule_days']); ?></strong>
                                <small class="text-muted d-block"><?php echo formatTimeDisplay($section['schedule_time']); ?></small>
                            </td>
                            <td>
                                <?php if ($section['has_lab'] && $section['lab_section_id']): ?>
                                    <div class="text-success">
                                        <i class="fas fa-flask me-1"></i><strong>Available</strong>
                                        <small class="d-block text-muted">
                                            <?php echo formatDaysDisplay($section['lab_schedule_days']); ?> 
                                            <?php echo formatTimeDisplay($section['lab_schedule_time']); ?>
                                        </small>
                                        <small class="d-block text-muted">
                                            Room: <?php echo sanitizeInput($section['lab_room_number']); ?>
                                            <?php if ($section['lab_building']): echo ' (' . sanitizeInput($section['lab_building']) . ')'; endif; ?>
                                        </small>
                                        <span class="badge <?php echo $section['lab_enrolled'] >= $section['lab_capacity'] ? 'bg-danger' : 'bg-primary'; ?> mt-1">
                                            <?php echo $section['lab_enrolled'] . '/' . $section['lab_capacity']; ?>
                                        </span>
                                    </div>
                                <?php elseif ($section['has_lab']): ?>
                                    <div class="text-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <small>Lab section not configured</small>
                                    </div>
                                <?php else: ?>
                                    <small class="text-muted">No lab required</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo sanitizeInput($section['room_number']); ?></strong>
                                <?php if ($section['building']): ?>
                                    <small class="text-muted d-block"><?php echo sanitizeInput($section['building']); ?></small>
                                <?php endif; ?>
                                <span class="badge bg-<?php echo $section['room_type'] === 'lab' ? 'info' : ($section['room_type'] === 'both' ? 'success' : 'secondary'); ?> mt-1">
                                    <?php echo ucfirst($section['room_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $section['enrolled'] >= $section['capacity'] ? 'bg-danger' : 'bg-primary'; ?>">
                                    <?php echo $section['enrolled'] . '/' . $section['capacity']; ?>
                                </span>
                                <?php if ($section['has_lab'] && $section['lab_section_id']): ?>
                                    <small class="text-muted d-block">Theory capacity</small>
                                <?php endif; ?>
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
                                                onclick="return confirm('Are you sure you want to drop this course?<?php echo $section['has_lab'] && $section['lab_section_id'] ? ' This will also drop you from the paired lab section.' : ''; ?>')">
                                            <i class="fas fa-times me-1"></i>Drop
                                            <?php if ($section['has_lab'] && $section['lab_section_id'] && $section['lab_is_registered'] > 0): ?>
                                                <small class="d-block">+ Lab</small>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                <?php elseif ($current_semester_info['registration_open']): ?>
                                    <?php if ($section['enrolled'] >= $section['capacity']): ?>
                                        <span class="badge bg-danger">Full</span>
                                    <?php elseif ($section['has_lab'] && $section['lab_section_id'] && $section['lab_enrolled'] >= $section['lab_capacity']): ?>
                                        <span class="badge bg-warning text-dark">Lab Full</span>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="register">
                                            <input type="hidden" name="section_id" value="<?php echo $section['section_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-plus me-1"></i>Register
                                                <?php if ($section['has_lab'] && $section['lab_section_id']): ?>
                                                    <small class="d-block">+ Lab</small>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Reg. Closed</span>
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
