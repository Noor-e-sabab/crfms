<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';
require_once '../includes/admin_dependencies.php';
require_once '../includes/schedule_generator.php';
require_once '../includes/validation_helper.php';

checkUserType('admin');

$message = '';
$error_message = '';
$current_semester_info = getCurrentSemester($db);

// Simple POST debug
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = "POST request received! Action: " . ($_POST['action'] ?? 'none');
}

// Check dependencies
$dependencies = checkPageSpecificDependencies($db, 'sections');
$can_add_sections = hasRequiredDependencies($db, 'sections');

// Debug dependency checking
error_log("Dependencies: " . print_r($dependencies, true));
error_log("Can add sections: " . ($can_add_sections ? 'true' : 'false'));

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    error_log("POST data received: " . print_r($_POST, true));
    
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'];
        error_log("Action: " . $action);
        
        if ($action === 'add') {
            // Check dependencies before processing
            if (!$can_add_sections) {
                $error_message = 'Cannot add sections. Please complete the required setup first (courses, faculty, and rooms are needed).';
            } else {
                $course_id = sanitizeInput($_POST['course_id']);
                $faculty_id = (int)$_POST['faculty_id'];
                $semester = sanitizeInput($_POST['semester']);
                $year = (int)$_POST['year'];
                $section_type = sanitizeInput($_POST['section_type']);
                $parent_section_id = !empty($_POST['parent_section_id']) ? (int)$_POST['parent_section_id'] : null;
                $schedule_days = sanitizeInput($_POST['schedule_days']);
                $schedule_time = sanitizeInput($_POST['schedule_time']);
                $room_id = (int)$_POST['room_id'];
                $capacity = (int)$_POST['capacity'];
                
                error_log("Form data - Course: $course_id, Faculty: $faculty_id, Semester: $semester, Year: $year");
                
                // Auto-generate section number
                $section_number = generateSectionNumber($db, $course_id, $section_type, $semester, $year);
                error_log("Generated section number: " . $section_number);
                
                // Validation with lab-specific checks
                $validation_errors = [];
                
                if (empty($course_id)) $validation_errors[] = 'Course ID is required';
                if ($faculty_id <= 0) $validation_errors[] = 'Faculty is required';
                if (empty($semester)) $validation_errors[] = 'Semester is required';
                if ($year < 2020) $validation_errors[] = 'Valid year is required';
                if (empty($section_type)) $validation_errors[] = 'Section type is required';
                if (empty($schedule_days)) $validation_errors[] = 'Schedule days are required';
                if (empty($schedule_time)) $validation_errors[] = 'Schedule time is required';
                if ($room_id <= 0) $validation_errors[] = 'Room is required';
                if ($capacity <= 0) $validation_errors[] = 'Capacity is required';
                
                // Special validation for lab sections
                if ($section_type === 'lab' && (!$parent_section_id || $parent_section_id <= 0)) {
                    $validation_errors[] = 'Lab sections must be paired with a theory section';
                }
                
                if (!empty($validation_errors)) {
                    $error_message = 'Validation failed: ' . implode(', ', $validation_errors);
                    error_log("Validation failed: " . implode(', ', $validation_errors));
                } else {
                    // Simple insertion without complex validation for now
                    $query = "INSERT INTO sections (course_id, section_number, faculty_id, semester, year, section_type, parent_section_id, schedule_days, schedule_time, room_id, capacity) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    error_log("SQL Query: " . $query);
                    
                    $stmt = $db->prepare($query);
                    if (!$stmt) {
                        $error_message = 'Database prepare error: ' . $db->error;
                        error_log("Prepare failed: " . $db->error);
                    } else {
                        $stmt->bind_param('ssisssissii', $course_id, $section_number, $faculty_id, $semester, $year, $section_type, $parent_section_id, $schedule_days, $schedule_time, $room_id, $capacity);
                        
                        if ($stmt->execute()) {
                            $success_msg = "Section {$section_number} added successfully!";
                            if ($section_type === 'lab') {
                                $success_msg .= " (Paired with theory section)";
                            }
                            $message = $success_msg;
                            error_log("Section added successfully: " . $success_msg);
                        } else {
                            $error_message = 'Failed to add section: ' . $stmt->error;
                            error_log("Execute failed: " . $stmt->error);
                        }
                    }
                }
            }
        } elseif ($action === 'delete') {
            $section_id = (int)$_POST['section_id'];
            
            // Check if section has any registrations
            $reg_query = "SELECT COUNT(*) as count FROM registration WHERE section_id = ?";
            $stmt = $db->prepare($reg_query);
            $stmt->bind_param('i', $section_id);
            $stmt->execute();
            $reg_count = $stmt->get_result()->fetch_assoc()['count'];
            
            if ($reg_count > 0) {
                $error_message = 'Cannot delete section. Students are registered for this section.';
            } else {
                $query = "DELETE FROM sections WHERE section_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param('i', $section_id);
                
                if ($stmt->execute()) {
                    $message = 'Section deleted successfully!';
                } else {
                    $error_message = 'Failed to delete section. Please try again.';
                }
            }
        }
    }
}

// Get sections with course, faculty, and enrollment info
$query = "SELECT s.*, c.title as course_title, c.total_credits as credits, f.name as faculty_name, d.name as department_name,
          r.room_number, r.building, r.room_type,
          parent.section_number as parent_section_number,
          (SELECT COUNT(*) FROM registration WHERE section_id = s.section_id AND status = 'registered') as enrolled
          FROM sections s 
          JOIN courses c ON s.course_id = c.course_id 
          JOIN faculty f ON s.faculty_id = f.faculty_id
          LEFT JOIN departments d ON c.department_id = d.department_id
          LEFT JOIN rooms r ON s.room_id = r.room_id
          LEFT JOIN sections parent ON s.parent_section_id = parent.section_id
          ORDER BY s.year DESC, s.semester DESC, s.course_id, s.section_number";
$sections = $db->query($query);

// Check if query was successful
if (!$sections) {
    $error_message = "Database error: " . $db->error;
    $sections = false;
}

// Get courses for dropdown
$courses_query = "SELECT course_id, title, has_lab, theory_credits, lab_credits FROM courses ORDER BY course_id";
$courses = $db->query($courses_query);

// Get faculty for dropdown
$faculty_query = "SELECT faculty_id, name FROM faculty ORDER BY name";
$faculty = $db->query($faculty_query);

// Get rooms for dropdown with room type grouping
$rooms_query = "SELECT room_id, room_number, building, capacity, room_type FROM rooms ORDER BY room_type, room_number";
$rooms = $db->query($rooms_query);

$page_title = 'Manage Sections';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Sections</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($can_add_sections): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
            <i class="fas fa-plus me-2"></i>Add Section
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($can_add_sections): ?>
    <!-- Main Section Management Interface -->
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

    <div class="card">
<?php else: ?>
    <!-- Setup Guide - Show when dependencies not satisfied -->
    <?php echo renderDependencyWarnings($dependencies); ?>
<?php endif; ?>

<?php if ($can_add_sections): ?>
    <div class="card-body">
        <?php if ($sections && $sections->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Course</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Faculty</th>
                            <th>Semester</th>
                            <th>Schedule</th>
                            <th>Room</th>
                            <th>Capacity</th>
                            <th>Enrolled</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($section = $sections->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo $section['course_id'] . '-' . $section['section_number']; ?></strong>
                                <small class="text-muted d-block">ID: <?php echo $section['section_id']; ?></small>
                            </td>
                            <td>
                                <strong><?php echo $section['course_id']; ?></strong>
                                <small class="text-muted d-block"><?php echo $section['credits']; ?> credits</small>
                            </td>
                            <td><?php echo sanitizeInput($section['course_title']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $section['section_type'] === 'lab' ? 'info' : 'secondary'; ?>">
                                    <?php echo ucfirst($section['section_type']); ?>
                                </span>
                                <?php if ($section['section_type'] === 'lab' && $section['parent_section_number']): ?>
                                    <small class="text-muted d-block">
                                        Paired with: <?php echo $section['parent_section_number']; ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo sanitizeInput($section['faculty_name']); ?></td>
                            <td><?php echo $section['semester'] . ' ' . $section['year']; ?></td>
                            <td>
                                <strong><?php echo formatDaysDisplay($section['schedule_days']); ?></strong>
                                <small class="text-muted d-block"><?php echo formatTimeDisplay($section['schedule_time']); ?></small>
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
                            <td><?php echo $section['capacity']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $section['enrolled'] >= $section['capacity'] ? 'danger' : 'primary'; ?>">
                                    <?php echo $section['enrolled']; ?>/<?php echo $section['capacity']; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this section?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="section_id" value="<?php echo $section['section_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No sections found</h5>
                <p class="text-muted">Click "Add Section" to create your first section.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                    <i class="fas fa-plus me-2"></i>Add Section
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Section Modal -->
<div class="modal fade" id="addSectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate id="addSectionForm">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="course_id" class="form-label">Course</label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php if ($courses): ?>
                                        <?php while ($course = $courses->fetch_assoc()): ?>
                                            <option value="<?php echo $course['course_id']; ?>" 
                                                    data-has-lab="<?php echo $course['has_lab']; ?>"
                                                    data-theory-credits="<?php echo $course['theory_credits']; ?>"
                                                    data-lab-credits="<?php echo $course['lab_credits']; ?>">
                                                <?php echo $course['course_id'] . ' - ' . sanitizeInput($course['title']); ?>
                                                <?php if ($course['has_lab']): ?>
                                                    <span class="text-muted">(Has Lab)</span>
                                                <?php endif; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="invalid-feedback">Please select a course.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="section_type" class="form-label">Section Type</label>
                                <select class="form-select" id="section_type" name="section_type" required>
                                    <option value="">Select Type</option>
                                    <option value="theory">Theory</option>
                                    <option value="lab">Lab</option>
                                </select>
                                <div class="invalid-feedback">Please select section type.</div>
                            </div>
                        </div>
                        <div class="col-md-4" id="parent_section_div" style="display: none;">
                            <div class="mb-3">
                                <label for="parent_section_id" class="form-label">Theory Section to Pair</label>
                                <select class="form-select" id="parent_section_id" name="parent_section_id">
                                    <option value="">Select Theory Section</option>
                                </select>
                                <div class="invalid-feedback">Please select theory section to pair with.</div>
                                <small class="form-text text-muted">Required for lab sections</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="faculty_id" class="form-label">Faculty</label>
                                <select class="form-select" id="faculty_id" name="faculty_id" required>
                                    <option value="">Select Faculty</option>
                                    <?php if ($faculty): ?>
                                        <?php while ($faculty_member = $faculty->fetch_assoc()): ?>
                                            <option value="<?php echo $faculty_member['faculty_id']; ?>">
                                                <?php echo sanitizeInput($faculty_member['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="invalid-feedback">Please select faculty.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="Spring">Spring</option>
                                    <option value="Summer">Summer</option>
                                    <option value="Fall">Fall</option>
                                </select>
                                <div class="invalid-feedback">Please select semester.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="year" class="form-label">Year</label>
                                <input type="number" class="form-control" id="year" name="year" min="2020" max="2030" 
                                       value="<?php echo date('Y'); ?>" required>
                                <div class="invalid-feedback">Please enter a valid year.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_days" class="form-label">Schedule Days</label>
                                <select class="form-select" id="schedule_days" name="schedule_days" required>
                                    <option value="">Select Days</option>
                                    <?php 
                                    $dayOptions = ScheduleGenerator::getDayOptionsGrouped();
                                    ?>
                                    <optgroup label="Single Days">
                                        <?php foreach ($dayOptions['single'] as $code => $name): ?>
                                            <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Day Combinations">
                                        <?php foreach ($dayOptions['pairs'] as $code => $name): ?>
                                            <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                                <div class="invalid-feedback">Please select schedule days.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_time" class="form-label">Schedule Time</label>
                                <select class="form-select" id="schedule_time" name="schedule_time" required>
                                    <option value="">Select Time Slot</option>
                                    <?php 
                                    $timeSlots = ScheduleGenerator::getAllTimeSlots();
                                    ?>
                                    <optgroup label="Theory Classes (1.5 hours)">
                                        <?php foreach ($timeSlots['theory'] as $slot): ?>
                                            <option value="<?php echo $slot; ?>"><?php echo $slot; ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Lab Classes (3 hours)">
                                        <?php foreach ($timeSlots['lab'] as $slot): ?>
                                            <option value="<?php echo $slot; ?>"><?php echo $slot; ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                                <div class="invalid-feedback">Please select schedule time.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="room_id" class="form-label">Room</label>
                                <select class="form-select" id="room_id" name="room_id" required>
                                    <option value="">Select Room</option>
                                    <?php if ($rooms): 
                                        $rooms_array = [];
                                        while ($room = $rooms->fetch_assoc()) {
                                            $rooms_array[] = $room;
                                        }
                                        
                                        // Group rooms by type
                                        $grouped_rooms = ['classroom' => [], 'lab' => [], 'both' => []];
                                        foreach ($rooms_array as $room) {
                                            $grouped_rooms[$room['room_type']][] = $room;
                                        }
                                        
                                        // Display rooms grouped by type
                                        foreach ($grouped_rooms as $type => $rooms_list):
                                            if (count($rooms_list) > 0):
                                    ?>
                                        <optgroup label="<?php echo ucfirst($type); ?> Rooms">
                                            <?php foreach ($rooms_list as $room): ?>
                                                <option value="<?php echo $room['room_id']; ?>" 
                                                        data-room-type="<?php echo $room['room_type']; ?>"
                                                        data-capacity="<?php echo $room['capacity']; ?>">
                                                    <?php echo $room['room_number']; ?>
                                                    <?php if ($room['building']): echo ' (' . $room['building'] . ')'; endif; ?>
                                                    - Cap: <?php echo $room['capacity']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php 
                                            endif;
                                        endforeach; 
                                    endif; ?>
                                </select>
                                <div class="invalid-feedback">Please select a room.</div>
                                <div class="form-text text-muted">
                                    <small><strong>Room Types:</strong> Classroom (theory only), Lab (lab only), Both (theory & lab)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="capacity" class="form-label">Section Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                                <div class="invalid-feedback">Please enter section capacity.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseSelect = document.getElementById('course_id');
    const sectionTypeSelect = document.getElementById('section_type');
    const parentSectionDiv = document.getElementById('parent_section_div');
    const parentSectionSelect = document.getElementById('parent_section_id');
    const scheduleTimeSelect = document.getElementById('schedule_time');
    const roomSelect = document.getElementById('room_id');
    const capacityInput = document.getElementById('capacity');
    
    // Handle section type change
    sectionTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        console.log('Section type changed to:', selectedType);
        
        if (selectedType === 'lab') {
            console.log('Lab section selected, showing parent section div');
            parentSectionDiv.style.display = 'block';
            parentSectionSelect.required = true;
            
            // Immediately try to load theory sections
            loadTheorySections();
        } else {
            console.log('Theory section selected, hiding parent section div');
            parentSectionDiv.style.display = 'none';
            parentSectionSelect.required = false;
            parentSectionSelect.innerHTML = '<option value="">Select Theory Section</option>';
        }
        
        updateRoomOptions();
        updateTimeSlots();
    });
    
    // Load theory sections for pairing
    function loadTheorySections() {
        const courseId = courseSelect.value;
        const semester = document.getElementById('semester').value;
        const year = document.getElementById('year').value;
        
        console.log('Loading theory sections for:', {courseId, semester, year});
        
        // Clear existing options
        parentSectionSelect.innerHTML = '<option value="">Select Theory Section</option>';
        
        if (!courseId || !semester || !year) {
            console.log('Missing required data for loading theory sections');
            const option = document.createElement('option');
            option.disabled = true;
            option.textContent = 'Please select course, semester, and year first';
            option.style.color = '#6c757d';
            parentSectionSelect.appendChild(option);
            return;
        }
        
        console.log('Making AJAX request to get_theory_sections.php');
        
        // Show loading indicator
        const loadingOption = document.createElement('option');
        loadingOption.disabled = true;
        loadingOption.textContent = 'Loading theory sections...';
        loadingOption.style.color = '#007bff';
        parentSectionSelect.appendChild(loadingOption);
        
        // Create AJAX request to get theory sections
        fetch('get_theory_sections.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `course_id=${encodeURIComponent(courseId)}&semester=${encodeURIComponent(semester)}&year=${encodeURIComponent(year)}`
        })
        .then(response => {
            console.log('AJAX response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('AJAX response data:', data);
            
            // Clear loading indicator
            parentSectionSelect.innerHTML = '<option value="">Select Theory Section</option>';
            
            if (data.success && data.sections && data.sections.length > 0) {
                console.log('Found', data.sections.length, 'theory sections');
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section.section_id;
                    option.textContent = `Section ${section.section_number} - ${section.faculty_name} (${section.schedule_days} ${section.schedule_time})`;
                    parentSectionSelect.appendChild(option);
                });
                
                // Add success message
                const successOption = document.createElement('option');
                successOption.disabled = true;
                successOption.textContent = `↑ Found ${data.sections.length} theory section(s) to pair with`;
                successOption.style.color = '#28a745';
                successOption.style.fontSize = '0.875em';
                parentSectionSelect.appendChild(successOption);
            } else {
                console.log('No theory sections found');
                const option = document.createElement('option');
                option.disabled = true;
                option.textContent = 'No theory sections available for pairing';
                option.style.color = '#dc3545';
                parentSectionSelect.appendChild(option);
                
                const hintOption = document.createElement('option');
                hintOption.disabled = true;
                hintOption.textContent = '→ Create a theory section first for this course';
                hintOption.style.color = '#6c757d';
                hintOption.style.fontSize = '0.875em';
                parentSectionSelect.appendChild(hintOption);
            }
        })
        .catch(error => {
            console.error('Error loading theory sections:', error);
            parentSectionSelect.innerHTML = '<option value="">Select Theory Section</option>';
            
            const errorOption = document.createElement('option');
            errorOption.disabled = true;
            errorOption.textContent = 'Error loading theory sections';
            errorOption.style.color = '#dc3545';
            parentSectionSelect.appendChild(errorOption);
            
            const retryOption = document.createElement('option');
            retryOption.disabled = true;
            retryOption.textContent = '→ Check browser console for details';
            retryOption.style.color = '#6c757d';
            retryOption.style.fontSize = '0.875em';
            parentSectionSelect.appendChild(retryOption);
        });
    }
    
    // Trigger theory section loading when course, semester, or year changes (but only if lab is selected)
    function reloadTheorySectionsIfNeeded() {
        if (sectionTypeSelect.value === 'lab') {
            console.log('Lab section selected, reloading theory sections...');
            loadTheorySections();
        }
    }
    
    courseSelect.addEventListener('change', reloadTheorySectionsIfNeeded);
    document.getElementById('semester').addEventListener('change', reloadTheorySectionsIfNeeded);
    document.getElementById('year').addEventListener('change', reloadTheorySectionsIfNeeded);
    
    // Store original options for restoration
    const originalTimeSlots = {
        theory: [],
        lab: []
    };
    
    const originalRooms = {
        classroom: [],
        lab: [],
        both: []
    };
    
    // Capture original time slots
    scheduleTimeSelect.querySelectorAll('optgroup').forEach(optgroup => {
        const type = optgroup.label.toLowerCase().includes('theory') ? 'theory' : 'lab';
        optgroup.querySelectorAll('option').forEach(option => {
            originalTimeSlots[type].push({
                value: option.value,
                text: option.textContent
            });
        });
    });
    
    // Capture original rooms
    roomSelect.querySelectorAll('optgroup').forEach(optgroup => {
        const type = optgroup.label.toLowerCase().replace(' rooms', '');
        optgroup.querySelectorAll('option').forEach(option => {
            originalRooms[type].push({
                value: option.value,
                text: option.textContent,
                roomType: option.dataset.roomType,
                capacity: option.dataset.capacity
            });
        });
    });
    
    function updateTimeSlots() {
        const selectedCourse = courseSelect.selectedOptions[0];
        const selectedSectionType = sectionTypeSelect.value;
        
        // Clear current options except the first one
        scheduleTimeSelect.innerHTML = '<option value="">Select Time Slot</option>';
        
        if (!selectedSectionType) {
            // Show all options when no section type is selected
            addTimeOptGroup('Theory Classes (1.5 hours)', originalTimeSlots.theory);
            addTimeOptGroup('Lab Classes (3 hours)', originalTimeSlots.lab);
            return;
        }
        
        // Filter based on section type
        if (selectedSectionType === 'theory') {
            addTimeOptGroup('Theory Classes (1.5 hours)', originalTimeSlots.theory);
        } else if (selectedSectionType === 'lab') {
            // Check if course has lab component
            if (selectedCourse && selectedCourse.dataset.hasLab === '1') {
                addTimeOptGroup('Lab Classes (3 hours)', originalTimeSlots.lab);
            } else {
                // Show warning if trying to create lab section for non-lab course
                const warningOption = document.createElement('option');
                warningOption.value = '';
                warningOption.textContent = 'This course does not have a lab component';
                warningOption.disabled = true;
                warningOption.style.color = 'red';
                scheduleTimeSelect.appendChild(warningOption);
            }
        }
    }
    
    function updateRoomOptions() {
        const selectedSectionType = sectionTypeSelect.value;
        
        // Clear current room options except the first one
        roomSelect.innerHTML = '<option value="">Select Room</option>';
        
        if (!selectedSectionType) {
            // Show all rooms when no section type is selected
            addRoomOptGroup('Classroom Rooms', originalRooms.classroom);
            addRoomOptGroup('Lab Rooms', originalRooms.lab);
            addRoomOptGroup('Both Rooms', originalRooms.both);
            return;
        }
        
        // Filter rooms based on section type
        if (selectedSectionType === 'theory') {
            // Theory classes can use classroom or both types
            addRoomOptGroup('Classroom Rooms', originalRooms.classroom);
            addRoomOptGroup('Multi-purpose Rooms', originalRooms.both);
        } else if (selectedSectionType === 'lab') {
            // Lab classes can use lab or both types
            addRoomOptGroup('Lab Rooms', originalRooms.lab);
            addRoomOptGroup('Multi-purpose Rooms', originalRooms.both);
        }
        
        // Add help text
        const helpText = document.createElement('option');
        helpText.disabled = true;
        helpText.style.fontStyle = 'italic';
        helpText.style.color = '#6c757d';
        if (selectedSectionType === 'theory') {
            helpText.textContent = '↑ Only classroom and multi-purpose rooms shown';
        } else if (selectedSectionType === 'lab') {
            helpText.textContent = '↑ Only lab and multi-purpose rooms shown';
        }
        roomSelect.appendChild(helpText);
    }
    
    function addTimeOptGroup(label, options) {
        if (options.length === 0) return;
        
        const optgroup = document.createElement('optgroup');
        optgroup.label = label;
        
        options.forEach(slot => {
            const option = document.createElement('option');
            option.value = slot.value;
            option.textContent = slot.text;
            optgroup.appendChild(option);
        });
        
        scheduleTimeSelect.appendChild(optgroup);
    }
    
    function addRoomOptGroup(label, rooms) {
        if (rooms.length === 0) return;
        
        const optgroup = document.createElement('optgroup');
        optgroup.label = label;
        
        rooms.forEach(room => {
            const option = document.createElement('option');
            option.value = room.value;
            option.textContent = room.text;
            option.dataset.roomType = room.roomType;
            option.dataset.capacity = room.capacity;
            optgroup.appendChild(option);
        });
        
        roomSelect.appendChild(optgroup);
    }
    
    function updateSectionTypeOptions() {
        const selectedCourse = courseSelect.selectedOptions[0];
        const hasLab = selectedCourse && selectedCourse.dataset.hasLab === '1';
        
        // Reset section type options
        sectionTypeSelect.innerHTML = '<option value="">Select Type</option>';
        
        if (selectedCourse) {
            // Always allow theory sections
            const theoryOption = document.createElement('option');
            theoryOption.value = 'theory';
            theoryOption.textContent = 'Theory';
            sectionTypeSelect.appendChild(theoryOption);
            
            // Only allow lab sections if course has lab component
            if (hasLab) {
                const labOption = document.createElement('option');
                labOption.value = 'lab';
                labOption.textContent = 'Lab';
                sectionTypeSelect.appendChild(labOption);
            }
        }
        
        // Clear section type selection and update dependent fields
        sectionTypeSelect.value = '';
        updateTimeSlots();
        updateRoomOptions();
    }
    
    function updateCapacityWarning() {
        const selectedRoom = roomSelect.selectedOptions[0];
        const enteredCapacity = parseInt(capacityInput.value);
        
        if (selectedRoom && enteredCapacity) {
            const roomCapacity = parseInt(selectedRoom.dataset.capacity);
            if (enteredCapacity > roomCapacity) {
                capacityInput.setCustomValidity(`Section capacity cannot exceed room capacity (${roomCapacity})`);
                capacityInput.classList.add('is-invalid');
            } else {
                capacityInput.setCustomValidity('');
                capacityInput.classList.remove('is-invalid');
            }
        }
    }
    
    // Event listeners
    courseSelect.addEventListener('change', updateSectionTypeOptions);
    sectionTypeSelect.addEventListener('change', function() {
        updateTimeSlots();
        updateRoomOptions();
    });
    roomSelect.addEventListener('change', updateCapacityWarning);
    capacityInput.addEventListener('input', updateCapacityWarning);
    
    // Initialize
    updateSectionTypeOptions();
    
    // Add form submission handler
    const form = document.getElementById('addSectionForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            console.log('Form submission attempted');
            
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                console.log('Form validation failed');
            } else {
                console.log('Form validation passed, submitting...');
            }
            
            form.classList.add('was-validated');
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>