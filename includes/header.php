<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>EWU Course Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .dashboard-card {
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-2px);
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .main-content {
            min-height: calc(100vh - 56px);
        }
        .schedule-time {
            font-size: 0.9em;
            color: #6c757d;
        }
        .alert-custom {
            border: none;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-graduation-cap me-2"></i>EWU CRFMS
            </a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>
                        <?php echo sanitizeInput($_SESSION['user_name']); ?>
                        <span class="badge bg-light text-primary ms-1">
                            <?php echo ucfirst($_SESSION['user_type']); ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php if (isset($_SESSION['user_id'])): ?>
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <?php if ($_SESSION['user_type'] === 'student'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>student/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>student/courses.php">
                                    <i class="fas fa-book me-2"></i>Available Courses
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>student/schedule.php">
                                    <i class="fas fa-calendar me-2"></i>My Schedule
                                </a>
                            </li>
                        <?php elseif ($_SESSION['user_type'] === 'faculty'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>faculty/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sections.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>faculty/sections.php">
                                    <i class="fas fa-chalkboard-teacher me-2"></i>My Sections
                                </a>
                            </li>
                        <?php elseif ($_SESSION['user_type'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'departments.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>admin/departments.php">
                                    <i class="fas fa-building me-2"></i>Departments
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>admin/courses.php">
                                    <i class="fas fa-book me-2"></i>Courses
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'faculty.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>admin/faculty.php">
                                    <i class="fas fa-chalkboard-teacher me-2"></i>Faculty
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>admin/students.php">
                                    <i class="fas fa-user-graduate me-2"></i>Students
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sections.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>admin/sections.php">
                                    <i class="fas fa-layer-group me-2"></i>Sections
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>admin/admins.php">
                                    <i class="fas fa-user-shield me-2"></i>Admins
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'config.php' ? 'active' : ''; ?>" 
                                   href="<?php echo BASE_URL; ?>admin/config.php">
                                    <i class="fas fa-cog me-2"></i>System Config
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="pt-3">
            <?php else: ?>
            <main class="container mt-4">
            <?php endif; ?>
