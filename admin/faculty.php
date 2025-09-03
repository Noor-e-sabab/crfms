<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';
require_once '../includes/admin_dependencies.php';

checkUserType('admin');

$message = '';
$error_message = '';

// Check dependencies
$dependencies = checkPageSpecificDependencies($db, 'faculty');
$can_add_faculty = hasRequiredDependencies($db, 'faculty');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            // Check dependencies before processing
            if (!$can_add_faculty) {
                $error_message = 'Cannot add faculty. Please complete the required setup first (departments are needed).';
            } else {
                $name = sanitizeInput($_POST['name']);
                $email = sanitizeInput($_POST['email']);
                $password = $_POST['password'];
                $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
                $designation = sanitizeInput($_POST['designation']);
                
                if (empty($name) || empty($email) || empty($password) || empty($designation)) {
                    $error_message = 'All fields are required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = 'Please enter a valid email address.';
                } elseif (strlen($password) < 6) {
                    $error_message = 'Password must be at least 6 characters long.';
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $query = "INSERT INTO faculty (name, email, password_hash, department_id, designation) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('sssis', $name, $email, $password_hash, $department_id, $designation);
                    
                    if ($stmt->execute()) {
                        $message = 'Faculty member added successfully!';
                    } else {
                        if ($stmt->errno === 1062) {
                            $error_message = 'Email already exists.';
                        } else {
                            $error_message = 'Failed to add faculty member. Please try again.';
                        }
                    }
                }
            }
        } elseif ($action === 'edit') {
            $faculty_id = (int)$_POST['faculty_id'];
            $name = sanitizeInput($_POST['name']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
            $designation = sanitizeInput($_POST['designation']);
            
            if (empty($name) || empty($email) || empty($designation)) {
                $error_message = 'Name, email, and designation are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Please enter a valid email address.';
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error_message = 'Password must be at least 6 characters long.';
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $query = "UPDATE faculty SET name = ?, email = ?, password_hash = ?, department_id = ?, designation = ? WHERE faculty_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param('sssisi', $name, $email, $password_hash, $department_id, $designation, $faculty_id);
                    }
                } else {
                    $query = "UPDATE faculty SET name = ?, email = ?, department_id = ?, designation = ? WHERE faculty_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('ssisi', $name, $email, $department_id, $designation, $faculty_id);
                }
                
                if (empty($error_message)) {
                    if ($stmt->execute()) {
                        $message = 'Faculty member updated successfully!';
                    } else {
                        if ($stmt->errno === 1062) {
                            $error_message = 'Email already exists.';
                        } else {
                            $error_message = 'Failed to update faculty member. Please try again.';
                        }
                    }
                }
            }
        } elseif ($action === 'delete') {
            $faculty_id = (int)$_POST['faculty_id'];
            
            // Check if faculty has sections
            $check_query = "SELECT COUNT(*) as count FROM sections WHERE faculty_id = ?";
            $stmt = $db->prepare($check_query);
            $stmt->bind_param('i', $faculty_id);
            $stmt->execute();
            $section_count = $stmt->get_result()->fetch_assoc()['count'];
            
            if ($section_count > 0) {
                $error_message = 'Cannot delete faculty member. They have assigned sections.';
            } else {
                $query = "DELETE FROM faculty WHERE faculty_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param('i', $faculty_id);
                
                if ($stmt->execute()) {
                    $message = 'Faculty member deleted successfully!';
                } else {
                    $error_message = 'Failed to delete faculty member. Please try again.';
                }
            }
        }
    }
}

// Get all faculty with department info
$query = "SELECT f.*, d.name as department_name,
          (SELECT COUNT(*) FROM sections WHERE faculty_id = f.faculty_id) as section_count
          FROM faculty f 
          LEFT JOIN departments d ON f.department_id = d.department_id
          ORDER BY f.name";
$faculty = $db->query($query);

// Get all departments for dropdown
$departments_query = "SELECT * FROM departments ORDER BY name";
$departments = $db->query($departments_query);

$page_title = 'Manage Faculty';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Faculty</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($can_add_faculty): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
            <i class="fas fa-plus me-2"></i>Add Faculty
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($can_add_faculty): ?>
    <!-- Main Faculty Management Interface -->
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

<?php if ($can_add_faculty): ?>
    <div class="card-body">
        <?php if ($faculty->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Sections</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($member = $faculty->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $member['faculty_id']; ?></td>
                            <td><strong><?php echo sanitizeInput($member['name']); ?></strong></td>
                            <td><?php echo sanitizeInput($member['email']); ?></td>
                            <td><?php echo $member['department_name'] ?? '<em>Not assigned</em>'; ?></td>
                            <td><?php echo sanitizeInput($member['designation']); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo $member['section_count']; ?></span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($member['created_at'])); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editFaculty(<?php echo $member['faculty_id']; ?>, '<?php echo addslashes($member['name']); ?>', '<?php echo addslashes($member['email']); ?>', <?php echo $member['department_id'] ?? 'null'; ?>, '<?php echo addslashes($member['designation']); ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <?php if ($member['section_count'] == 0): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this faculty member?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="faculty_id" value="<?php echo $member['faculty_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete - has assigned sections">
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
                <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No faculty members found</h5>
                <p class="text-muted">Start by adding your first faculty member.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                    <i class="fas fa-plus me-2"></i>Add Faculty
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Faculty Modal -->
<div class="modal fade" id="addFacultyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Add New Faculty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Faculty Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">Please enter faculty name.</div>
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
                        <label for="designation" class="form-label">Designation</label>
                        <select class="form-select" id="designation" name="designation" required>
                            <option value="">Select Designation</option>
                            <option value="Lecturer">Lecturer</option>
                            <option value="Assistant Professor">Assistant Professor</option>
                            <option value="Associate Professor">Associate Professor</option>
                            <option value="Professor">Professor</option>
                            <option value="Adjunct Faculty">Adjunct Faculty</option>
                        </select>
                        <div class="invalid-feedback">Please select a designation.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Faculty</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Faculty Modal -->
<div class="modal fade" id="editFacultyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Faculty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_faculty_id" name="faculty_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Faculty Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                        <div class="invalid-feedback">Please enter faculty name.</div>
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
                        <label for="edit_designation" class="form-label">Designation</label>
                        <select class="form-select" id="edit_designation" name="designation" required>
                            <option value="">Select Designation</option>
                            <option value="Lecturer">Lecturer</option>
                            <option value="Assistant Professor">Assistant Professor</option>
                            <option value="Associate Professor">Associate Professor</option>
                            <option value="Professor">Professor</option>
                            <option value="Adjunct Faculty">Adjunct Faculty</option>
                        </select>
                        <div class="invalid-feedback">Please select a designation.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Faculty</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
function editFaculty(id, name, email, departmentId, designation) {
    document.getElementById('edit_faculty_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_department_id').value = departmentId || '';
    document.getElementById('edit_designation').value = designation;
    document.getElementById('edit_password').value = '';
    new bootstrap.Modal(document.getElementById('editFacultyModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
