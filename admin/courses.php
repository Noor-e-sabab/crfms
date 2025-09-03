<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';
require_once '../includes/admin_dependencies.php';

checkUserType('admin');

$message = '';
$error_message = '';

// Check dependencies
$dependencies = checkPageSpecificDependencies($db, 'courses');
$can_add_courses = hasRequiredDependencies($db, 'courses');

// DEBUG: Let's see what's happening
error_log("DEBUG: can_add_courses = " . ($can_add_courses ? 'true' : 'false'));
error_log("DEBUG: dependencies count = " . count($dependencies));

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            // Check dependencies before processing
            if (!$can_add_courses) {
                $error_message = 'Cannot add courses. Please complete the required setup first (departments are needed).';
            } else {
                $course_id = strtoupper(sanitizeInput($_POST['course_id']));
                $title = sanitizeInput($_POST['title']);
                $theory_credits = (float)$_POST['theory_credits'];
                $lab_credits = (float)$_POST['lab_credits'];
                $has_lab = isset($_POST['has_lab']) ? 1 : 0;
                $program_id = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
                $description = sanitizeInput($_POST['description']);
                $prerequisites = isset($_POST['prerequisites']) ? $_POST['prerequisites'] : [];
                
                // Auto-detect department from course_id
                $department_id = null;
                if (preg_match('/^([A-Z]+)/', $course_id, $matches)) {
                    $dept_code = $matches[1];
                    $dept_query = "SELECT department_id FROM departments WHERE short_name = ?";
                    $dept_stmt = $db->prepare($dept_query);
                    $dept_stmt->bind_param('s', $dept_code);
                    $dept_stmt->execute();
                $dept_result = $dept_stmt->get_result();
                if ($dept_result->num_rows > 0) {
                    $department_id = $dept_result->fetch_assoc()['department_id'];
                }
            }
            
            if (empty($course_id) || empty($title) || $theory_credits <= 0) {
                $error_message = 'Course ID, title, and theory credits are required.';
            } elseif (!$department_id) {
                $error_message = 'Could not auto-detect department from course ID. Please ensure department short name matches course prefix.';
            } else {
                // Start transaction
                $db->getConnection()->autocommit(false);
                
                try {
                    // Insert course
                    $query = "INSERT INTO courses (course_id, title, theory_credits, lab_credits, has_lab, department_id, program_id, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('ssddiiis', $course_id, $title, $theory_credits, $lab_credits, $has_lab, $department_id, $program_id, $description);
                    
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to add course');
                    }
                    
                    // Add prerequisites
                    if (!empty($prerequisites)) {
                        $prereq_query = "INSERT INTO prerequisites (course_id, prerequisite_course_id) VALUES (?, ?)";
                        $prereq_stmt = $db->prepare($prereq_query);
                        
                        foreach ($prerequisites as $prereq_id) {
                            if (!empty(trim($prereq_id))) {
                                $prereq_stmt->bind_param('ss', $course_id, $prereq_id);
                                $prereq_stmt->execute();
                            }
                        }
                    }
                    
                    $db->getConnection()->commit();
                    $message = 'Course added successfully!';
                    
                } catch (Exception $e) {
                    $db->getConnection()->rollback();
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $error_message = 'Course ID already exists.';
                    } else {
                        $error_message = 'Failed to add course. Please try again.';
                    }
                }
                
                $db->getConnection()->autocommit(true);
            }
            }
        } elseif ($action === 'edit') {
            $course_id = $_POST['course_id'];
            $title = sanitizeInput($_POST['title']);
            $theory_credits = (float)$_POST['theory_credits'];
            $lab_credits = (float)$_POST['lab_credits'];
            $has_lab = isset($_POST['has_lab']) ? 1 : 0;
            $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
            $program_id = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
            $description = sanitizeInput($_POST['description']);
            $prerequisites = isset($_POST['prerequisites']) ? $_POST['prerequisites'] : [];
            
            if (empty($title) || ($theory_credits + $lab_credits) <= 0) {
                $error_message = 'Title and valid credit hours are required.';
            } else {
                // Calculate total credits
                $total_credits = $theory_credits + $lab_credits;
                
                // Start transaction
                $db->getConnection()->autocommit(false);
                
                try {
                    // Update course
                    $query = "UPDATE courses SET title = ?, theory_credits = ?, lab_credits = ?, total_credits = ?, 
                              has_lab = ?, department_id = ?, program_id = ?, description = ? WHERE course_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('sdddiiiis', $title, $theory_credits, $lab_credits, $total_credits, 
                                     $has_lab, $department_id, $program_id, $description, $course_id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to update course');
                    }
                    
                    // Delete existing prerequisites
                    $delete_prereq = "DELETE FROM prerequisites WHERE course_id = ?";
                    $stmt = $db->prepare($delete_prereq);
                    $stmt->bind_param('s', $course_id);
                    $stmt->execute();
                    
                    // Add new prerequisites
                    if (!empty($prerequisites)) {
                        $prereq_query = "INSERT INTO prerequisites (course_id, prerequisite_course_id) VALUES (?, ?)";
                        $prereq_stmt = $db->prepare($prereq_query);
                        
                        foreach ($prerequisites as $prereq_id) {
                            if (!empty(trim($prereq_id))) {
                                $prereq_stmt->bind_param('ss', $course_id, $prereq_id);
                                $prereq_stmt->execute();
                            }
                        }
                    }
                    
                    $db->getConnection()->commit();
                    $message = 'Course updated successfully!';
                    
                } catch (Exception $e) {
                    $db->getConnection()->rollback();
                    $error_message = 'Failed to update course. Please try again.';
                }
                
                $db->getConnection()->autocommit(true);
            }
        } elseif ($action === 'delete') {
            $course_id = $_POST['course_id'];
            
            // Check if course has sections
            $check_query = "SELECT COUNT(*) as count FROM sections WHERE course_id = ?";
            $stmt = $db->prepare($check_query);
            $stmt->bind_param('s', $course_id);
            $stmt->execute();
            $section_count = $stmt->get_result()->fetch_assoc()['count'];
            
            if ($section_count > 0) {
                $error_message = 'Cannot delete course. It has associated sections.';
            } else {
                // Start transaction
                $db->getConnection()->autocommit(false);
                
                try {
                    // Delete prerequisites first
                    $delete_prereq = "DELETE FROM prerequisites WHERE course_id = ? OR prerequisite_course_id = ?";
                    $stmt = $db->prepare($delete_prereq);
                    $stmt->bind_param('ss', $course_id, $course_id);
                    $stmt->execute();
                    
                    // Delete course
                    $query = "DELETE FROM courses WHERE course_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('s', $course_id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to delete course');
                    }
                    
                    $db->getConnection()->commit();
                    $message = 'Course deleted successfully!';
                    
                } catch (Exception $e) {
                    $db->getConnection()->rollback();
                    $error_message = 'Failed to delete course. Please try again.';
                }
                
                $db->getConnection()->autocommit(true);
            }
        }
    }
}

// Get all courses with department info, program info, and prerequisites
$query = "SELECT c.*, d.name as department_name, 
          pr.program_name, pr.program_code, pr.short_code as program_short_code,
          (SELECT COUNT(*) FROM sections WHERE course_id = c.course_id) as section_count,
          GROUP_CONCAT(DISTINCT p.prerequisite_course_id) as prerequisites
          FROM courses c 
          LEFT JOIN departments d ON c.department_id = d.department_id
          LEFT JOIN programs pr ON c.program_id = pr.program_id
          LEFT JOIN prerequisites p ON c.course_id = p.course_id
          GROUP BY c.course_id
          ORDER BY c.course_id";
$courses = $db->query($query);

// Get all departments for dropdown
$departments_query = "SELECT * FROM departments ORDER BY name";
$departments = $db->query($departments_query);

// Get all programs for dropdown
$programs_query = "SELECT p.*, d.name as department_name FROM programs p 
                   LEFT JOIN departments d ON p.department_id = d.department_id 
                   ORDER BY d.name, p.program_name";
$programs = $db->query($programs_query);

// Get all courses for prerequisites dropdown
$all_courses_query = "SELECT course_id, title FROM courses ORDER BY course_id";
$all_courses = $db->query($all_courses_query);

$page_title = 'Manage Courses';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Courses</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($can_add_courses): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
            <i class="fas fa-plus me-2"></i>Add Course
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($can_add_courses): ?>
    <!-- Main Course Management Interface -->
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

<?php if ($can_add_courses): ?>
    <div class="card-body">
        <?php if ($courses->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Course ID</th>
                            <th>Title</th>
                            <th>Theory Credits</th>
                            <th>Lab Credits</th>
                            <th>Total Credits</th>
                            <th>Department</th>
                            <th>Program</th>
                            <th>Prerequisites</th>
                            <th>Sections</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo sanitizeInput($course['course_id']); ?></strong></td>
                            <td><?php echo sanitizeInput($course['title']); ?></td>
                            <td><?php echo $course['theory_credits']; ?></td>
                            <td><?php echo $course['lab_credits'] > 0 ? $course['lab_credits'] : '-'; ?></td>
                            <td><strong><?php echo $course['total_credits']; ?></strong></td>
                            <td><?php echo $course['department_name'] ?? '<em>Not assigned</em>'; ?></td>
                            <td>
                                <?php if (!empty($course['program_name'])): ?>
                                    <span class="badge bg-secondary">
                                        <?php echo !empty($course['program_short_code']) ? sanitizeInput($course['program_short_code']) : sanitizeInput($course['program_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <small class="text-muted">No program</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($course['prerequisites'])): ?>
                                    <small class="text-muted"><?php echo sanitizeInput($course['prerequisites']); ?></small>
                                <?php else: ?>
                                    <small class="text-success">None</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $course['section_count']; ?></span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editCourse('<?php echo $course['course_id']; ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <?php if ($course['section_count'] == 0): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this course?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete - has sections">
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
                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No courses found</h5>
                <p class="text-muted">Start by adding your first course.</p>
                <?php if ($can_add_courses): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                    <i class="fas fa-plus me-2"></i>Add Course
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Add New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="course_id" class="form-label">Course ID</label>
                                <input type="text" class="form-control" id="course_id" name="course_id" 
                                       placeholder="e.g., CSE101" required>
                                <div class="invalid-feedback">
                                    Please enter a course ID.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Course Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                                <div class="invalid-feedback">
                                    Please enter a course title.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="theory_credits" class="form-label">Theory Credits</label>
                                <input type="number" class="form-control" id="theory_credits" name="theory_credits" 
                                       min="0" max="6" step="0.5" required>
                                <div class="invalid-feedback">
                                    Please enter theory credits.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="lab_credits" class="form-label">Lab Credits</label>
                                <input type="number" class="form-control" id="lab_credits" name="lab_credits" 
                                       min="0" max="6" step="0.5" value="0">
                                <div class="form-text">Leave 0 if no lab component.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Has Lab Component?</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="has_lab" name="has_lab" value="1">
                                    <label class="form-check-label" for="has_lab">
                                        This course has lab sessions
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department_id" class="form-label">Department</label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">Auto-detect from Course ID</option>
                                    <?php 
                                    $departments->data_seek(0);
                                    while ($dept = $departments->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $dept['department_id']; ?>">
                                            <?php echo sanitizeInput($dept['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="form-text">Department will be auto-detected from course prefix if not selected.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="program_id" class="form-label">Program <small class="text-muted">(Optional)</small></label>
                                <select class="form-select" id="program_id" name="program_id">
                                    <option value="">No specific program</option>
                                    <?php 
                                    $programs->data_seek(0);
                                    while ($prog = $programs->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $prog['program_id']; ?>">
                                            <?php echo sanitizeInput($prog['department_name'] . ' - ' . $prog['program_name']); ?>
                                            <?php if (!empty($prog['short_code'])): ?>
                                                (<?php echo sanitizeInput($prog['short_code']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <small class="text-muted">(Optional)</small></label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="prerequisites" class="form-label">Prerequisites <small class="text-muted">(Optional)</small></label>
                        <select class="form-select" id="prerequisites" name="prerequisites[]" multiple>
                            <?php 
                            $all_courses->data_seek(0);
                            while ($course_opt = $all_courses->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $course_opt['course_id']; ?>">
                                    <?php echo $course_opt['course_id'] . ' - ' . sanitizeInput($course_opt['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple prerequisites.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_course_id" name="course_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_course_id_display" class="form-label">Course ID</label>
                                <input type="text" class="form-control" id="edit_course_id_display" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_title" class="form-label">Course Title</label>
                                <input type="text" class="form-control" id="edit_title" name="title" required>
                                <div class="invalid-feedback">
                                    Please enter a course title.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_theory_credits" class="form-label">Theory Credits</label>
                                <input type="number" class="form-control" id="edit_theory_credits" name="theory_credits" 
                                       min="0" max="6" step="0.5" required>
                                <div class="invalid-feedback">
                                    Please enter theory credits.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_lab_credits" class="form-label">Lab Credits</label>
                                <input type="number" class="form-control" id="edit_lab_credits" name="lab_credits" 
                                       min="0" max="6" step="0.5" value="0">
                                <div class="form-text">Leave 0 if no lab component.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Has Lab Component?</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_has_lab" name="has_lab" value="1">
                                    <label class="form-check-label" for="edit_has_lab">
                                        This course has lab sessions
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_department_id" class="form-label">Department</label>
                                <select class="form-select" id="edit_department_id" name="department_id">
                                    <option value="">Select Department</option>
                                    <?php 
                                    $departments->data_seek(0);
                                    while ($dept = $departments->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $dept['department_id']; ?>">
                                            <?php echo sanitizeInput($dept['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_program_id" class="form-label">Program <small class="text-muted">(Optional)</small></label>
                                <select class="form-select" id="edit_program_id" name="program_id">
                                    <option value="">No specific program</option>
                                    <?php 
                                    $programs->data_seek(0);
                                    while ($prog = $programs->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $prog['program_id']; ?>">
                                            <?php echo sanitizeInput($prog['department_name'] . ' - ' . $prog['program_name']); ?>
                                            <?php if (!empty($prog['short_code'])): ?>
                                                (<?php echo sanitizeInput($prog['short_code']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_prerequisites" class="form-label">Prerequisites</label>
                        <select class="form-select" id="edit_prerequisites" name="prerequisites[]" multiple>
                            <!-- Will be populated by JavaScript -->
                        </select>
                        <div class="form-text">
                            Hold Ctrl/Cmd to select multiple prerequisites.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
// Auto-check "Has Lab" when lab credits are entered
document.addEventListener('DOMContentLoaded', function() {
    const labCreditsInput = document.getElementById('lab_credits');
    const hasLabCheckbox = document.getElementById('has_lab');
    const editLabCreditsInput = document.getElementById('edit_lab_credits');
    const editHasLabCheckbox = document.getElementById('edit_has_lab');
    
    // Add Course modal
    if (labCreditsInput && hasLabCheckbox) {
        labCreditsInput.addEventListener('input', function() {
            if (parseFloat(this.value) > 0) {
                hasLabCheckbox.checked = true;
            } else {
                hasLabCheckbox.checked = false;
            }
        });
        
        hasLabCheckbox.addEventListener('change', function() {
            if (!this.checked) {
                labCreditsInput.value = '0';
            }
        });
    }
    
    // Edit Course modal
    if (editLabCreditsInput && editHasLabCheckbox) {
        editLabCreditsInput.addEventListener('input', function() {
            if (parseFloat(this.value) > 0) {
                editHasLabCheckbox.checked = true;
            } else {
                editHasLabCheckbox.checked = false;
            }
        });
        
        editHasLabCheckbox.addEventListener('change', function() {
            if (!this.checked) {
                editLabCreditsInput.value = '0';
            }
        });
    }
});

function editCourse(courseId) {
    // Fetch course details via AJAX
    fetch(`get_course_details.php?course_id=${courseId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_course_id').value = data.course_id;
            document.getElementById('edit_course_id_display').value = data.course_id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_theory_credits').value = data.theory_credits;
            document.getElementById('edit_lab_credits').value = data.lab_credits || 0;
            document.getElementById('edit_has_lab').checked = data.has_lab == 1;
            document.getElementById('edit_department_id').value = data.department_id || '';
            document.getElementById('edit_program_id').value = data.program_id || '';
            document.getElementById('edit_description').value = data.description || '';
            
            // Populate prerequisites dropdown
            const prereqSelect = document.getElementById('edit_prerequisites');
            prereqSelect.innerHTML = '';
            
            <?php 
            $all_courses->data_seek(0);
            while ($course_opt = $all_courses->fetch_assoc()): 
            ?>
            const option<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $course_opt['course_id']); ?> = document.createElement('option');
            option<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $course_opt['course_id']); ?>.value = '<?php echo $course_opt['course_id']; ?>';
            option<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $course_opt['course_id']); ?>.textContent = '<?php echo $course_opt['course_id'] . ' - ' . addslashes($course_opt['title']); ?>';
            if (data.prerequisites && data.prerequisites.includes('<?php echo $course_opt['course_id']; ?>')) {
                option<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $course_opt['course_id']); ?>.selected = true;
            }
            prereqSelect.appendChild(option<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $course_opt['course_id']); ?>);
            <?php endwhile; ?>
            
            new bootstrap.Modal(document.getElementById('editCourseModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load course details');
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>
