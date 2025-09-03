<?php
require_once '../includes/config.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';
require_once '../includes/admin_dependencies.php';

checkUserType('admin');

$message = '';
$error_message = '';

// Check dependencies
$dependencies = checkPageSpecificDependencies($db, 'rooms');
$can_add_rooms = hasRequiredDependencies($db, 'rooms');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $room_number = sanitizeInput($_POST['room_number']);
            $building = sanitizeInput($_POST['building']);
            $capacity = (int)$_POST['capacity'];
            $room_type = sanitizeInput($_POST['room_type']);
            
            if (empty($room_number) || empty($capacity)) {
                $error_message = 'Room number and capacity are required.';
            } else {
                $query = "INSERT INTO rooms (room_number, building, capacity, room_type) VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->bind_param('ssis', $room_number, $building, $capacity, $room_type);
                
                if ($stmt->execute()) {
                    $message = 'Room added successfully!';
                } else {
                    if ($stmt->errno === 1062) {
                        $error_message = 'Room number already exists.';
                    } else {
                        $error_message = 'Failed to add room. Please try again.';
                    }
                }
            }
        } elseif ($action === 'edit') {
            $room_id = (int)$_POST['room_id'];
            $room_number = sanitizeInput($_POST['room_number']);
            $building = sanitizeInput($_POST['building']);
            $capacity = (int)$_POST['capacity'];
            $room_type = sanitizeInput($_POST['room_type']);
            
            if (empty($room_number) || empty($capacity)) {
                $error_message = 'Room number and capacity are required.';
            } else {
                $query = "UPDATE rooms SET room_number = ?, building = ?, capacity = ?, room_type = ? WHERE room_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param('ssisi', $room_number, $building, $capacity, $room_type, $room_id);
                
                if ($stmt->execute()) {
                    $message = 'Room updated successfully!';
                } else {
                    if ($stmt->errno === 1062) {
                        $error_message = 'Room number already exists.';
                    } else {
                        $error_message = 'Failed to update room. Please try again.';
                    }
                }
            }
        } elseif ($action === 'delete') {
            $room_id = (int)$_POST['room_id'];
            
            // Check if room is assigned to any sections
            $query = "SELECT COUNT(*) as count FROM sections WHERE room_id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('i', $room_id);
            $stmt->execute();
            $section_count = $stmt->get_result()->fetch_assoc()['count'];
            
            if ($section_count > 0) {
                $error_message = 'Cannot delete room. It is assigned to sections.';
            } else {
                $query = "DELETE FROM rooms WHERE room_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param('i', $room_id);
                
                if ($stmt->execute()) {
                    $message = 'Room deleted successfully!';
                } else {
                    $error_message = 'Failed to delete room. Please try again.';
                }
            }
        }
    }
}

// Get all rooms with usage statistics
$query = "SELECT r.*, 
          (SELECT COUNT(*) FROM sections WHERE room_id = r.room_id) as section_count
          FROM rooms r 
          ORDER BY r.building, r.room_number";
$rooms = $db->query($query);

$page_title = 'Manage Rooms';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Rooms</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($can_add_rooms): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
            <i class="fas fa-plus me-2"></i>Add Room
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($can_add_rooms): ?>
    <!-- Main Room Management Interface -->
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

<?php if ($can_add_rooms): ?>
    <div class="card-body">
        <?php if ($rooms->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Room Number</th>
                            <th>Building</th>
                            <th>Capacity</th>
                            <th>Type</th>
                            <th>Sections</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($room = $rooms->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $room['room_id']; ?></td>
                            <td><strong><?php echo sanitizeInput($room['room_number']); ?></strong></td>
                            <td><?php echo sanitizeInput($room['building']); ?></td>
                            <td><?php echo $room['capacity']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $room['room_type'] === 'lab' ? 'warning' : ($room['room_type'] === 'both' ? 'info' : 'secondary'); ?>">
                                    <?php echo ucfirst($room['room_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo $room['section_count']; ?></span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($room['created_at'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editRoom(<?php echo $room['room_id']; ?>, '<?php echo addslashes($room['room_number']); ?>', '<?php echo addslashes($room['building']); ?>', <?php echo $room['capacity']; ?>, '<?php echo $room['room_type']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this room?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
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
                <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No rooms found</h5>
                <p class="text-muted">Click "Add Room" to create your first room.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    <i class="fas fa-plus me-2"></i>Add Room
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Add New Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="room_number" class="form-label">Room Number</label>
                        <input type="text" class="form-control" id="room_number" name="room_number" required>
                        <div class="invalid-feedback">
                            Please enter a room number.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="building" class="form-label">Building</label>
                        <input type="text" class="form-control" id="building" name="building">
                        <div class="form-text">Optional building name or identifier.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacity</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                        <div class="invalid-feedback">
                            Please enter room capacity.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="room_type" class="form-label">Room Type</label>
                        <select class="form-select" id="room_type" name="room_type" required>
                            <option value="">Select Type</option>
                            <option value="classroom">Classroom</option>
                            <option value="lab">Lab</option>
                            <option value="both">Both</option>
                        </select>
                        <div class="invalid-feedback">
                            Please select a room type.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_room_id" name="room_id">
                    
                    <div class="mb-3">
                        <label for="edit_room_number" class="form-label">Room Number</label>
                        <input type="text" class="form-control" id="edit_room_number" name="room_number" required>
                        <div class="invalid-feedback">
                            Please enter a room number.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_building" class="form-label">Building</label>
                        <input type="text" class="form-control" id="edit_building" name="building">
                        <div class="form-text">Optional building name or identifier.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_capacity" class="form-label">Capacity</label>
                        <input type="number" class="form-control" id="edit_capacity" name="capacity" min="1" required>
                        <div class="invalid-feedback">
                            Please enter room capacity.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_room_type" class="form-label">Room Type</label>
                        <select class="form-select" id="edit_room_type" name="room_type" required>
                            <option value="">Select Type</option>
                            <option value="classroom">Classroom</option>
                            <option value="lab">Lab</option>
                            <option value="both">Both</option>
                        </select>
                        <div class="invalid-feedback">
                            Please select a room type.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
function editRoom(id, roomNumber, building, capacity, roomType) {
    document.getElementById('edit_room_id').value = id;
    document.getElementById('edit_room_number').value = roomNumber;
    document.getElementById('edit_building').value = building;
    document.getElementById('edit_capacity').value = capacity;
    document.getElementById('edit_room_type').value = roomType;
    new bootstrap.Modal(document.getElementById('editRoomModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
