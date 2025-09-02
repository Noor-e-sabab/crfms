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
            
            if (empty($name)) {
                $error_message = 'Department name is required.';
            } else {
                $query = "INSERT INTO departments (name) VALUES (?)";
                $stmt = $db->prepare($query);
                $stmt->bind_param('s', $name);
                
                if ($stmt->execute()) {
                    $message = 'Department added successfully!';
                } else {
                    if ($stmt->errno === 1062) {
                        $error_message = 'Department name already exists.';
                    } else {
                        $error_message = 'Failed to add department. Please try again.';
                    }
                }
            }
        } elseif ($action === 'edit') {
            $department_id = (int)$_POST['department_id'];
            $name = sanitizeInput($_POST['name']);
            
            if (empty($name)) {
                $error_message = 'Department name is required.';
            } else {
                $query = "UPDATE departments SET name = ? WHERE department_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param('si', $name, $department_id);
                
                if ($stmt->execute()) {
                    $message = 'Department updated successfully!';
                } else {
                    if ($stmt->errno === 1062) {
                        $error_message = 'Department name already exists.';
                    } else {
                        $error_message = 'Failed to update department. Please try again.';
                    }
                }
            }
        } elseif ($action === 'delete') {
            $department_id = (int)$_POST['department_id'];
            
            // Check if department has associated records
            $check_queries = [
                'students' => "SELECT COUNT(*) as count FROM students WHERE department_id = ?",
                'faculty' => "SELECT COUNT(*) as count FROM faculty WHERE department_id = ?",
                'courses' => "SELECT COUNT(*) as count FROM courses WHERE department_id = ?"
            ];
            
            $can_delete = true;
            $associated_records = [];
            
            foreach ($check_queries as $table => $query) {
                $stmt = $db->prepare($query);
                $stmt->bind_param('i', $department_id);
                $stmt->execute();
                $count = $stmt->get_result()->fetch_assoc()['count'];
                
                if ($count > 0) {
                    $can_delete = false;
                    $associated_records[] = "$count $table";
                }
            }
            
            if (!$can_delete) {
                $error_message = 'Cannot delete department. It has associated ' . implode(', ', $associated_records) . '.';
            } else {
                $query = "DELETE FROM departments WHERE department_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param('i', $department_id);
                
                if ($stmt->execute()) {
                    $message = 'Department deleted successfully!';
                } else {
                    $error_message = 'Failed to delete department. Please try again.';
                }
            }
        }
    }
}

// Get all departments with statistics
$query = "SELECT d.*, 
          (SELECT COUNT(*) FROM students WHERE department_id = d.department_id AND status = 'active') as student_count,
          (SELECT COUNT(*) FROM faculty WHERE department_id = d.department_id) as faculty_count,
          (SELECT COUNT(*) FROM courses WHERE department_id = d.department_id) as course_count
          FROM departments d 
          ORDER BY d.name";
$departments = $db->query($query);

$page_title = 'Manage Departments';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Departments</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
            <i class="fas fa-plus me-2"></i>Add Department
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
        <?php if ($departments->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Department Name</th>
                            <th>Students</th>
                            <th>Faculty</th>
                            <th>Courses</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($department = $departments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $department['department_id']; ?></td>
                            <td><strong><?php echo sanitizeInput($department['name']); ?></strong></td>
                            <td>
                                <span class="badge bg-primary"><?php echo $department['student_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success"><?php echo $department['faculty_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $department['course_count']; ?></span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($department['created_at'])); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editDepartment(<?php echo $department['department_id']; ?>, '<?php echo addslashes($department['name']); ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <?php if ($department['student_count'] == 0 && $department['faculty_count'] == 0 && $department['course_count'] == 0): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this department?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="department_id" value="<?php echo $department['department_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete - has associated records">
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
                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No departments found</h5>
                <p class="text-muted">Start by adding your first department.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                    <i class="fas fa-plus me-2"></i>Add Department
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Add New Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">
                            Please enter a department name.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_department_id" name="department_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                        <div class="invalid-feedback">
                            Please enter a department name.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editDepartment(id, name) {
    document.getElementById('edit_department_id').value = id;
    document.getElementById('edit_name').value = name;
    new bootstrap.Modal(document.getElementById('editDepartmentModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
