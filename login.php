<?php
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

$error_message = '';
$success_message = '';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_type']) {
        case 'student':
            header('Location: ' . BASE_URL . 'student/dashboard.php');
            break;
        case 'faculty':
            header('Location: ' . BASE_URL . 'faculty/dashboard.php');
            break;
        case 'admin':
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
            break;
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $user_type = sanitizeInput($_POST['user_type']);
    
    if (empty($email) || empty($password) || empty($user_type)) {
        $error_message = 'Please fill in all fields.';
    } else {
        // Determine which table to check based on user type
        $table = '';
        $id_field = '';
        $name_field = 'name';
        
        switch ($user_type) {
            case 'student':
                $table = 'students';
                $id_field = 'student_id';
                break;
            case 'faculty':
                $table = 'faculty';
                $id_field = 'faculty_id';
                break;
            case 'admin':
                $table = 'admins';
                $id_field = 'admin_id';
                $name_field = 'username';
                break;
            default:
                $error_message = 'Invalid user type.';
        }
        
        if (empty($error_message)) {
            // Check credentials
            $query = "SELECT $id_field, $name_field, password_hash FROM $table WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password_hash'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user[$id_field];
                    $_SESSION['user_name'] = $user[$name_field];
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['user_email'] = $email;
                    
                    // Redirect to appropriate dashboard
                    switch ($user_type) {
                        case 'student':
                            header('Location: ' . BASE_URL . 'student/dashboard.php');
                            break;
                        case 'faculty':
                            header('Location: ' . BASE_URL . 'faculty/dashboard.php');
                            break;
                        case 'admin':
                            header('Location: ' . BASE_URL . 'admin/dashboard.php');
                            break;
                    }
                    exit();
                } else {
                    $error_message = 'Invalid email or password.';
                }
            } else {
                $error_message = 'Invalid email or password.';
            }
        }
    }
}

$page_title = 'Login';
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-graduation-cap fa-3x text-primary mb-3"></i>
                    <h3>EWU Course Registration</h3>
                    <p class="text-muted">Faculty Management System</p>
                </div>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-custom">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-custom">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="user_type" class="form-label">Login as</label>
                        <select class="form-select" id="user_type" name="user_type" required>
                            <option value="">Select user type</option>
                            <option value="student" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'student') ? 'selected' : ''; ?>>Student</option>
                            <option value="faculty" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'faculty') ? 'selected' : ''; ?>>Faculty</option>
                            <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                        <div class="invalid-feedback">
                            Please select a user type.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? sanitizeInput($_POST['email']) : ''; ?>" 
                               required>
                        <div class="invalid-feedback">
                            Please enter a valid email address.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">
                            Please enter your password.
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-muted">
                For support, contact the system administrator.
            </small>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
