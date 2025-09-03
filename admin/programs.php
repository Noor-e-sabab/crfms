<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';
require_once '../includes/admin_dependencies.php';

checkUserType('admin');

$message = '';
$error_message = '';

// Check dependencies
$dependencies = checkPageSpecificDependencies($db, 'programs');
$can_add_programs = hasRequiredDependencies($db, 'programs');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            // Check dependencies before processing
            if (!$can_add_programs) {
                $error_message = 'Cannot add programs. Please complete the required setup first (departments are needed).';
            } else {
                $program_name = sanitizeInput($_POST['program_name']);
                $program_code = sanitizeInput($_POST['program_code']);
                $short_code = !empty($_POST['short_code']) ? sanitizeInput($_POST['short_code']) : null;
                $department_id = (int)$_POST['department_id'];
                
                if (empty($program_name) || empty($program_code) || empty($department_id)) {
                    $error_message = 'Program name, program code, and department are required.';
                } else {
                    $query = "INSERT INTO programs (program_name, program_code, short_code, department_id) VALUES (?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('sssi', $program_name, $program_code, $short_code, $department_id);
                    
                    if ($stmt->execute()) {
                        $message = 'Program added successfully!';
                    } else {
                        if ($stmt->errno === 1062) {
                            $error_message = 'Program code already exists.';
                        } else {
                            $error_message = 'Failed to add program. Please try again.';
                        }
                    }
                }
            }
        } elseif ($action === 'edit') {
            $program_id = (int)$_POST['program_id'];
            $program_name = sanitizeInput($_POST['program_name']);
            $program_code = sanitizeInput($_POST['program_code']);
            $short_code = !empty($_POST['short_code']) ? sanitizeInput($_POST['short_code']) : null;
            $department_id = (int)$_POST['department_id'];
            
            if (empty($program_name) || empty($program_code) || empty($department_id)) {
                $error_message = 'Program name, program code, and department are required.';
            } else {
                $query = "UPDATE programs SET program_name = ?, program_code = ?, short_code = ?, department_id = ? WHERE program_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param('sssii', $program_name, $program_code, $short_code, $department_id, $program_id);
                
                if ($stmt->execute()) {
                    $message = 'Program updated successfully!';
                } else {
                    if ($stmt->errno === 1062) {
                        $error_message = 'Program code already exists.';
                    } else {
                        $error_message = 'Failed to update program. Please try again.';
                    }
                }
            }
        } elseif ($action === 'delete') {
            $program_id = (int)$_POST['program_id'];
            
            // Check if program has any students or courses
            $query = "SELECT COUNT(*) as count FROM students WHERE program_id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('i', $program_id);
            $stmt->execute();
            $student_count = $stmt->get_result()->fetch_assoc()['count'];
            
            $query = "SELECT COUNT(*) as count FROM courses WHERE program_id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('i', $program_id);
            $stmt->execute();
            $course_count = $stmt->get_result()->fetch_assoc()['count'];
            
            if ($student_count > 0 || $course_count > 0) {
                $error_message = 'Cannot delete program. It has associated students or courses.';
            } else {
                $query = "DELETE FROM programs WHERE program_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param('i', $program_id);
                
                if ($stmt->execute()) {
                    $message = 'Program deleted successfully!';
                } else {
                    $error_message = 'Failed to delete program. Please try again.';
                }
            }
        }
    }
}

// Get all programs with department info and statistics
$query = "SELECT p.*, d.name as department_name,
          (SELECT COUNT(*) FROM students WHERE program_id = p.program_id AND status = 'active') as student_count,
          (SELECT COUNT(*) FROM courses WHERE program_id = p.program_id) as course_count
          FROM programs p 
          JOIN departments d ON p.department_id = d.department_id
          ORDER BY d.name, p.program_name";
$programs = $db->query($query);

// Get all departments for dropdown
$departments_query = "SELECT department_id, name FROM departments ORDER BY name";
$departments = $db->query($departments_query);

$page_title = 'Manage Programs';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Programs</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($can_add_programs): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">
            <i class="fas fa-plus me-2"></i>Add Program
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($can_add_programs): ?>
    <!-- Main Program Management Interface -->
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

<?php if ($can_add_programs): ?>
<div class="card">
    <div class="card-body">
        <?php if ($programs->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Program Name</th>
                            <th>Code</th>
                            <th>Short Code</th>
                            <th>Department</th>
                            <th>Students</th>
                            <th>Courses</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($program = $programs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $program['program_id']; ?></td>
                            <td><strong><?php echo sanitizeInput($program['program_name']); ?></strong></td>
                            <td><span class="badge bg-info"><?php echo sanitizeInput($program['program_code']); ?></span></td>
                            <td>
                                <?php if (!empty($program['short_code'])): ?>
                                    <span class="badge bg-secondary"><?php echo sanitizeInput($program['short_code']); ?></span>
                                <?php else: ?>
                                    <small class="text-muted">Uses program name</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo sanitizeInput($program['department_name']); ?></td>
                            <td>
                                <span class="badge bg-primary"><?php echo $program['student_count']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success"><?php echo $program['course_count']; ?></span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($program['created_at'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editProgram(<?php echo $program['program_id']; ?>, '<?php echo addslashes($program['program_name']); ?>', '<?php echo addslashes($program['program_code']); ?>', '<?php echo addslashes($program['short_code'] ?? ''); ?>', <?php echo $program['department_id']; ?>)">>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this program?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="program_id" value="<?php echo $program['program_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No programs found</h5>
                <p class="text-muted">Click "Add Program" to create your first program.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">
                    <i class="fas fa-plus me-2"></i>Add Program
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Program Modal -->
<div class="modal fade" id="addProgramModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Add New Program</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="program_name" class="form-label">Program Name</label>
                        <input type="text" class="form-control" id="program_name" name="program_name" required>
                        <div class="invalid-feedback">
                            Please enter a program name.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="program_code" class="form-label">Program Code</label>
                        <input type="text" class="form-control" id="program_code" name="program_code" maxlength="10" required>
                        <div class="invalid-feedback">
                            Please enter a program code (e.g., CSE, ICE).
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="short_code" class="form-label">Short Code <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control" id="short_code" name="short_code" maxlength="10">
                        <div class="form-text">Brief abbreviation for the program (e.g., "BSC", "MSC"). If not provided, program name will be used.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id" required>
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
                        <div class="invalid-feedback">
                            Please select a department.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Program Modal -->
<div class="modal fade" id="editProgramModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Program</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_program_id" name="program_id">
                    
                    <div class="mb-3">
                        <label for="edit_program_name" class="form-label">Program Name</label>
                        <input type="text" class="form-control" id="edit_program_name" name="program_name" required>
                        <div class="invalid-feedback">
                            Please enter a program name.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_program_code" class="form-label">Program Code</label>
                        <input type="text" class="form-control" id="edit_program_code" name="program_code" maxlength="10" required>
                        <div class="invalid-feedback">
                            Please enter a program code.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_short_code" class="form-label">Short Code <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control" id="edit_short_code" name="short_code" maxlength="10">
                        <div class="form-text">Brief abbreviation for the program (e.g., "BSC", "MSC"). If not provided, program name will be used.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_department_id" class="form-label">Department</label>
                        <select class="form-select" id="edit_department_id" name="department_id" required>
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
                        <div class="invalid-feedback">
                            Please select a department.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
function editProgram(id, name, code, shortCode, departmentId) {
    document.getElementById('edit_program_id').value = id;
    document.getElementById('edit_program_name').value = name;
    document.getElementById('edit_program_code').value = code;
    document.getElementById('edit_short_code').value = shortCode || '';
    document.getElementById('edit_department_id').value = departmentId;
    new bootstrap.Modal(document.getElementById('editProgramModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
