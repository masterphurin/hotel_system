<?php
require_once __DIR__ . '/admin-header.php';

$message = "";
$error = "";
$action = $_GET['action'] ?? 'list';
$room_id = intval($_GET['id'] ?? 0);

// Available amenities for checklist
$available_amenities = [
    'wifi' => 'Wi-Fi',
    'pool' => 'Swimming Pool',
    'parking' => 'Free Parking',
    'gym' => 'Fitness Gym',
    'spa' => 'Luxury Spa',
    'room_service' => 'Room Service',
    'mini_bar' => 'Mini Bar',
    'smart_tv' => 'Smart TV',
    'air_con' => 'Air Conditioning',
    'bathtub' => 'Marble Bathtub',
    'private_jacuzzi' => 'Private Jacuzzi',
    'kitchen' => 'Kitchen'
];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';
    
    // 1. CREATE ROOM
    if ($post_action === 'create') {
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $price = floatval($_POST['price_per_night']);
        $size = intval($_POST['size_sqm']);
        $capacity = intval($_POST['capacity']);
        $total = intval($_POST['total_rooms']);
        $desc = trim($_POST['description']);
        $landmark = trim($_POST['landmark_name']);
        $distance = floatval($_POST['distance_landmark']);
        $amts = isset($_POST['amenities']) ? $_POST['amenities'] : [];
        
        $image_path = 'assets/images/room_deluxe.jpg'; // default fallback
        
        // Handle Image Upload
        if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['room_image']['tmp_name'];
            $file_name = $_FILES['room_image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $new_name = 'room_' . uniqid() . '.' . $file_ext;
                $dest = __DIR__ . '/../assets/images/' . $new_name;
                if (move_uploaded_file($file_tmp, $dest)) {
                    $image_path = 'assets/images/' . $new_name;
                }
            }
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO rooms (name, type, price_per_night, size_sqm, capacity, total_rooms, description, image_url, amenities, distance_landmark, landmark_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $name, $type, $price, $size, $capacity, $total, $desc, $image_path, json_encode($amts), $distance, $landmark
            ]);
            $message = "Room created successfully.";
            $action = 'list';
        } catch (PDOException $e) {
            $error = "Error creating room: " . $e->getMessage();
        }
    }
    
    // 2. UPDATE ROOM
    if ($post_action === 'update' && $room_id > 0) {
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $price = floatval($_POST['price_per_night']);
        $size = intval($_POST['size_sqm']);
        $capacity = intval($_POST['capacity']);
        $total = intval($_POST['total_rooms']);
        $desc = trim($_POST['description']);
        $landmark = trim($_POST['landmark_name']);
        $distance = floatval($_POST['distance_landmark']);
        $amts = isset($_POST['amenities']) ? $_POST['amenities'] : [];
        
        try {
            // First, update text fields
            $stmt = $pdo->prepare("UPDATE rooms SET name = ?, type = ?, price_per_night = ?, size_sqm = ?, capacity = ?, total_rooms = ?, description = ?, amenities = ?, distance_landmark = ?, landmark_name = ? WHERE id = ?");
            $stmt->execute([
                $name, $type, $price, $size, $capacity, $total, $desc, json_encode($amts), $distance, $landmark, $room_id
            ]);
            
            // Check if new image was uploaded
            if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['room_image']['tmp_name'];
                $file_name = $_FILES['room_image']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $new_name = 'room_' . uniqid() . '.' . $file_ext;
                    $dest = __DIR__ . '/../assets/images/' . $new_name;
                    if (move_uploaded_file($file_tmp, $dest)) {
                        $image_path = 'assets/images/' . $new_name;
                        // update image path in DB
                        $stmt = $pdo->prepare("UPDATE rooms SET image_url = ? WHERE id = ?");
                        $stmt->execute([$image_path, $room_id]);
                    }
                }
            }
            
            $message = "Room details updated successfully.";
            $action = 'list';
        } catch (PDOException $e) {
            $error = "Error updating room: " . $e->getMessage();
        }
    }
    
    // 3. DELETE ROOM
    if ($post_action === 'delete') {
        $del_id = intval($_POST['room_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$del_id]);
            $message = "Room deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting room: " . $e->getMessage();
        }
    }
}

// Fetch all rooms for listing
$rooms = [];
if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM rooms ORDER BY id DESC");
        $rooms = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Fetch failed: " . $e->getMessage();
    }
}

// Fetch active room details for edit form
$edit_room = null;
if ($action === 'edit' && $room_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $edit_room = $stmt->fetch();
        if (!$edit_room) {
            $error = "Room not found.";
            $action = 'list';
        }
    } catch (PDOException $e) {
        $error = "Fetch failed: " . $e->getMessage();
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1 class="elegant-title" style="font-size: 2rem; color: var(--primary-gold);">Rooms Inventory</h1>
        <p style="color: var(--text-muted); font-size: 0.85rem;">Modify accommodations parameters, add premium suites or control inventory sizes.</p>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="rooms.php?action=add" class="btn-book-nav" style="text-decoration: none;"><i class="fas fa-plus"></i> Add New Room</a>
    <?php else: ?>
        <a href="rooms.php" class="btn-secondary" style="text-decoration: none;"><i class="fas fa-arrow-left"></i> Back to Inventory</a>
    <?php endif; ?>
</div>

<?php if ($message): ?>
    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); padding: 12px; color: var(--success); margin-bottom: 25px;">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); padding: 12px; color: var(--danger); margin-bottom: 25px;">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>


<?php if ($action === 'list'): ?>
    <!-- Rooms Inventory Table -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Room Name</th>
                    <th>Type</th>
                    <th>Price/Night</th>
                    <th>Size (sqm)</th>
                    <th>Max Guests</th>
                    <th>Total Inventory</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rooms) === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">No rooms in database. Create one now.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($rooms as $r): ?>
                    <tr>
                        <td style="width: 100px;">
                            <img src="../<?php echo htmlspecialchars($r['image_url']); ?>" alt="Room" style="width: 80px; height: 50px; object-fit: cover; border: 1px solid var(--border-light);">
                        </td>
                        <td style="font-weight: 600; color: var(--text-light);"><?php echo htmlspecialchars($r['name']); ?></td>
                        <td><span style="color: var(--primary-gold); font-size: 0.75rem; text-transform: uppercase; font-weight: bold;"><?php echo htmlspecialchars($r['type']); ?></span></td>
                        <td style="font-weight: 600; color: var(--primary-gold);">฿<?php echo number_format($r['price_per_night']); ?></td>
                        <td><?php echo htmlspecialchars($r['size_sqm']); ?> sqm</td>
                        <td><?php echo htmlspecialchars($r['capacity']); ?> Guests</td>
                        <td><strong><?php echo htmlspecialchars($r['total_rooms']); ?></strong> Rooms</td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <a href="rooms.php?action=edit&id=<?php echo $r['id']; ?>" class="action-btn" title="Edit Room Details"><i class="fas fa-edit"></i> Edit</a>
                                <form action="" method="POST" onsubmit="return confirm('WARNING: Deleting this room type will also delete all associated bookings and reviews! Proceed?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="room_id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="action-btn btn-delete" title="Delete Room"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'): 
    $is_edit = ($action === 'edit');
    $form_action = $is_edit ? 'update' : 'create';
    $title_label = $is_edit ? 'Edit Room' : 'Add New Room';
    
    // Set default values for variables
    $name_val = $is_edit ? $edit_room['name'] : '';
    $type_val = $is_edit ? $edit_room['type'] : 'Deluxe';
    $price_val = $is_edit ? $edit_room['price_per_night'] : '3500.00';
    $size_val = $is_edit ? $edit_room['size_sqm'] : '40';
    $capacity_val = $is_edit ? $edit_room['capacity'] : '2';
    $total_val = $is_edit ? $edit_room['total_rooms'] : '5';
    $desc_val = $is_edit ? $edit_room['description'] : '';
    $landmark_val = $is_edit ? $edit_room['landmark_name'] : 'Patong Beach';
    $distance_val = $is_edit ? $edit_room['distance_landmark'] : '0.50';
    $selected_amts = $is_edit ? (json_decode($edit_room['amenities'], true) ?: []) : ['wifi', 'parking', 'air_con'];
?>
    <!-- Create / Edit Form Layout -->
    <div class="booking-mgmt-card" style="margin-top: 20px; max-width: 800px; margin-left: auto; margin-right: auto;">
        <h3 style="font-family: var(--font-serif); color: var(--primary-gold); font-size: 1.4rem; margin-bottom: 25px; border-bottom: 1px solid var(--border-light); padding-bottom: 10px;"><?php echo $title_label; ?></h3>
        
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $form_action; ?>">
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div class="admin-form-group">
                    <label>Room Name / Title</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($name_val); ?>" required placeholder="e.g. Deluxe Ocean View Suite">
                </div>
                <div class="admin-form-group">
                    <label>Room Type</label>
                    <select name="type">
                        <option value="Deluxe" <?php echo ($type_val === 'Deluxe') ? 'selected' : ''; ?>>Deluxe Room</option>
                        <option value="Suite" <?php echo ($type_val === 'Suite') ? 'selected' : ''; ?>>Executive Suite</option>
                        <option value="Penthouse" <?php echo ($type_val === 'Penthouse') ? 'selected' : ''; ?>>Royal Penthouse</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                <div class="admin-form-group">
                    <label>Price/Night (THB)</label>
                    <input type="number" step="100" name="price_per_night" value="<?php echo htmlspecialchars($price_val); ?>" required>
                </div>
                <div class="admin-form-group">
                    <label>Size (sqm)</label>
                    <input type="number" name="size_sqm" value="<?php echo htmlspecialchars($size_val); ?>" required>
                </div>
                <div class="admin-form-group">
                    <label>Max Guests</label>
                    <input type="number" name="capacity" value="<?php echo htmlspecialchars($capacity_val); ?>" required>
                </div>
                <div class="admin-form-group">
                    <label>Total Rooms Count</label>
                    <input type="number" name="total_rooms" value="<?php echo htmlspecialchars($total_val); ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div class="admin-form-group">
                    <label>Nearby Landmark Name</label>
                    <select name="landmark_name" required style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 12px; outline: none; width: 100%;">
                        <?php
                        try {
                            $lm_stmt = $pdo->query("SELECT name_en, name_th FROM landmarks ORDER BY id ASC");
                            while ($lm = $lm_stmt->fetch()) {
                                $selected = ($landmark_val === $lm['name_en'] || $landmark_val === $lm['name_th']) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($lm['name_en']) . '" ' . $selected . '>' . htmlspecialchars($lm['name_en']) . ' (' . htmlspecialchars($lm['name_th']) . ')</option>';
                            }
                        } catch (Exception $e) {
                            echo '<option value="Patong Beach">Patong Beach</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label>Distance to Landmark (km)</label>
                    <input type="number" step="0.01" name="distance_landmark" value="<?php echo htmlspecialchars($distance_val); ?>" required>
                </div>
            </div>

            <div class="admin-form-group">
                <label>Room Description</label>
                <textarea name="description" rows="5" required placeholder="Describe the room, beds, bathrooms, view..."><?php echo htmlspecialchars($desc_val); ?></textarea>
            </div>

            <div class="admin-form-group" style="margin-bottom: 25px;">
                <label>Select Room Amenities</label>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 5px;">
                    <?php foreach ($available_amenities as $key => $label): ?>
                        <label class="checkbox-label" style="font-size: 0.8rem;">
                            <input type="checkbox" name="amenities[]" value="<?php echo $key; ?>" <?php echo in_array($key, $selected_amts) ? 'checked' : ''; ?>>
                            <?php echo $label; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="admin-form-group" style="margin-bottom: 30px;">
                <label>Room Cover Photo</label>
                <?php if ($is_edit && $edit_room['image_url']): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="../<?php echo htmlspecialchars($edit_room['image_url']); ?>" alt="Current Cover" style="width: 150px; height: 90px; object-fit: cover; border: 1px solid var(--border-light);">
                        <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Current cover image</span>
                    </div>
                <?php endif; ?>
                <input type="file" name="room_image" accept="image/*" style="border: 1px dashed var(--border-light); padding: 10px; color: var(--text-muted);">
            </div>

            <div style="display: flex; gap: 15px;">
                <button type="submit" class="btn-search" style="height: auto; padding: 15px 30px; font-size: 0.85rem;">
                    <?php echo $is_edit ? 'Save Changes' : 'Create Room Type'; ?>
                </button>
                <a href="rooms.php" class="btn-secondary" style="display: flex; align-items: center; justify-content: center; text-decoration: none; padding: 15px 30px;">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
