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

// Get student information
$query = "SELECT s.*, d.name as department_name FROM students s 
          LEFT JOIN departments d ON s.department_id = d.department_id 
          WHERE s.student_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student_info = $stmt->get_result()->fetch_assoc();

// Get current registrations count - only if system is configured
$current_registrations = 0;
$total_credits = 0;

if ($system_configured) {
    $query = "SELECT COUNT(*) as count FROM registration r 
              JOIN sections sec ON r.section_id = sec.section_id 
              WHERE r.student_id = ? AND r.status = 'registered' 
              AND sec.semester = ? AND sec.year = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('isi', $student_id, $current_semester_info['current_semester'], $current_semester_info['current_year']);
    $stmt->execute();
    $current_registrations = $stmt->get_result()->fetch_assoc()['count'];

    // Get total credits for current semester
    $query = "SELECT SUM(c.credits) as total_credits FROM registration r 
              JOIN sections sec ON r.section_id = sec.section_id 
              JOIN courses c ON sec.course_id = c.course_id
              WHERE r.student_id = ? AND r.status = 'registered' 
              AND sec.semester = ? AND sec.year = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('isi', $student_id, $current_semester_info['current_semester'], $current_semester_info['current_year']);
    $stmt->execute();
    $total_credits = $stmt->get_result()->fetch_assoc()['total_credits'] ?? 0;
}

// Get recent registrations
$query = "SELECT c.course_id, c.title, sec.schedule_days, sec.schedule_time, 
          f.name as faculty_name, r.registration_date
          FROM registration r 
          JOIN sections sec ON r.section_id = sec.section_id 
          JOIN courses c ON sec.course_id = c.course_id
          JOIN faculty f ON sec.faculty_id = f.faculty_id
          WHERE r.student_id = ? AND r.status = 'registered'
          ORDER BY r.registration_date DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$recent_registrations = $stmt->get_result();

$page_title = 'Student Dashboard';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <?php if ($system_configured): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <span class="badge bg-info fs-6">
                <?php echo $current_semester_info['current_semester'] . ' ' . $current_semester_info['current_year']; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$system_configured): ?>
<div class="alert alert-warning alert-custom alert-permanent">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>System Not Configured:</strong> The academic semester has not been set up yet. Please contact the administrator to configure the current semester and year.
</div>
<?php else: ?>
    <?php if (!$current_semester_info['registration_open']): ?>
    <div class="alert alert-warning alert-custom">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Registration Closed:</strong> Course registration is currently closed for this semester.
    </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Student Info Card -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-user me-2"></i>Student Information
                </h5>
                <div class="row">
                    <div class="col-md-3">
                        <strong>Name:</strong><br>
                        <?php echo sanitizeInput($student_info['name']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Email:</strong><br>
                        <?php echo sanitizeInput($student_info['email']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Department:</strong><br>
                        <?php echo $student_info['department_name'] ?? 'Not assigned'; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Admission Year:</strong><br>
                        <?php echo $student_info['admission_year']; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Current Courses
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $current_registrations; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Credits
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $total_credits; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-credit-card fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2 dashboard-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Registration Status
                                        </div>
                                        <div class="h6 mb-0 font-weight-bold text-gray-800">
                                            <?php 
                                            if (!$system_configured) {
                                                echo 'Not Configured';
                                            } else {
                                                echo $current_semester_info['registration_open'] ? 'Open' : 'Closed';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-<?php 
                                        if (!$system_configured) {
                                            echo 'exclamation-triangle';
                                        } else {
                                            echo $current_semester_info['registration_open'] ? 'check-circle' : 'times-circle';
                                        }
                                        ?> fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Status
                        </div>
                        <div class="h6 mb-0 font-weight-bold text-gray-800">
                            <?php echo ucfirst($student_info['status']); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <a href="courses.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-search me-2"></i>Browse Courses
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="schedule.php" class="btn btn-outline-success w-100">
                            <i class="fas fa-calendar me-2"></i>View Schedule
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="schedule.php#grades" class="btn btn-outline-info w-100">
                            <i class="fas fa-chart-line me-2"></i>View Grades
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Registrations -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Recent Registrations
                </h5>
            </div>
            <div class="card-body">
                <?php if ($recent_registrations->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Title</th>
                                    <th>Schedule</th>
                                    <th>Faculty</th>
                                    <th>Registration Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($registration = $recent_registrations->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo sanitizeInput($registration['course_id']); ?></strong></td>
                                    <td><?php echo sanitizeInput($registration['title']); ?></td>
                                    <td>
                                        <span class="schedule-time">
                                            <?php echo formatSchedule($registration['schedule_days'], $registration['schedule_time']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo sanitizeInput($registration['faculty_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($registration['registration_date'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No registrations found. Start by browsing available courses.</p>
                        <a href="courses.php" class="btn btn-primary">Browse Courses</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
