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

// Get faculty information
$query = "SELECT f.*, d.name as department_name FROM faculty f 
          LEFT JOIN departments d ON f.department_id = d.department_id 
          WHERE f.faculty_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $faculty_id);
$stmt->execute();
$faculty_info = $stmt->get_result()->fetch_assoc();

// Get current semester sections count - only if system is configured
$current_sections = 0;
$total_students = 0;

if ($system_configured) {
    $query = "SELECT COUNT(*) as count FROM sections 
              WHERE faculty_id = ? AND semester = ? AND year = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('isi', $faculty_id, $current_semester_info['current_semester'], $current_semester_info['current_year']);
    $stmt->execute();
    $current_sections = $stmt->get_result()->fetch_assoc()['count'];

    // Get total students across all current sections
    $query = "SELECT COUNT(DISTINCT r.student_id) as count FROM registration r 
              JOIN sections sec ON r.section_id = sec.section_id 
              WHERE sec.faculty_id = ? AND r.status = 'registered' 
              AND sec.semester = ? AND sec.year = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('isi', $faculty_id, $current_semester_info['current_semester'], $current_semester_info['current_year']);
    $stmt->execute();
    $total_students = $stmt->get_result()->fetch_assoc()['count'];
}

// Get recent sections
$query = "SELECT sec.*, c.title, c.course_id, r.room_number as room, r.building,
          (SELECT COUNT(*) FROM registration WHERE section_id = sec.section_id AND status = 'registered') as enrolled
          FROM sections sec 
          JOIN courses c ON sec.course_id = c.course_id
          LEFT JOIN rooms r ON sec.room_id = r.room_id
          WHERE sec.faculty_id = ?
          ORDER BY sec.year DESC, sec.semester DESC, sec.section_id DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $faculty_id);
$stmt->execute();
$recent_sections = $stmt->get_result();

$page_title = 'Faculty Dashboard';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Faculty Dashboard</h1>
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
<?php endif; ?>

<!-- Faculty Info Card -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Faculty Information
                </h5>
                <div class="row">
                    <div class="col-md-3">
                        <strong>Name:</strong><br>
                        <?php echo sanitizeInput($faculty_info['name']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Email:</strong><br>
                        <?php echo sanitizeInput($faculty_info['email']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Department:</strong><br>
                        <?php echo $faculty_info['department_name'] ?? 'Not assigned'; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Designation:</strong><br>
                        <?php echo sanitizeInput($faculty_info['designation']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Current Sections
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $current_sections; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Students
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $total_students; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Department
                        </div>
                        <div class="h6 mb-0 font-weight-bold text-gray-800">
                            <?php echo $faculty_info['department_name'] ?? 'Not assigned'; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-building fa-2x text-gray-300"></i>
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
                    <div class="col-md-6 mb-3">
                        <a href="sections.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-layer-group me-2"></i>View My Sections
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="sections.php#grading" class="btn btn-outline-success w-100">
                            <i class="fas fa-clipboard-list me-2"></i>Grade Students
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Sections -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Recent Sections
                </h5>
            </div>
            <div class="card-body">
                <?php if ($recent_sections->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Semester</th>
                                    <th>Course</th>
                                    <th>Title</th>
                                    <th>Schedule</th>
                                    <th>Room</th>
                                    <th>Enrolled</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($section = $recent_sections->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $section['semester'] . ' ' . $section['year']; ?></td>
                                    <td><strong><?php echo sanitizeInput($section['course_id']); ?></strong></td>
                                    <td><?php echo sanitizeInput($section['title']); ?></td>
                                    <td>
                                        <span class="schedule-time">
                                            <?php echo formatSchedule($section['schedule_days'], $section['schedule_time']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($section['room'])): ?>
                                            <?php echo sanitizeInput($section['room']); ?>
                                            <?php if (!empty($section['building'])): ?>
                                                <small class="text-muted d-block"><?php echo sanitizeInput($section['building']); ?></small>
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
                                        <a href="sections.php?section_id=<?php echo $section['section_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chalkboard fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No sections assigned yet.</p>
                        <small class="text-muted">Contact the administrator to get sections assigned.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
