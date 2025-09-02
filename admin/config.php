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
        $registration_open = isset($_POST['registration_open']) ? 1 : 0;
        $current_semester = sanitizeInput($_POST['current_semester']);
        $current_year = (int)$_POST['current_year'];
        
        if (empty($current_semester) || $current_year < 2020 || $current_year > 2030) {
            $error_message = 'Please provide valid semester and year information.';
        } else {
            // Check if config exists
            $check_query = "SELECT id FROM config LIMIT 1";
            $check_result = $db->query($check_query);
            
            if ($check_result->num_rows > 0) {
                // Update existing config
                $query = "UPDATE config SET registration_open = ?, current_semester = ?, current_year = ?, last_updated = CURRENT_TIMESTAMP";
                $stmt = $db->prepare($query);
                $stmt->bind_param('isi', $registration_open, $current_semester, $current_year);
            } else {
                // Insert new config
                $query = "INSERT INTO config (registration_open, current_semester, current_year) VALUES (?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->bind_param('isi', $registration_open, $current_semester, $current_year);
            }
            
            if ($stmt->execute()) {
                $message = 'System configuration updated successfully!';
            } else {
                $error_message = 'Failed to update configuration. Please try again.';
            }
        }
    }
}

// Get current configuration
$current_config = getCurrentSemester($db);

$page_title = 'System Configuration';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">System Configuration</h1>
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

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cog me-2"></i>Semester & Registration Settings
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="current_semester" class="form-label">Current Semester</label>
                                <select class="form-select" id="current_semester" name="current_semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="Spring" <?php echo $current_config['current_semester'] === 'Spring' ? 'selected' : ''; ?>>Spring</option>
                                    <option value="Summer" <?php echo $current_config['current_semester'] === 'Summer' ? 'selected' : ''; ?>>Summer</option>
                                    <option value="Fall" <?php echo $current_config['current_semester'] === 'Fall' ? 'selected' : ''; ?>>Fall</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a semester.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="current_year" class="form-label">Current Year</label>
                                <input type="number" class="form-control" id="current_year" name="current_year" 
                                       min="2020" max="2030" value="<?php echo $current_config['current_year']; ?>" required>
                                <div class="invalid-feedback">
                                    Please enter a valid year.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="registration_open" name="registration_open" 
                                   <?php echo $current_config['registration_open'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="registration_open">
                                <strong>Enable Student Registration</strong>
                            </label>
                            <div class="form-text">
                                When enabled, students can register for courses. When disabled, registration is closed.
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Current Status
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Active Semester:</strong><br>
                    <span class="badge bg-info fs-6">
                        <?php echo $current_config['current_semester'] . ' ' . $current_config['current_year']; ?>
                    </span>
                </div>
                
                <div class="mb-3">
                    <strong>Registration Status:</strong><br>
                    <?php if ($current_config['registration_open']): ?>
                        <span class="badge bg-success fs-6">
                            <i class="fas fa-check-circle me-1"></i>Open
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6">
                            <i class="fas fa-times-circle me-1"></i>Closed
                        </span>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <div class="alert alert-info alert-custom">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Note:</strong> Changes to these settings will immediately affect the system. Students will only be able to register when registration is enabled.
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-tasks me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="sections.php" class="btn btn-outline-primary">
                        <i class="fas fa-layer-group me-2"></i>Manage Sections
                    </a>
                    <a href="students.php" class="btn btn-outline-success">
                        <i class="fas fa-user-graduate me-2"></i>Manage Students
                    </a>
                    <a href="faculty.php" class="btn btn-outline-info">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Manage Faculty
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
