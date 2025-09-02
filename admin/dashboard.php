<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

checkUserType('admin');

$current_semester_info = getCurrentSemester($db);

// Check if system is configured
if (!$current_semester_info['current_semester'] || !$current_semester_info['current_year']) {
    $system_configured = false;
} else {
    $system_configured = true;
}

// Get system statistics
$stats = [];

// Total counts
$queries = [
    'students' => "SELECT COUNT(*) as count FROM students WHERE status = 'active'",
    'faculty' => "SELECT COUNT(*) as count FROM faculty",
    'departments' => "SELECT COUNT(*) as count FROM departments",
    'courses' => "SELECT COUNT(*) as count FROM courses",
    'sections' => $system_configured ? "SELECT COUNT(*) as count FROM sections WHERE semester = ? AND year = ?" : "SELECT COUNT(*) as count FROM sections",
    'registrations' => $system_configured ? "SELECT COUNT(*) as count FROM registration r JOIN sections s ON r.section_id = s.section_id WHERE r.status = 'registered' AND s.semester = ? AND s.year = ?" : "SELECT COUNT(*) as count FROM registration WHERE status = 'registered'"
];

foreach ($queries as $key => $query) {
    if (in_array($key, ['sections', 'registrations']) && $system_configured) {
        $stmt = $db->prepare($query);
        $stmt->bind_param('si', $current_semester_info['current_semester'], $current_semester_info['current_year']);
        $stmt->execute();
        $stats[$key] = $stmt->get_result()->fetch_assoc()['count'];
    } else {
        $result = $db->query($query);
        $stats[$key] = $result->fetch_assoc()['count'];
    }
}

// Recent activities
$recent_registrations_query = "SELECT s.name as student_name, c.course_id, c.title, r.registration_date
                               FROM registration r 
                               JOIN students s ON r.student_id = s.student_id
                               JOIN sections sec ON r.section_id = sec.section_id
                               JOIN courses c ON sec.course_id = c.course_id
                               WHERE r.status = 'registered'
                               ORDER BY r.registration_date DESC LIMIT 5";
$recent_registrations = $db->query($recent_registrations_query);

$page_title = 'Admin Dashboard';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Admin Dashboard</h1>
    <?php if ($system_configured): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <span class="badge bg-info fs-6">
                <?php echo $current_semester_info['current_semester'] . ' ' . $current_semester_info['current_year']; ?>
            </span>
            <?php if ($current_semester_info['registration_open']): ?>
                <span class="badge bg-success fs-6 ms-2">Registration Open</span>
            <?php else: ?>
                <span class="badge bg-danger fs-6 ms-2">Registration Closed</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$system_configured): ?>
<div class="alert alert-danger alert-custom alert-permanent">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>System Setup Required:</strong> Please configure the system first.
    <div class="mt-2">
        <strong>Quick Setup Steps:</strong>
        <ol class="mb-0 mt-1">
            <li>Go to <a href="config.php" class="alert-link">System Config</a> and set current semester/year</li>
            <li>Add departments in <a href="departments.php" class="alert-link">Departments</a></li>
            <li>Add courses in <a href="courses.php" class="alert-link">Courses</a></li>
            <li>Add faculty and students</li>
            <li>Create course sections for the semester</li>
        </ol>
    </div>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Students</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['students']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Faculty</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['faculty']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Departments</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['departments']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-building fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Courses</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['courses']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Sections</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['sections']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
        <div class="card border-left-dark shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">Registrations</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['registrations']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="students.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-user-graduate me-2"></i>Manage Students
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="faculty.php" class="btn btn-outline-success w-100">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Manage Faculty
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="courses.php" class="btn btn-outline-info w-100">
                            <i class="fas fa-book me-2"></i>Manage Courses
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="sections.php" class="btn btn-outline-warning w-100">
                            <i class="fas fa-layer-group me-2"></i>Manage Sections
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="departments.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-building me-2"></i>Departments
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="config.php" class="btn btn-outline-dark w-100">
                            <i class="fas fa-cog me-2"></i>System Config
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
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
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Title</th>
                                    <th>Registration Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($registration = $recent_registrations->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo sanitizeInput($registration['student_name']); ?></td>
                                    <td><strong><?php echo sanitizeInput($registration['course_id']); ?></strong></td>
                                    <td><?php echo sanitizeInput($registration['title']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($registration['registration_date'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No recent registrations found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
