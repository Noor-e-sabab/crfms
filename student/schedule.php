<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

checkUserType('student');

$student_id = $_SESSION['user_id'];
$current_semester_info = getCurrentSemester($db);

// Check if system is configured
if (!$current_semester_info['current_semester'] || !$current_semester_info['current_year']) {
    $system_configured = false;
} else {
    $system_configured = true;
}

// Get current semester schedule only if system is configured - show theory and lab separately with proper grouping
if ($system_configured) {
    $query = "SELECT sec.*, c.title, c.has_lab, c.theory_credits, c.lab_credits, c.course_id, sec.section_number,
              f.name as faculty_name, r.registration_date, rm.room_number as room,
              -- For theory sections, get paired lab info
              (CASE WHEN sec.section_type = 'theory' THEN
                (SELECT lab_sec.section_id FROM sections lab_sec 
                 JOIN registration r2 ON lab_sec.section_id = r2.section_id
                 WHERE r2.student_id = ? AND r2.status = 'registered' 
                 AND lab_sec.parent_section_id = sec.section_id AND lab_sec.section_type = 'lab'
                 LIMIT 1)
               ELSE sec.parent_section_id END) as paired_section_id
              FROM registration r 
              JOIN sections sec ON r.section_id = sec.section_id 
              JOIN courses c ON sec.course_id = c.course_id 
              JOIN faculty f ON sec.faculty_id = f.faculty_id
              JOIN rooms rm ON sec.room_id = rm.room_id
              WHERE r.student_id = ? AND r.status = 'registered' 
              AND sec.semester = ? AND sec.year = ?
              ORDER BY sec.schedule_days, sec.schedule_time";
    $stmt = $db->prepare($query);
    $stmt->bind_param('iisi', $student_id, $student_id, $current_semester_info['current_semester'], $current_semester_info['current_year']);
    $stmt->execute();
    $current_schedule = $stmt->get_result();
} else {
    $current_schedule = null;
}

// Get all enrollments with grades - show theory sections with combined credits
$query = "SELECT sec.semester, sec.year, c.course_id, c.title, c.has_lab, c.theory_credits, c.lab_credits,
          sec.section_number, sec.section_type, f.name as faculty_name, e.grade,
          -- For theory sections, check if student also completed lab
          (CASE WHEN sec.section_type = 'theory' AND c.has_lab THEN
            (SELECT COUNT(*) FROM enrollments e2 
             JOIN sections lab_sec ON e2.section_id = lab_sec.section_id 
             WHERE e2.student_id = ? AND lab_sec.parent_section_id = sec.section_id 
             AND lab_sec.section_type = 'lab' AND e2.grade IS NOT NULL)
           ELSE 0 END) as has_lab_grade
          FROM enrollments e 
          JOIN sections sec ON e.section_id = sec.section_id 
          JOIN courses c ON sec.course_id = c.course_id 
          JOIN faculty f ON sec.faculty_id = f.faculty_id
          WHERE e.student_id = ? AND e.grade IS NOT NULL AND sec.section_type = 'theory'
          ORDER BY sec.year DESC, sec.semester DESC";
$stmt = $db->prepare($query);
$stmt->bind_param('ii', $student_id, $student_id);
$stmt->execute();
$all_grades = $stmt->get_result();

// Calculate GPA with proper credit calculation
function calculateGPA($grades_array) {
    $grade_points = [
        'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
        'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
        'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
        'D+' => 1.3, 'D' => 1.0, 'F' => 0.0
    ];
    
    $total_points = 0;
    $total_credits = 0;
    
    foreach ($grades_array as $grade) {
        if (isset($grade_points[$grade['grade']])) {
            // Use correct credits - combined for theory+lab
            $credits = $grade['has_lab_grade'] > 0 ? 
                ($grade['theory_credits'] + $grade['lab_credits']) : $grade['theory_credits'];
            
            $total_points += $grade_points[$grade['grade']] * $credits;
            $total_credits += $credits;
        }
    }
    
    return $total_credits > 0 ? round($total_points / $total_credits, 2) : 0;
}

$page_title = 'My Schedule & Grades';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Schedule & Grades</h1>
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
        The academic semester has not been configured yet. Schedule information is not available. Please contact the administration to set up the current semester.
    </div>
<?php else: ?>

<!-- Current Semester Schedule -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar me-2"></i>Current Semester Schedule
                </h5>
            </div>
            <div class="card-body">
                <?php if ($current_schedule->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Title</th>
                                    <th>Credits</th>
                                    <th>Faculty</th>
                                    <th>Schedule</th>
                                    <th>Room</th>
                                    <th>Registered On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_current_credits = 0;
                                while ($course = $current_schedule->fetch_assoc()): 
                                    // Skip lab sections since we show them combined with theory
                                    if ($course['section_type'] === 'lab') continue;
                                    
                                    // Calculate proper credits - combined for theory+lab
                                    if ($course['has_lab'] && $course['paired_section_id']) {
                                        $display_credits = $course['theory_credits'] + $course['lab_credits'];
                                    } else {
                                        $display_credits = $course['theory_credits'];
                                    }
                                    $total_current_credits += $display_credits;
                                ?>
                                <tr>
                                    <td><strong><?php echo sanitizeInput($course['course_id']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo sanitizeInput($course['section_number'] ?? 'N/A'); ?>
                                        </span>
                                        <?php if ($course['has_lab'] && $course['paired_section_id']): ?>
                                            <span class="badge bg-info ms-1">+ Lab</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo sanitizeInput($course['title']); ?>
                                        <?php if ($course['has_lab'] && $course['paired_section_id']): ?>
                                            <small class="text-muted d-block">Includes paired lab section</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $display_credits; ?></strong>
                                        <?php if ($course['has_lab'] && $course['paired_section_id']): ?>
                                            <small class="text-muted d-block">
                                                (<?php echo $course['theory_credits']; ?> + <?php echo $course['lab_credits']; ?>)
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo sanitizeInput($course['faculty_name']); ?></td>
                                    <td>
                                        <span class="schedule-time">
                                            <?php echo formatSchedule($course['schedule_days'], $course['schedule_time']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo sanitizeInput($course['room']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($course['registration_date'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-info">
                                    <td colspan="3"><strong>Total Credits</strong></td>
                                    <td><strong><?php echo $total_current_credits; ?></strong></td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No courses registered for the current semester.</p>
                        <a href="courses.php" class="btn btn-primary">Browse Available Courses</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Grades History -->
<div class="row" id="grades">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>Academic History & Grades
                </h5>
                <?php 
                $grades_array = $all_grades->fetch_all(MYSQLI_ASSOC);
                $gpa = calculateGPA($grades_array);
                if ($gpa > 0): 
                ?>
                <span class="badge bg-success fs-6">GPA: <?php echo $gpa; ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($grades_array)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Semester</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Title</th>
                                    <th>Credits</th>
                                    <th>Faculty</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $current_sem = '';
                                foreach ($grades_array as $grade): 
                                    $sem_year = $grade['semester'] . ' ' . $grade['year'];
                                    if ($sem_year !== $current_sem) {
                                        $current_sem = $sem_year;
                                        echo '<tr class="table-secondary"><td colspan="7"><strong>' . $sem_year . '</strong></td></tr>';
                                    }
                                    
                                    // Calculate proper credits for grades
                                    $grade_credits = $grade['has_lab_grade'] > 0 ? 
                                        ($grade['theory_credits'] + $grade['lab_credits']) : $grade['theory_credits'];
                                ?>
                                <tr>
                                    <td></td>
                                    <td><strong><?php echo sanitizeInput($grade['course_id']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo sanitizeInput($grade['section_number'] ?? 'N/A'); ?>
                                        </span>
                                        <?php if ($grade['has_lab'] && $grade['has_lab_grade'] > 0): ?>
                                            <span class="badge bg-info ms-1">+ Lab</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo sanitizeInput($grade['title']); ?>
                                        <?php if ($grade['has_lab'] && $grade['has_lab_grade'] > 0): ?>
                                            <small class="text-muted d-block">Completed with lab</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $grade_credits; ?></strong>
                                        <?php if ($grade['has_lab'] && $grade['has_lab_grade'] > 0): ?>
                                            <small class="text-muted d-block">
                                                (<?php echo $grade['theory_credits']; ?> + <?php echo $grade['lab_credits']; ?>)
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo sanitizeInput($grade['faculty_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo in_array($grade['grade'], ['A+', 'A', 'A-']) ? 'success' : 
                                                (in_array($grade['grade'], ['B+', 'B', 'B-']) ? 'primary' : 
                                                (in_array($grade['grade'], ['C+', 'C', 'C-']) ? 'warning' : 'danger')); 
                                        ?>">
                                            <?php echo sanitizeInput($grade['grade']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- GPA Calculation Summary -->
                    <div class="mt-3 p-3 bg-light rounded">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Total Completed Credits:</strong> 
                                <?php 
                                $total_completed_credits = 0;
                                foreach ($grades_array as $grade) {
                                    $credits = $grade['has_lab_grade'] > 0 ? 
                                        ($grade['theory_credits'] + $grade['lab_credits']) : $grade['theory_credits'];
                                    $total_completed_credits += $credits;
                                }
                                echo $total_completed_credits; 
                                ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Cumulative GPA:</strong> 
                                <span class="text-success"><?php echo $gpa; ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Academic Standing:</strong> 
                                <span class="badge bg-<?php echo $gpa >= 3.0 ? 'success' : ($gpa >= 2.0 ? 'warning' : 'danger'); ?>">
                                    <?php 
                                    if ($gpa >= 3.5) echo 'Excellent';
                                    elseif ($gpa >= 3.0) echo 'Good';
                                    elseif ($gpa >= 2.0) echo 'Satisfactory';
                                    else echo 'Needs Improvement';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No grades available yet.</p>
                        <small class="text-muted">Grades will appear here once faculty submit them.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; // End system configuration check ?>

<?php require_once '../includes/footer.php'; ?>
