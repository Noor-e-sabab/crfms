<?php
// Admin dependency checking functions

function checkSystemDependencies($db) {
    $dependencies = [];
    
    // Check basic system setup
    $departments_count = $db->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
    $programs_count = $db->query("SELECT COUNT(*) as count FROM programs")->fetch_assoc()['count'];
    $rooms_count = $db->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
    $faculty_count = $db->query("SELECT COUNT(*) as count FROM faculty")->fetch_assoc()['count'];
    $courses_count = $db->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
    $students_count = $db->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
    $config_count = $db->query("SELECT COUNT(*) as count FROM config")->fetch_assoc()['count'];
    
    // System configuration check
    if ($config_count == 0) {
        $dependencies[] = [
            'type' => 'critical',
            'title' => 'System Configuration Missing',
            'message' => 'System semester configuration is required before any operations.',
            'action' => 'Go to System Config',
            'url' => 'config.php',
            'icon' => 'fas fa-cog'
        ];
    }
    
    // Departments check
    if ($departments_count == 0) {
        $dependencies[] = [
            'type' => 'critical',
            'title' => 'No Departments Found',
            'message' => 'Departments are required before adding programs, courses, faculty, or students.',
            'action' => 'Add Departments',
            'url' => 'departments.php',
            'icon' => 'fas fa-building'
        ];
    }
    
    // Programs check (depends on departments)
    if ($departments_count > 0 && $programs_count == 0) {
        $dependencies[] = [
            'type' => 'warning',
            'title' => 'No Programs Found',
            'message' => 'Programs are required to organize courses and assign students properly.',
            'action' => 'Add Programs',
            'url' => 'programs.php',
            'icon' => 'fas fa-graduation-cap'
        ];
    }
    
    // Rooms check
    if ($rooms_count == 0) {
        $dependencies[] = [
            'type' => 'warning',
            'title' => 'No Rooms Found',
            'message' => 'Rooms are required before creating sections.',
            'action' => 'Add Rooms',
            'url' => 'rooms.php',
            'icon' => 'fas fa-door-open'
        ];
    }
    
    // Faculty check (depends on departments)
    if ($departments_count > 0 && $faculty_count == 0) {
        $dependencies[] = [
            'type' => 'warning',
            'title' => 'No Faculty Found',
            'message' => 'Faculty members are required before creating sections.',
            'action' => 'Add Faculty',
            'url' => 'faculty.php',
            'icon' => 'fas fa-chalkboard-teacher'
        ];
    }
    
    // Courses check (depends on departments and ideally programs)
    if ($departments_count > 0 && $courses_count == 0) {
        $dependencies[] = [
            'type' => 'warning',
            'title' => 'No Courses Found',
            'message' => 'Courses are required before creating sections.',
            'action' => 'Add Courses',
            'url' => 'courses.php',
            'icon' => 'fas fa-book'
        ];
    }
    
    return $dependencies;
}

function checkPageSpecificDependencies($db, $page) {
    $dependencies = [];
    
    switch ($page) {
        case 'programs':
            $departments_count = $db->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
            if ($departments_count == 0) {
                $dependencies[] = [
                    'type' => 'critical',
                    'title' => 'Departments Required',
                    'message' => 'You must create departments before adding programs.',
                    'action' => 'Add Departments First',
                    'url' => 'departments.php',
                    'icon' => 'fas fa-building'
                ];
            }
            break;
            
        case 'courses':
            $departments_count = $db->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
            $programs_count = $db->query("SELECT COUNT(*) as count FROM programs")->fetch_assoc()['count'];
            
            if ($departments_count == 0) {
                $dependencies[] = [
                    'type' => 'critical',
                    'title' => 'Departments Required',
                    'message' => 'Departments are required for course auto-detection and organization.',
                    'action' => 'Add Departments First',
                    'url' => 'departments.php',
                    'icon' => 'fas fa-building'
                ];
            }
            
            // Always show program recommendation if none exist
            if ($programs_count == 0) {
                $dependencies[] = [
                    'type' => 'warning',
                    'title' => 'Programs Recommended',
                    'message' => 'Programs help organize courses better and restrict student access.',
                    'action' => 'Add Programs',
                    'url' => 'programs.php',
                    'icon' => 'fas fa-graduation-cap'
                ];
            }
            break;
            
        case 'sections':
            $courses_count = $db->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
            $faculty_count = $db->query("SELECT COUNT(*) as count FROM faculty")->fetch_assoc()['count'];
            $rooms_count = $db->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
            
            if ($courses_count == 0) {
                $dependencies[] = [
                    'type' => 'critical',
                    'title' => 'Courses Required',
                    'message' => 'You must create courses before adding sections.',
                    'action' => 'Add Courses First',
                    'url' => 'courses.php',
                    'icon' => 'fas fa-book'
                ];
            }
            
            if ($faculty_count == 0) {
                $dependencies[] = [
                    'type' => 'critical',
                    'title' => 'Faculty Required',
                    'message' => 'You must add faculty members before creating sections.',
                    'action' => 'Add Faculty First',
                    'url' => 'faculty.php',
                    'icon' => 'fas fa-chalkboard-teacher'
                ];
            }
            
            if ($rooms_count == 0) {
                $dependencies[] = [
                    'type' => 'critical',
                    'title' => 'Rooms Required',
                    'message' => 'You must create rooms before adding sections.',
                    'action' => 'Add Rooms First',
                    'url' => 'rooms.php',
                    'icon' => 'fas fa-door-open'
                ];
            }
            break;
            
        case 'students':
            $departments_count = $db->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
            $programs_count = $db->query("SELECT COUNT(*) as count FROM programs")->fetch_assoc()['count'];
            
            if ($departments_count == 0) {
                $dependencies[] = [
                    'type' => 'critical',
                    'title' => 'Departments Required',
                    'message' => 'Departments are required before adding students.',
                    'action' => 'Add Departments First',
                    'url' => 'departments.php',
                    'icon' => 'fas fa-building'
                ];
            }
            
            if ($departments_count > 0 && $programs_count == 0) {
                $dependencies[] = [
                    'type' => 'warning',
                    'title' => 'Programs Recommended',
                    'message' => 'Programs help organize students and restrict course access properly.',
                    'action' => 'Add Programs',
                    'url' => 'programs.php',
                    'icon' => 'fas fa-graduation-cap'
                ];
            }
            break;
            
        case 'faculty':
            $departments_count = $db->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
            if ($departments_count == 0) {
                $dependencies[] = [
                    'type' => 'critical',
                    'title' => 'Departments Required',
                    'message' => 'You must create departments before adding faculty members.',
                    'action' => 'Add Departments First',
                    'url' => 'departments.php',
                    'icon' => 'fas fa-building'
                ];
            }
            break;
    }
    
    return $dependencies;
}

function renderDependencyWarnings($dependencies, $show_setup_guide = true) {
    if (empty($dependencies)) {
        return '';
    }
    
    $html = '';
    
    foreach ($dependencies as $dep) {
        $alert_class = $dep['type'] === 'critical' ? 'alert-danger' : 'alert-warning';
        $icon_class = $dep['type'] === 'critical' ? 'text-danger' : 'text-warning';
        
        // Completely static alerts - no Bootstrap dismissible functionality
        $html .= '<div class="alert ' . $alert_class . ' alert-permanent mb-3 border-0 shadow-sm" role="alert">';
        $html .= '<div class="d-flex align-items-start">';
        $html .= '<i class="' . $dep['icon'] . ' ' . $icon_class . ' me-3 mt-1 fs-5"></i>';
        $html .= '<div class="flex-grow-1">';
        $html .= '<h6 class="alert-heading mb-2 fw-bold">' . $dep['title'] . '</h6>';
        $html .= '<p class="mb-2">' . $dep['message'] . '</p>';
        
        if ($show_setup_guide) {
            $html .= '<div class="mt-2">';
            $html .= '<a href="' . $dep['url'] . '" class="btn btn-sm btn-outline-primary">';
            $html .= '<i class="fas fa-arrow-right me-1"></i>' . $dep['action'];
            $html .= '</a>';
            $html .= '</div>';
        }
        
        $html .= '</div></div></div>';
    }
    
    return $html;
}

function hasRequiredDependencies($db, $page) {
    $dependencies = checkPageSpecificDependencies($db, $page);
    
    // Check if there are any critical dependencies
    foreach ($dependencies as $dep) {
        if ($dep['type'] === 'critical') {
            return false;
        }
    }
    
    return true;
}
?>
