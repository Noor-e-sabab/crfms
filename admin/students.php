<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

checkUserType('admin');

$message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $name = sanitizeInput($_POST['name']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
            $admission_year = (int)$_POST['admission_year'];
            
            if (empty($name) || empty($email) || empty($password) || $admission_year < 2010) {
                $error_message = 'All fields are required with valid data.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Please enter a valid email address.';
            } elseif (strlen($password) < 6) {
                $error_message = 'Password must be at least 6 characters long.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO students (name, email, password_hash, department_id, admission_year) VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->bind_param('sssii', $name, $email, $password_hash, $department_id, $admission_year);
                
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
        } elseif ($action === 'edit') {
            $student_id = (int)$_POST['student_id'];
            $name = sanitizeInput($_POST['name']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
            $admission_year = (int)$_POST['admission_year'];
            $status = sanitizeInput($_POST['status']);
            
            if (empty($name) || empty($email) || $admission_year < 2010) {
                $error_message = 'Name, email, and valid admission year are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Please enter a valid email address.';
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error_message = 'Password must be at least 6 characters long.';
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $query = "UPDATE students SET name = ?, email = ?, password_hash = ?, department_id = ?, admission_year = ?, status = ? WHERE student_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param('sssiisi', $name, $email, $password_hash, $department_id, $admission_year, $status, $student_id);
                    }
                } else {
                    $query = "UPDATE students SET name = ?, email = ?, department_id = ?, admission_year = ?, status = ? WHERE student_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('ssiisi', $name, $email, $department_id, $admission_year, $status, $student_id);
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

// Get all students with department info
$query = "SELECT s.*, d.name as department_name,
          (SELECT COUNT(*) FROM registration WHERE student_id = s.student_id AND status = 'registered') as active_registrations
          FROM students s 
          LEFT JOIN departments d ON s.department_id = d.department_id
          ORDER BY s.name";
$students = $db->query($query);

// Get all departments for dropdown
$departments_query = "SELECT * FROM departments ORDER BY name";
$departments = $db->query($departments_query);

$page_title = 'Manage Students';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Students</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            <i class="fas fa-plus me-2"></i>Add Student
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
        <?php if ($students->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Admission Year</th>
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
                            <td><?php echo $student['admission_year']; ?></td>
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
                                        onclick="editStudent(<?php echo $student['student_id']; ?>, '<?php echo addslashes($student['name']); ?>', '<?php echo addslashes($student['email']); ?>', <?php echo $student['department_id'] ?? 'null'; ?>, <?php echo $student['admission_year']; ?>, '<?php echo $student['status']; ?>')">
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
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Student Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">Please enter student name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">Please enter a valid email.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                        <div class="invalid-feedback">Password must be at least 6 characters.</div>
                    </div>
                    
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
                    
                    <div class="mb-3">
                        <label for="admission_year" class="form-label">Admission Year</label>
                        <input type="number" class="form-control" id="admission_year" name="admission_year" 
                               min="2010" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>" required>
                        <div class="invalid-feedback">Please enter a valid admission year.</div>
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
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Student Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                        <div class="invalid-feedback">Please enter student name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                        <div class="invalid-feedback">Please enter a valid email.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                        <div class="form-text">Leave blank to keep current password.</div>
                        <div class="invalid-feedback">Password must be at least 6 characters.</div>
                    </div>
                    
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
                    
                    <div class="mb-3">
                        <label for="edit_admission_year" class="form-label">Admission Year</label>
                        <input type="number" class="form-control" id="edit_admission_year" name="admission_year" 
                               min="2010" max="<?php echo date('Y'); ?>" required>
                        <div class="invalid-feedback">Please enter a valid admission year.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="graduated">Graduated</option>
                        </select>
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

<script>
function editStudent(id, name, email, departmentId, admissionYear, status) {
    document.getElementById('edit_student_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_department_id').value = departmentId || '';
    document.getElementById('edit_admission_year').value = admissionYear;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_password').value = '';
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
