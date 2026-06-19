<?php
require_once __DIR__ . '/admin-header.php';

$success_message = "";
$error_message = "";

$action = $_GET['action'] ?? 'list';
$landmark_id = intval($_GET['id'] ?? 0);

// Fetch Hotel Coordinates from settings
try {
    $hotel_lat = floatval($pdo->query("SELECT value_en FROM settings WHERE key_name = 'hotel_latitude'")->fetchColumn() ?: '7.8969000');
    $hotel_lng = floatval($pdo->query("SELECT value_en FROM settings WHERE key_name = 'hotel_longitude'")->fetchColumn() ?: '98.2966000');
} catch (Exception $e) {
    $hotel_lat = 7.8969000;
    $hotel_lng = 98.2966000;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_en = trim($_POST['name_en'] ?? '');
    $name_th = trim($_POST['name_th'] ?? '');
    $distance = floatval($_POST['distance_km'] ?? 1.0);
    $map_top = floatval($_POST['map_top_percent'] ?? $hotel_lat); // stores latitude
    $map_left = floatval($_POST['map_left_percent'] ?? $hotel_lng); // stores longitude
    
    if (empty($name_en) || empty($name_th)) {
        $error_message = "Please enter both English and Thai names.";
    } else {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO landmarks (name_en, name_th, distance_km, map_top_percent, map_left_percent, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name_en, $name_th, $distance, $map_top, $map_left, $map_top, $map_left]);
                $success_message = "Landmark added successfully!";
                $action = 'list';
            } elseif ($action === 'edit' && $landmark_id > 0) {
                // Get old name first to update rooms table as well (cascade link)
                $old_name_stmt = $pdo->prepare("SELECT name_en FROM landmarks WHERE id = ?");
                $old_name_stmt->execute([$landmark_id]);
                $old_name = $old_name_stmt->fetchColumn();
                
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("UPDATE landmarks SET name_en = ?, name_th = ?, distance_km = ?, map_top_percent = ?, map_left_percent = ?, latitude = ?, longitude = ? WHERE id = ?");
                $stmt->execute([$name_en, $name_th, $distance, $map_top, $map_left, $map_top, $map_left, $landmark_id]);
                
                // Keep rooms landmark_name updated
                if ($old_name) {
                    $stmtRoom = $pdo->prepare("UPDATE rooms SET landmark_name = ? WHERE landmark_name = ?");
                    $stmtRoom->execute([$name_en, $old_name]);
                }
                
                $pdo->commit();
                $success_message = "Landmark updated successfully!";
                $action = 'list';
            }
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Handle Delete
if ($action === 'delete' && $landmark_id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM landmarks WHERE id = ?");
        $stmt->execute([$landmark_id]);
        $success_message = "Landmark deleted successfully!";
        $action = 'list';
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch single landmark details if editing
$landmark = null;
if ($action === 'edit' && $landmark_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM landmarks WHERE id = ?");
    $stmt->execute([$landmark_id]);
    $landmark = $stmt->fetch();
    if (!$landmark) {
        $error_message = "Landmark not found.";
        $action = 'list';
    }
}

// Fetch all landmarks for list view
$landmarks = [];
if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM landmarks ORDER BY id ASC");
        $landmarks = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error_message = "Database Error: " . $e->getMessage();
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1 class="elegant-title" style="font-size: 2rem; color: var(--primary-gold);">Landmarks & Map Location Settings</h1>
        <p style="color: var(--text-muted); font-size: 0.85rem;">Position nearby landmarks on the hotel's interactive map using the visual point picker.</p>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="landmarks.php?action=add" class="btn-search" style="display: inline-flex; align-items: center; gap: 10px; width: auto; height: auto; padding: 12px 25px; text-decoration: none;">
            <i class="fas fa-plus"></i> Add Landmark
        </a>
    <?php else: ?>
        <a href="landmarks.php" class="action-btn" style="text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    <?php endif; ?>
</div>

<?php if ($success_message): ?>
    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); padding: 15px; color: var(--success); font-size: 0.9rem; margin-bottom: 25px; border-radius: 4px;">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); padding: 15px; color: var(--danger); font-size: 0.9rem; margin-bottom: 25px; border-radius: 4px;">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- LIST VIEW -->
<?php if ($action === 'list'): ?>
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>English Name</th>
                    <th>Thai Name</th>
                    <th>Distance (km)</th>
                    <th>GPS Coordinates (Lat, Lng)</th>
                    <th style="width: 200px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($landmarks)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px;">No landmarks registered yet.</td>
                    </tr>
                <?php else: foreach ($landmarks as $lm): ?>
                    <tr>
                        <td><?php echo $lm['id']; ?></td>
                        <td style="font-weight: 600; color: var(--text-light);"><?php echo htmlspecialchars($lm['name_en']); ?></td>
                        <td><?php echo htmlspecialchars($lm['name_th']); ?></td>
                        <td><i class="fas fa-route text-gold"></i> <?php echo htmlspecialchars($lm['distance_km']); ?> km</td>
                        <td>Lat: <?php echo htmlspecialchars($lm['latitude']); ?> | Lng: <?php echo htmlspecialchars($lm['longitude']); ?></td>
                        <td style="text-align: center;">
                            <div style="display: flex; justify-content: center; gap: 10px;">
                                <a href="landmarks.php?action=edit&id=<?php echo $lm['id']; ?>" class="action-btn" title="Edit Landmark"><i class="fas fa-edit"></i> Edit</a>
                                <a href="landmarks.php?action=delete&id=<?php echo $lm['id']; ?>" class="action-btn btn-delete" title="Delete Landmark" onclick="return confirm('Are you sure you want to delete this landmark? Rooms linked to it will fallback to default distances.');"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

<!-- ADD / EDIT VIEW -->
<?php elseif ($action === 'add' || $action === 'edit'): 
    $name_en_val = $landmark ? $landmark['name_en'] : '';
    $name_th_val = $landmark ? $landmark['name_th'] : '';
    $distance_val = $landmark ? $landmark['distance_km'] : '1.0';
    $top_val = $landmark ? $landmark['latitude'] : $hotel_lat;
    $left_val = $landmark ? $landmark['longitude'] : $hotel_lng;
?>
    <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px; align-items: start;">
        
        <!-- Form panel -->
        <form action="" method="POST" class="booking-mgmt-card" style="margin-top: 0;">
            <h3 style="font-family: var(--font-serif); color: var(--primary-gold); margin-bottom: 20px;">
                <?php echo ($action === 'add') ? 'Add New Landmark' : 'Edit Landmark: ' . htmlspecialchars($name_en_val); ?>
            </h3>
            
            <div class="admin-form-group">
                <label>English Name</label>
                <input type="text" name="name_en" value="<?php echo htmlspecialchars($name_en_val); ?>" required placeholder="e.g. Patong Beach">
            </div>
            
            <div class="admin-form-group">
                <label>Thai Name (ภาษาไทย)</label>
                <input type="text" name="name_th" value="<?php echo htmlspecialchars($name_th_val); ?>" required placeholder="e.g. หาดป่าตอง">
            </div>

            <div class="admin-form-group">
                <label>Default Distance to Hotel (km)</label>
                <input type="number" step="0.01" name="distance_km" value="<?php echo htmlspecialchars($distance_val); ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div class="admin-form-group" style="margin-bottom: 0;">
                    <label>Latitude (วิกฤตติจูด / ละติจูด)</label>
                    <input type="number" step="0.0000001" id="map_top_percent" name="map_top_percent" value="<?php echo htmlspecialchars($top_val); ?>" readonly style="background: var(--bg-dark); border-color: var(--border-light); cursor: not-allowed;">
                </div>
                <div class="admin-form-group" style="margin-bottom: 0;">
                    <label>Longitude (ลองจิจูด)</label>
                    <input type="number" step="0.0000001" id="map_left_percent" name="map_left_percent" value="<?php echo htmlspecialchars($left_val); ?>" readonly style="background: var(--bg-dark); border-color: var(--border-light); cursor: not-allowed;">
                </div>
            </div>

            <button type="submit" class="btn-search" style="width: 100%; height: auto; padding: 15px;">
                <i class="fas fa-save"></i> Save Landmark Location
            </button>
        </form>

        <!-- Visual Map Point Selector -->
        <div>
            <h3 style="font-family: var(--font-serif); color: var(--primary-gold); margin-bottom: 10px;">Interactive Point Picker</h3>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 20px;">Click anywhere on the map or drag the gold marker to position the landmark. Coordinates will update in the form automatically.</p>
            
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            
            <div class="map-wrapper" style="border-radius: 4px; box-shadow: var(--shadow-premium); overflow: hidden; height: 400px; border: 1px solid var(--border-light);">
                <div id="landmark-selector-map" style="width: 100%; height: 100%; z-index: 10;"></div>
            </div>
        </div>

    </div>

    <!-- JavaScript to Bind Point Picker -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputTop = document.getElementById('map_top_percent');
        const inputLeft = document.getElementById('map_left_percent');
        
        const hotelLat = <?php echo $hotel_lat; ?>;
        const hotelLng = <?php echo $hotel_lng; ?>;
        
        const initLat = parseFloat(inputTop.value) || hotelLat;
        const initLng = parseFloat(inputLeft.value) || hotelLng;
        
        // Initialize Map
        const map = L.map('landmark-selector-map').setView([initLat, initLng], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Add Hotel Marker (Red pointer, fixed)
        const hotelMarker = L.marker([hotelLat, hotelLng]).addTo(map);
        hotelMarker.bindPopup("<b><?php echo addslashes(__t('hero_title')); ?></b><br>Hotel Location").openPopup();

        // Add Draggable Landmark Marker (Gold pointer)
        const markerIcon = L.divIcon({
            className: 'landmark-leaflet-marker',
            html: `<div style="background: #c5a880; width: 18px; height: 18px; border: 3px solid #fff; border-radius: 50%; box-shadow: 0 0 10px rgba(0,0,0,0.5);"></div>`,
            iconSize: [18, 18],
            iconAnchor: [9, 9]
        });

        const landmarkMarker = L.marker([initLat, initLng], {
            draggable: true,
            icon: markerIcon
        }).addTo(map);
        
        // Update fields when marker is dragged
        landmarkMarker.on('dragend', function(e) {
            const position = landmarkMarker.getLatLng();
            inputTop.value = position.lat.toFixed(7);
            inputLeft.value = position.lng.toFixed(7);
        });

        // Click on map to place landmark marker
        map.on('click', function(e) {
            landmarkMarker.setLatLng(e.latlng);
            inputTop.value = e.latlng.lat.toFixed(7);
            inputLeft.value = e.latlng.lng.toFixed(7);
        });
    });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
