<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';
require_once '../includes/admin_dependencies.php';

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

<!-- System Setup Progress -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>System Setup Progress</h5>
                <?php if (!$system_configured): ?>
                <a href="config.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-cog me-1"></i>System Config
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php
                // Get setup progress
                $setup_items = [
                    'system_config' => [
                        'name' => 'System Config',
                        'count' => $system_configured ? 1 : 0,
                        'required' => true,
                        'url' => 'config.php',
                        'icon' => 'fas fa-cog'
                    ],
                    'departments' => [
                        'name' => 'Departments',
                        'count' => $stats['departments'],
                        'required' => true,
                        'url' => 'departments.php',
                        'icon' => 'fas fa-building'
                    ],
                    'programs' => [
                        'name' => 'Programs',
                        'count' => 0,
                        'required' => false,
                        'url' => 'programs.php',
                        'icon' => 'fas fa-graduation-cap',
                        'depends_on' => 'departments'
                    ],
                    'rooms' => [
                        'name' => 'Rooms',
                        'count' => 0,
                        'required' => false,
                        'url' => 'rooms.php',
                        'icon' => 'fas fa-door-open'
                    ],
                    'faculty' => [
                        'name' => 'Faculty',
                        'count' => $stats['faculty'],
                        'required' => true,
                        'url' => 'faculty.php',
                        'icon' => 'fas fa-chalkboard-teacher',
                        'depends_on' => 'departments'
                    ],
                    'courses' => [
                        'name' => 'Courses',
                        'count' => $stats['courses'],
                        'required' => true,
                        'url' => 'courses.php',
                        'icon' => 'fas fa-book',
                        'depends_on' => 'departments'
                    ],
                    'students' => [
                        'name' => 'Students',
                        'count' => $stats['students'],
                        'required' => true,
                        'url' => 'students.php',
                        'icon' => 'fas fa-user-graduate',
                        'depends_on' => 'departments'
                    ],
                    'sections' => [
                        'name' => 'Sections',
                        'count' => $stats['sections'],
                        'required' => true,
                        'url' => 'sections.php',
                        'icon' => 'fas fa-users',
                        'depends_on' => ['courses', 'faculty', 'rooms']
                    ]
                ];
                
                // Get additional counts
                $programs_count = $db->query("SELECT COUNT(*) as count FROM programs")->fetch_assoc()['count'];
                $rooms_count = $db->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
                $setup_items['programs']['count'] = $programs_count;
                $setup_items['rooms']['count'] = $rooms_count;
                
                $total_required = 0;
                $completed_required = 0;
                ?>
                
                <div class="row">
                    <?php foreach ($setup_items as $key => $item): 
                        $is_complete = $item['count'] > 0;
                        $has_dependencies = isset($item['depends_on']);
                        $dependencies_met = true;
                        
                        if ($has_dependencies) {
                            $deps = is_array($item['depends_on']) ? $item['depends_on'] : [$item['depends_on']];
                            foreach ($deps as $dep) {
                                if ($setup_items[$dep]['count'] == 0) {
                                    $dependencies_met = false;
                                    break;
                                }
                            }
                        }
                        
                        if ($item['required']) {
                            $total_required++;
                            if ($is_complete) $completed_required++;
                        }
                        
                        $status_class = $is_complete ? 'text-success' : ($dependencies_met ? 'text-warning' : 'text-muted');
                        $icon_class = $is_complete ? 'fas fa-check-circle text-success' : ($dependencies_met ? 'fas fa-exclamation-circle text-warning' : 'fas fa-times-circle text-muted');
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                        <div class="d-flex align-items-center p-2 border rounded">
                            <div class="me-3">
                                <i class="<?php echo $item['icon']; ?> fa-2x <?php echo $status_class; ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo $item['name']; ?></h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-secondary"><?php echo $item['count']; ?> items</span>
                                    <i class="<?php echo $icon_class; ?>"></i>
                                </div>
                                <?php if (!$dependencies_met): ?>
                                    <small class="text-muted">Requires: <?php echo is_array($item['depends_on']) ? implode(', ', $item['depends_on']) : $item['depends_on']; ?></small>
                                <?php elseif (!$is_complete): ?>
                                    <a href="<?php echo $item['url']; ?>" class="btn btn-sm btn-outline-primary mt-1">Setup Now</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-3">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $total_required > 0 ? ($completed_required / $total_required) * 100 : 0; ?>%" 
                             aria-valuenow="<?php echo $completed_required; ?>" aria-valuemin="0" aria-valuemax="<?php echo $total_required; ?>">
                            <?php echo $completed_required; ?>/<?php echo $total_required; ?> Required Components
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
