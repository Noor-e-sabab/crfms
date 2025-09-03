<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';
require_once '../includes/admin_dependencies.php';

checkUserType('admin');

$message = '';
$error_message = '';

// Check dependencies
$dependencies = checkPageSpecificDependencies($db, 'students');
$can_add_students = hasRequiredDependencies($db, 'students');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            // Check dependencies before processing
            if (!$can_add_students) {
                $error_message = 'Cannot add students. Please complete the required setup first (departments are needed).';
            } else {
                $name = sanitizeInput($_POST['name']);
                $email = sanitizeInput($_POST['email']);
                $password = $_POST['password'];
                $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
                $program_id = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
                $admission_year = (int)$_POST['admission_year'];
                $admission_semester = sanitizeInput($_POST['admission_semester']);
                
                if (empty($name) || empty($email) || empty($password) || $admission_year < 2010 || empty($admission_semester)) {
                    $error_message = 'All fields are required with valid data.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = 'Please enter a valid email address.';
                } elseif (strlen($password) < 6) {
                    $error_message = 'Password must be at least 6 characters long.';
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO students (name, email, password_hash, department_id, program_id, admission_year, admission_semester) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->bind_param('sssiiss', $name, $email, $password_hash, $department_id, $program_id, $admission_year, $admission_semester);
                
                if ($stmt->execute()) {
                    $message = 'Student added successfully!';
                } else {
                    if ($stmt->errno === 1062) {
                        $error_message = 'Email already exists.';
                    } else {
                        $error_message = 'Failed to add student. Please try again.';
                    }
                }
            }
            }
        } elseif ($action === 'edit') {
            $student_id = (int)$_POST['student_id'];
            $name = sanitizeInput($_POST['name']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
            $program_id = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
            $admission_year = (int)$_POST['admission_year'];
            $admission_semester = sanitizeInput($_POST['admission_semester']);
            $status = sanitizeInput($_POST['status']);
            
            if (empty($name) || empty($email) || $admission_year < 2010 || empty($admission_semester)) {
                $error_message = 'Name, email, admission year and semester are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Please enter a valid email address.';
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error_message = 'Password must be at least 6 characters long.';
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $query = "UPDATE students SET name = ?, email = ?, password_hash = ?, department_id = ?, program_id = ?, admission_year = ?, admission_semester = ?, status = ? WHERE student_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param('sssiiiisi', $name, $email, $password_hash, $department_id, $program_id, $admission_year, $admission_semester, $status, $student_id);
                    }
                } else {
                    $query = "UPDATE students SET name = ?, email = ?, department_id = ?, program_id = ?, admission_year = ?, admission_semester = ?, status = ? WHERE student_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('ssiiiisi', $name, $email, $department_id, $program_id, $admission_year, $admission_semester, $status, $student_id);
                }
                
                if (empty($error_message)) {
                    if ($stmt->execute()) {
                        $message = 'Student updated successfully!';
                    } else {
                        if ($stmt->errno === 1062) {
                            $error_message = 'Email already exists.';
                        } else {
                            $error_message = 'Failed to update student. Please try again.';
                        }
                    }
                }
            }
        } elseif ($action === 'delete') {
            $student_id = (int)$_POST['student_id'];
            
            // Check if student has registrations
            $check_query = "SELECT COUNT(*) as count FROM registration WHERE student_id = ?";
            $stmt = $db->prepare($check_query);
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $registration_count = $stmt->get_result()->fetch_assoc()['count'];
            
            if ($registration_count > 0) {
                $error_message = 'Cannot delete student. Student has course registrations.';
            } else {
                $query = "DELETE FROM students WHERE student_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param('i', $student_id);
                
                if ($stmt->execute()) {
                    $message = 'Student deleted successfully!';
                } else {
                    $error_message = 'Failed to delete student. Please try again.';
                }
            }
        }
    }
}

// Get all students with department and program info
$query = "SELECT s.*, d.name as department_name, p.program_name, p.program_code, p.short_code as program_short_code,
          (SELECT COUNT(*) FROM registration WHERE student_id = s.student_id AND status = 'registered') as active_registrations
          FROM students s 
          LEFT JOIN departments d ON s.department_id = d.department_id
          LEFT JOIN programs p ON s.program_id = p.program_id
          ORDER BY s.name";
$students = $db->query($query);

// Get all departments for dropdown
$departments_query = "SELECT * FROM departments ORDER BY name";
$departments = $db->query($departments_query);

// Get all programs for dropdown
$programs_query = "SELECT p.*, d.name as department_name FROM programs p 
                   LEFT JOIN departments d ON p.department_id = d.department_id 
                   ORDER BY d.name, p.program_name";
$programs = $db->query($programs_query);

$page_title = 'Manage Students';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Students</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($can_add_students): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            <i class="fas fa-plus me-2"></i>Add Student
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($can_add_students): ?>
    <!-- Main Student Management Interface -->
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

<?php if ($can_add_students): ?>
    <div class="card-body">
        <?php if ($students->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Program</th>
                            <th>Admission</th>
                            <th>Status</th>
                            <th>Active Courses</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $student['student_id']; ?></td>
                            <td><strong><?php echo sanitizeInput($student['name']); ?></strong></td>
                            <td><?php echo sanitizeInput($student['email']); ?></td>
                            <td><?php echo $student['department_name'] ?? '<em>Not assigned</em>'; ?></td>
                            <td>
                                <?php if (!empty($student['program_name'])): ?>
                                    <span class="badge bg-secondary">
                                        <?php echo !empty($student['program_short_code']) ? sanitizeInput($student['program_short_code']) : sanitizeInput($student['program_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <small class="text-muted">No program</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo $student['admission_semester'] . ' ' . $student['admission_year']; ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $student['status'] === 'active' ? 'success' : ($student['status'] === 'graduated' ? 'info' : 'secondary'); ?>">
                                    <?php echo ucfirst($student['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo $student['active_registrations']; ?></span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editStudent(<?php echo $student['student_id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <?php if ($student['active_registrations'] == 0): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this student?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete - has course registrations">
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
                <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No students found</h5>
                <p class="text-muted">Start by adding your first student.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus me-2"></i>Add Student
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Student Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">Please enter student name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                        <div class="invalid-feedback">Password must be at least 6 characters.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department_id" class="form-label">Department</label>
                                <select class="form-select" id="department_id" name="department_id">
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
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="admission_year" class="form-label">Admission Year</label>
                                <input type="number" class="form-control" id="admission_year" name="admission_year" 
                                       min="2010" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>" required>
                                <div class="invalid-feedback">Please enter a valid admission year.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="admission_semester" class="form-label">Admission Semester</label>
                                <select class="form-select" id="admission_semester" name="admission_semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="Spring">Spring</option>
                                    <option value="Summer">Summer</option>
                                    <option value="Fall">Fall</option>
                                </select>
                                <div class="invalid-feedback">Please select admission semester.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_student_id" name="student_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Student Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                                <div class="invalid-feedback">Please enter student name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                        <div class="form-text">Leave blank to keep current password.</div>
                        <div class="invalid-feedback">Password must be at least 6 characters.</div>
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
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_admission_year" class="form-label">Admission Year</label>
                                <input type="number" class="form-control" id="edit_admission_year" name="admission_year" 
                                       min="2010" max="<?php echo date('Y'); ?>" required>
                                <div class="invalid-feedback">Please enter a valid admission year.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_admission_semester" class="form-label">Admission Semester</label>
                                <select class="form-select" id="edit_admission_semester" name="admission_semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="Spring">Spring</option>
                                    <option value="Summer">Summer</option>
                                    <option value="Fall">Fall</option>
                                </select>
                                <div class="invalid-feedback">Please select admission semester.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="graduated">Graduated</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
function editStudent(studentId) {
    // Fetch student details via AJAX
    fetch(`get_student_details.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_student_id').value = data.student_id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_email').value = data.email;
            document.getElementById('edit_department_id').value = data.department_id || '';
            document.getElementById('edit_program_id').value = data.program_id || '';
            document.getElementById('edit_admission_year').value = data.admission_year;
            document.getElementById('edit_admission_semester').value = data.admission_semester;
            document.getElementById('edit_status').value = data.status;
            document.getElementById('edit_password').value = '';
            
            new bootstrap.Modal(document.getElementById('editStudentModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load student details');
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>
