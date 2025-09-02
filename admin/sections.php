<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

checkUserType('admin');

$message = '';
$error_message = '';
$current_semester_info = getCurrentSemester($db);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $course_id = sanitizeInput($_POST['course_id']);
            $faculty_id = (int)$_POST['faculty_id'];
            $semester = sanitizeInput($_POST['semester']);
            $year = (int)$_POST['year'];
            $schedule_days = sanitizeInput($_POST['schedule_days']);
            $schedule_time = sanitizeInput($_POST['schedule_time']);
            $room = sanitizeInput($_POST['room']);
            $capacity = (int)$_POST['capacity'];
            
            if (empty($course_id) || empty($faculty_id) || empty($semester) || empty($year) || 
                empty($schedule_days) || empty($schedule_time) || empty($room) || $capacity <= 0) {
                $error_message = 'All fields are required with valid data.';
            } else {
                // Check for conflicts
                $conflict_query = "SELECT s.section_id, c.course_id, c.title, f.name as faculty_name 
                                   FROM sections s 
                                   JOIN courses c ON s.course_id = c.course_id 
                                   JOIN faculty f ON s.faculty_id = f.faculty_id
                                   WHERE s.semester = ? AND s.year = ? AND s.room = ? 
                                   AND s.schedule_days = ? AND s.schedule_time = ?";
                $stmt = $db->prepare($conflict_query);
                $stmt->bind_param('sisss', $semester, $year, $room, $schedule_days, $schedule_time);
                $stmt->execute();
                $conflicts = $stmt->get_result();
                
                if ($conflicts->num_rows > 0) {
                    $conflict = $conflicts->fetch_assoc();
                    $error_message = "Room/time conflict with {$conflict['course_id']} - {$conflict['title']} taught by {$conflict['faculty_name']}.";
                } else {
                    $query = "INSERT INTO sections (course_id, faculty_id, semester, year, schedule_days, schedule_time, room, capacity) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('sisssssi', $course_id, $faculty_id, $semester, $year, $schedule_days, $schedule_time, $room, $capacity);
                    
                    if ($stmt->execute()) {
                        $message = 'Section added successfully!';
                    } else {
                        $error_message = 'Failed to add section. Please try again.';
                    }
                }
            }
        } elseif ($action === 'edit') {
            $section_id = (int)$_POST['section_id'];
            $course_id = sanitizeInput($_POST['course_id']);
            $faculty_id = (int)$_POST['faculty_id'];
            $semester = sanitizeInput($_POST['semester']);
            $year = (int)$_POST['year'];
            $schedule_days = sanitizeInput($_POST['schedule_days']);
            $schedule_time = sanitizeInput($_POST['schedule_time']);
            $room = sanitizeInput($_POST['room']);
            $capacity = (int)$_POST['capacity'];
            
            if (empty($course_id) || empty($faculty_id) || empty($semester) || empty($year) || 
                empty($schedule_days) || empty($schedule_time) || empty($room) || $capacity <= 0) {
                $error_message = 'All fields are required with valid data.';
            } else {
                // Check for conflicts (excluding current section)
                $conflict_query = "SELECT s.section_id, c.course_id, c.title, f.name as faculty_name 
                                   FROM sections s 
                                   JOIN courses c ON s.course_id = c.course_id 
                                   JOIN faculty f ON s.faculty_id = f.faculty_id
                                   WHERE s.semester = ? AND s.year = ? AND s.room = ? 
                                   AND s.schedule_days = ? AND s.schedule_time = ? AND s.section_id != ?";
                $stmt = $db->prepare($conflict_query);
                $stmt->bind_param('sisssi', $semester, $year, $room, $schedule_days, $schedule_time, $section_id);
                $stmt->execute();
                $conflicts = $stmt->get_result();
                
                if ($conflicts->num_rows > 0) {
                    $conflict = $conflicts->fetch_assoc();
                    $error_message = "Room/time conflict with {$conflict['course_id']} - {$conflict['title']} taught by {$conflict['faculty_name']}.";
                } else {
                    $query = "UPDATE sections SET course_id = ?, faculty_id = ?, semester = ?, year = ?, 
                              schedule_days = ?, schedule_time = ?, room = ?, capacity = ? 
                              WHERE section_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('sisssssi', $course_id, $faculty_id, $semester, $year, $schedule_days, $schedule_time, $room, $capacity, $section_id);
                    
                    if ($stmt->execute()) {
                        $message = 'Section updated successfully!';
                    } else {
                        $error_message = 'Failed to update section. Please try again.';
                    }
                }
            }
        } elseif ($action === 'delete') {
            $section_id = (int)$_POST['section_id'];
            
            // Check if section has registrations
            $check_query = "SELECT COUNT(*) as count FROM registration WHERE section_id = ?";
            $stmt = $db->prepare($check_query);
            $stmt->bind_param('i', $section_id);
            $stmt->execute();
            $registration_count = $stmt->get_result()->fetch_assoc()['count'];
            
            if ($registration_count > 0) {
                $error_message = 'Cannot delete section. It has student registrations.';
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
$query = "SELECT s.*, c.title as course_title, c.credits, f.name as faculty_name, d.name as department_name,
          (SELECT COUNT(*) FROM registration WHERE section_id = s.section_id AND status = 'registered') as enrolled
          FROM sections s 
          JOIN courses c ON s.course_id = c.course_id 
          JOIN faculty f ON s.faculty_id = f.faculty_id
          LEFT JOIN departments d ON c.department_id = d.department_id
          ORDER BY s.year DESC, s.semester DESC, s.course_id, s.section_id";
$sections = $db->query($query);

// Get courses for dropdown
$courses_query = "SELECT course_id, title FROM courses ORDER BY course_id";
$courses = $db->query($courses_query);

// Get faculty for dropdown
$faculty_query = "SELECT faculty_id, name FROM faculty ORDER BY name";
$faculty_list = $db->query($faculty_query);

$page_title = 'Manage Sections';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Sections</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
            <i class="fas fa-plus me-2"></i>Add Section
        </button>
    </div>
</div>

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
    <div class="card-body">
        <?php if ($sections->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Course</th>
                            <th>Title</th>
                            <th>Faculty</th>
                            <th>Semester</th>
                            <th>Schedule</th>
                            <th>Room</th>
                            <th>Enrollment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $current_semester = '';
                        while ($section = $sections->fetch_assoc()): 
                            $semester_year = $section['semester'] . ' ' . $section['year'];
                            if ($semester_year !== $current_semester) {
                                $current_semester = $semester_year;
                                echo '<tr class="table-secondary"><td colspan="9"><strong>' . $semester_year . '</strong></td></tr>';
                            }
                        ?>
                        <tr>
                            <td><?php echo $section['section_id']; ?></td>
                            <td><strong><?php echo sanitizeInput($section['course_id']); ?></strong></td>
                            <td><?php echo sanitizeInput($section['course_title']); ?></td>
                            <td><?php echo sanitizeInput($section['faculty_name']); ?></td>
                            <td>
                                <?php if ($semester_year === $current_semester_info['current_semester'] . ' ' . $current_semester_info['current_year']): ?>
                                    <span class="badge bg-success">Current</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="schedule-time">
                                    <?php echo formatSchedule($section['schedule_days'], $section['schedule_time']); ?>
                                </span>
                            </td>
                            <td><?php echo sanitizeInput($section['room']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $section['enrolled'] >= $section['capacity'] ? 'danger' : 'primary'; ?>">
                                    <?php echo $section['enrolled'] . '/' . $section['capacity']; ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editSection(<?php echo $section['section_id']; ?>, '<?php echo $section['course_id']; ?>', <?php echo $section['faculty_id']; ?>, '<?php echo $section['semester']; ?>', <?php echo $section['year']; ?>, '<?php echo $section['schedule_days']; ?>', '<?php echo $section['schedule_time']; ?>', '<?php echo addslashes($section['room']); ?>', <?php echo $section['capacity']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <?php if ($section['enrolled'] == 0): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this section?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="section_id" value="<?php echo $section['section_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete - has registrations">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
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
                <p class="text-muted">Start by adding your first section to create a new semester.</p>
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
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Add New Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="course_id" class="form-label">Course</label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php 
                                    $courses->data_seek(0);
                                    while ($course = $courses->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $course['course_id']; ?>">
                                            <?php echo $course['course_id'] . ' - ' . sanitizeInput($course['title']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">Please select a course.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="faculty_id" class="form-label">Faculty</label>
                                <select class="form-select" id="faculty_id" name="faculty_id" required>
                                    <option value="">Select Faculty</option>
                                    <?php 
                                    $faculty_list->data_seek(0);
                                    while ($faculty = $faculty_list->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $faculty['faculty_id']; ?>">
                                            <?php echo sanitizeInput($faculty['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">Please select a faculty member.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="Spring" <?php echo $current_semester_info['current_semester'] === 'Spring' ? 'selected' : ''; ?>>Spring</option>
                                    <option value="Summer" <?php echo $current_semester_info['current_semester'] === 'Summer' ? 'selected' : ''; ?>>Summer</option>
                                    <option value="Fall" <?php echo $current_semester_info['current_semester'] === 'Fall' ? 'selected' : ''; ?>>Fall</option>
                                </select>
                                <div class="invalid-feedback">Please select a semester.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="year" class="form-label">Year</label>
                                <input type="number" class="form-control" id="year" name="year" 
                                       min="2020" max="2030" value="<?php echo $current_semester_info['current_year']; ?>" required>
                                <div class="invalid-feedback">Please enter a valid year.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="schedule_days" class="form-label">Days</label>
                                <select class="form-select" id="schedule_days" name="schedule_days" required>
                                    <option value="">Select Days</option>
                                    <option value="MW">Monday & Wednesday</option>
                                    <option value="TR">Tuesday & Thursday</option>
                                    <option value="MWF">Monday, Wednesday & Friday</option>
                                    <option value="S">Saturday</option>
                                    <option value="U">Sunday</option>
                                </select>
                                <div class="invalid-feedback">Please select days.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="schedule_time" class="form-label">Time</label>
                                <select class="form-select" id="schedule_time" name="schedule_time" required>
                                    <option value="">Select Time</option>
                                    <option value="08:00-09:20">08:00 - 09:20</option>
                                    <option value="09:30-10:50">09:30 - 10:50</option>
                                    <option value="11:00-12:20">11:00 - 12:20</option>
                                    <option value="12:30-13:50">12:30 - 13:50</option>
                                    <option value="14:00-15:20">14:00 - 15:20</option>
                                    <option value="15:30-16:50">15:30 - 16:50</option>
                                    <option value="17:00-18:20">17:00 - 18:20</option>
                                </select>
                                <div class="invalid-feedback">Please select time.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="room" class="form-label">Room</label>
                                <input type="text" class="form-control" id="room" name="room" placeholder="e.g., NAC-101" required>
                                <div class="invalid-feedback">Please enter a room.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacity</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" 
                               min="1" max="100" value="40" required>
                        <div class="invalid-feedback">Please enter capacity (1-100).</div>
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

<!-- Edit Section Modal -->
<div class="modal fade" id="editSectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_section_id" name="section_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_course_id" class="form-label">Course</label>
                                <select class="form-select" id="edit_course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php 
                                    $courses->data_seek(0);
                                    while ($course = $courses->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $course['course_id']; ?>">
                                            <?php echo $course['course_id'] . ' - ' . sanitizeInput($course['title']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">Please select a course.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_faculty_id" class="form-label">Faculty</label>
                                <select class="form-select" id="edit_faculty_id" name="faculty_id" required>
                                    <option value="">Select Faculty</option>
                                    <?php 
                                    $faculty_list->data_seek(0);
                                    while ($faculty = $faculty_list->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $faculty['faculty_id']; ?>">
                                            <?php echo sanitizeInput($faculty['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">Please select a faculty member.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_semester" class="form-label">Semester</label>
                                <select class="form-select" id="edit_semester" name="semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="Spring">Spring</option>
                                    <option value="Summer">Summer</option>
                                    <option value="Fall">Fall</option>
                                </select>
                                <div class="invalid-feedback">Please select a semester.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_year" class="form-label">Year</label>
                                <input type="number" class="form-control" id="edit_year" name="year" 
                                       min="2020" max="2030" required>
                                <div class="invalid-feedback">Please enter a valid year.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_schedule_days" class="form-label">Days</label>
                                <select class="form-select" id="edit_schedule_days" name="schedule_days" required>
                                    <option value="">Select Days</option>
                                    <option value="MW">Monday & Wednesday</option>
                                    <option value="TR">Tuesday & Thursday</option>
                                    <option value="MWF">Monday, Wednesday & Friday</option>
                                    <option value="S">Saturday</option>
                                    <option value="U">Sunday</option>
                                </select>
                                <div class="invalid-feedback">Please select days.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_schedule_time" class="form-label">Time</label>
                                <select class="form-select" id="edit_schedule_time" name="schedule_time" required>
                                    <option value="">Select Time</option>
                                    <option value="08:00-09:20">08:00 - 09:20</option>
                                    <option value="09:30-10:50">09:30 - 10:50</option>
                                    <option value="11:00-12:20">11:00 - 12:20</option>
                                    <option value="12:30-13:50">12:30 - 13:50</option>
                                    <option value="14:00-15:20">14:00 - 15:20</option>
                                    <option value="15:30-16:50">15:30 - 16:50</option>
                                    <option value="17:00-18:20">17:00 - 18:20</option>
                                </select>
                                <div class="invalid-feedback">Please select time.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_room" class="form-label">Room</label>
                                <input type="text" class="form-control" id="edit_room" name="room" required>
                                <div class="invalid-feedback">Please enter a room.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_capacity" class="form-label">Capacity</label>
                        <input type="number" class="form-control" id="edit_capacity" name="capacity" 
                               min="1" max="100" required>
                        <div class="invalid-feedback">Please enter capacity (1-100).</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSection(id, courseId, facultyId, semester, year, scheduleDays, scheduleTime, room, capacity) {
    document.getElementById('edit_section_id').value = id;
    document.getElementById('edit_course_id').value = courseId;
    document.getElementById('edit_faculty_id').value = facultyId;
    document.getElementById('edit_semester').value = semester;
    document.getElementById('edit_year').value = year;
    document.getElementById('edit_schedule_days').value = scheduleDays;
    document.getElementById('edit_schedule_time').value = scheduleTime;
    document.getElementById('edit_room').value = room;
    document.getElementById('edit_capacity').value = capacity;
    new bootstrap.Modal(document.getElementById('editSectionModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
