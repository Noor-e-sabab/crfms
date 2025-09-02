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
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            
            if (empty($username) || empty($email) || empty($password)) {
                $error_message = 'All fields are required.';
            } elseif (strlen($password) < 6) {
                $error_message = 'Password must be at least 6 characters long.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Please enter a valid email address.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO admins (username, email, password_hash) VALUES (?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->bind_param('sss', $username, $email, $password_hash);
                
                if ($stmt->execute()) {
                    $message = 'Admin added successfully!';
                } else {
                    if ($stmt->errno === 1062) {
                        $error_message = 'Username or email already exists.';
                    } else {
                        $error_message = 'Failed to add admin. Please try again.';
                    }
                }
            }
        } elseif ($action === 'edit') {
            $admin_id = (int)$_POST['admin_id'];
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            
            if (empty($username) || empty($email)) {
                $error_message = 'Username and email are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Please enter a valid email address.';
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error_message = 'Password must be at least 6 characters long.';
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $query = "UPDATE admins SET username = ?, email = ?, password_hash = ? WHERE admin_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param('sssi', $username, $email, $password_hash, $admin_id);
                    }
                } else {
                    $query = "UPDATE admins SET username = ?, email = ? WHERE admin_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('ssi', $username, $email, $admin_id);
                }
                
                if (empty($error_message)) {
                    if ($stmt->execute()) {
                        $message = 'Admin updated successfully!';
                    } else {
                        if ($stmt->errno === 1062) {
                            $error_message = 'Username or email already exists.';
                        } else {
                            $error_message = 'Failed to update admin. Please try again.';
                        }
                    }
                }
            }
        } elseif ($action === 'delete') {
            $admin_id = (int)$_POST['admin_id'];
            
            // Prevent deleting current admin
            if ($admin_id == $_SESSION['user_id']) {
                $error_message = 'You cannot delete your own account.';
            } else {
                // Check if this is the last admin
                $count_query = "SELECT COUNT(*) as count FROM admins";
                $count_result = $db->query($count_query);
                $admin_count = $count_result->fetch_assoc()['count'];
                
                if ($admin_count <= 1) {
                    $error_message = 'Cannot delete the last admin account.';
                } else {
                    $query = "DELETE FROM admins WHERE admin_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param('i', $admin_id);
                    
                    if ($stmt->execute()) {
                        $message = 'Admin deleted successfully!';
                    } else {
                        $error_message = 'Failed to delete admin. Please try again.';
                    }
                }
            }
        }
    }
}

// Get all admins
$query = "SELECT * FROM admins ORDER BY created_at DESC";
$admins = $db->query($query);

$page_title = 'Manage Admins';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Admins</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="fas fa-plus me-2"></i>Add Admin
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
        <?php if ($admins->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($admin = $admins->fetch_assoc()): ?>
                        <tr <?php echo $admin['admin_id'] == $_SESSION['user_id'] ? 'class="table-info"' : ''; ?>>
                            <td><?php echo $admin['admin_id']; ?></td>
                            <td>
                                <strong><?php echo sanitizeInput($admin['username']); ?></strong>
                                <?php if ($admin['admin_id'] == $_SESSION['user_id']): ?>
                                    <span class="badge bg-primary ms-2">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo sanitizeInput($admin['email']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($admin['created_at'])); ?></td>
                            <td>
                                <span class="badge bg-success">Active</span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editAdmin(<?php echo $admin['admin_id']; ?>, '<?php echo addslashes($admin['username']); ?>', '<?php echo addslashes($admin['email']); ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <?php if ($admin['admin_id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this admin?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['admin_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete your own account">
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
                <i class="fas fa-user-shield fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No admins found</h5>
                <p class="text-muted">This should not happen. Please contact support.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Add New Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="invalid-feedback">
                            Please enter a username.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">
                            Please enter a valid email address.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                        <div class="invalid-feedback">
                            Password must be at least 6 characters long.
                        </div>
                        <div class="form-text">
                            Password should be at least 6 characters long.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_admin_id" name="admin_id">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                        <div class="invalid-feedback">
                            Please enter a username.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                        <div class="invalid-feedback">
                            Please enter a valid email address.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                        <div class="invalid-feedback">
                            Password must be at least 6 characters long.
                        </div>
                        <div class="form-text">
                            Leave blank to keep current password. Minimum 6 characters if changing.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editAdmin(id, username, email) {
    document.getElementById('edit_admin_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_password').value = '';
    new bootstrap.Modal(document.getElementById('editAdminModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
