<?php
require_once __DIR__ . '/admin-header.php';

$success_message = "";
$error_message = "";

// Handle Configuration Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        if (isset($_POST['settings']) && is_array($_POST['settings'])) {
            $stmt = $pdo->prepare("UPDATE settings SET value_en = ?, value_th = ? WHERE key_name = ?");
            foreach ($_POST['settings'] as $key => $values) {
                $val_en = trim($values['en'] ?? '');
                $val_th = trim($values['th'] ?? '');
                $stmt->execute([$val_en, $val_th, $key]);
            }
        }
        
        // Handle Hero Background Image upload
        if (isset($_FILES['hero_bg_image']) && $_FILES['hero_bg_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['hero_bg_image']['tmp_name'];
            $file_name = basename($_FILES['hero_bg_image']['name']);
            // Sanitise file name
            $file_name = preg_replace("/[^a-zA-Z0-9\._-]/", "", $file_name);
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowed_exts)) {
                throw new Exception("Invalid file type. Only JPG, PNG, WEBP, and GIF are allowed.");
            }
            
            $upload_dir = __DIR__ . '/../assets/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $new_name = 'uploaded_hero_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $new_name;
            $db_path = 'assets/images/' . $new_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Update hero background image path in settings
                $stmtImage = $pdo->prepare("UPDATE settings SET value_en = ?, value_th = ? WHERE key_name = 'hero_bg_image'");
                $stmtImage->execute([$db_path, $db_path]);
            } else {
                throw new Exception("Failed to save the uploaded image file.");
            }
        }
        
        $pdo->commit();
        $success_message = "Homepage configurations updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error saving settings: " . $e->getMessage();
    }
}

// Fetch current configurations sorted by category
try {
    $stmt = $pdo->query("SELECT * FROM settings ORDER BY category, id ASC");
    $settings = $stmt->fetchAll();
    
    // Group settings by category
    $grouped_settings = [];
    foreach ($settings as $s) {
        $grouped_settings[$s['category']][] = $s;
    }
} catch (PDOException $e) {
    die("Error retrieving settings from database: " . $e->getMessage());
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1 class="elegant-title" style="font-size: 2rem; color: var(--primary-gold);">Homepage Settings</h1>
        <p style="color: var(--text-muted); font-size: 0.85rem;">Modify frontpage banners, slides, philosophy, services, and testimonial sections without code.</p>
    </div>
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

<form action="" method="POST" enctype="multipart/form-data" class="booking-mgmt-card" style="margin-top: 20px;">
    <!-- Tab Controls -->
    <div style="display: flex; border-bottom: 1px solid var(--border-light); margin-bottom: 30px; gap: 5px; overflow-x: auto;">
        <button type="button" class="tab-btn active" onclick="switchSettingsTab(event, 'tab-hero')" style="background: transparent; border: none; border-bottom: 2px solid var(--primary-gold); padding: 12px 25px; color: var(--primary-gold); font-weight: 600; cursor: pointer; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap;">Hero Banner</button>
        <button type="button" class="tab-btn" onclick="switchSettingsTab(event, 'tab-philosophy')" style="background: transparent; border: none; border-bottom: 2px solid transparent; padding: 12px 25px; color: var(--text-muted); font-weight: 600; cursor: pointer; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap;">Philosophy</button>
        <button type="button" class="tab-btn" onclick="switchSettingsTab(event, 'tab-services')" style="background: transparent; border: none; border-bottom: 2px solid transparent; padding: 12px 25px; color: var(--text-muted); font-weight: 600; cursor: pointer; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap;">Services</button>
        <button type="button" class="tab-btn" onclick="switchSettingsTab(event, 'tab-testimonials')" style="background: transparent; border: none; border-bottom: 2px solid transparent; padding: 12px 25px; color: var(--text-muted); font-weight: 600; cursor: pointer; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap;">Testimonials</button>
        <button type="button" class="tab-btn" onclick="switchSettingsTab(event, 'tab-general')" style="background: transparent; border: none; border-bottom: 2px solid transparent; padding: 12px 25px; color: var(--text-muted); font-weight: 600; cursor: pointer; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap;">General & Map</button>
    </div>

    <!-- Tab: Hero Banner -->
    <div id="tab-hero" class="settings-tab-content">
        <h3 style="font-family: var(--font-serif); color: var(--primary-gold); margin-bottom: 20px;">Main Hero & Slides settings</h3>
        
        <!-- Background Image Picker -->
        <div class="admin-form-group" style="margin-bottom: 30px; border-bottom: 1px dashed var(--border-light); padding-bottom: 30px;">
            <label>Hero Background Image</label>
            <div style="display: flex; align-items: center; gap: 20px; margin-top: 10px;">
                <?php 
                $bg_img = "";
                foreach ($settings as $s) {
                    if ($s['key_name'] === 'hero_bg_image') {
                        $bg_img = $s['value_en'];
                        break;
                    }
                }
                ?>
                <img src="../<?php echo htmlspecialchars($bg_img); ?>" style="width: 150px; height: 90px; object-fit: cover; border: 1px solid var(--border-light); border-radius: 4px;" alt="Hero Preview">
                <div>
                    <input type="file" name="hero_bg_image" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-muted); padding: 8px; font-size: 0.8rem; width: 300px;">
                    <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 5px;">Upload a high-resolution image (Recommended 1920x1080 px). JPG, PNG or WEBP.</p>
                </div>
            </div>
        </div>

        <?php if (isset($grouped_settings['hero'])): foreach ($grouped_settings['hero'] as $s): if ($s['type'] === 'image') continue; ?>
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-light); padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                <h4 style="color: var(--primary-gold); font-size: 0.85rem; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-edit"></i> Key: <?php echo htmlspecialchars($s['key_name']); ?></h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="admin-form-group" style="margin-bottom: 0;">
                        <label>English Value</label>
                        <?php if ($s['type'] === 'textarea'): ?>
                            <textarea name="settings[<?php echo $s['key_name']; ?>][en]" rows="3" required><?php echo htmlspecialchars($s['value_en']); ?></textarea>
                        <?php else: ?>
                            <input type="text" name="settings[<?php echo $s['key_name']; ?>][en]" value="<?php echo htmlspecialchars($s['value_en']); ?>" required>
                        <?php endif; ?>
                    </div>
                    <div class="admin-form-group" style="margin-bottom: 0;">
                        <label>Thai Value (ภาษาไทย)</label>
                        <?php if ($s['type'] === 'textarea'): ?>
                            <textarea name="settings[<?php echo $s['key_name']; ?>][th]" rows="3" required><?php echo htmlspecialchars($s['value_th']); ?></textarea>
                        <?php else: ?>
                            <input type="text" name="settings[<?php echo $s['key_name']; ?>][th]" value="<?php echo htmlspecialchars($s['value_th']); ?>" required>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Tab: Philosophy -->
    <div id="tab-philosophy" class="settings-tab-content" style="display: none;">
        <h3 style="font-family: var(--font-serif); color: var(--primary-gold); margin-bottom: 20px;">Our Philosophy Section Settings</h3>
        <?php if (isset($grouped_settings['philosophy'])): foreach ($grouped_settings['philosophy'] as $s): ?>
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-light); padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                <h4 style="color: var(--primary-gold); font-size: 0.85rem; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-edit"></i> Key: <?php echo htmlspecialchars($s['key_name']); ?></h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="admin-form-group" style="margin-bottom: 0;">
                        <label>English Value</label>
                        <?php if ($s['type'] === 'textarea'): ?>
                            <textarea name="settings[<?php echo $s['key_name']; ?>][en]" rows="4" required><?php echo htmlspecialchars($s['value_en']); ?></textarea>
                        <?php else: ?>
                            <input type="text" name="settings[<?php echo $s['key_name']; ?>][en]" value="<?php echo htmlspecialchars($s['value_en']); ?>" required>
                        <?php endif; ?>
                    </div>
                    <div class="admin-form-group" style="margin-bottom: 0;">
                        <label>Thai Value (ภาษาไทย)</label>
                        <?php if ($s['type'] === 'textarea'): ?>
                            <textarea name="settings[<?php echo $s['key_name']; ?>][th]" rows="4" required><?php echo htmlspecialchars($s['value_th']); ?></textarea>
                        <?php else: ?>
                            <input type="text" name="settings[<?php echo $s['key_name']; ?>][th]" value="<?php echo htmlspecialchars($s['value_th']); ?>" required>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Tab: Services -->
    <div id="tab-services" class="settings-tab-content" style="display: none;">
        <h3 style="font-family: var(--font-serif); color: var(--primary-gold); margin-bottom: 20px;">Signature Services Section Settings</h3>
        <?php if (isset($grouped_settings['services'])): foreach ($grouped_settings['services'] as $s): ?>
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-light); padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                <h4 style="color: var(--primary-gold); font-size: 0.85rem; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-edit"></i> Key: <?php echo htmlspecialchars($s['key_name']); ?></h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="admin-form-group" style="margin-bottom: 0;">
                        <label>English Value</label>
                        <?php if ($s['type'] === 'textarea'): ?>
                            <textarea name="settings[<?php echo $s['key_name']; ?>][en]" rows="3" required><?php echo htmlspecialchars($s['value_en']); ?></textarea>
                        <?php else: ?>
                            <input type="text" name="settings[<?php echo $s['key_name']; ?>][en]" value="<?php echo htmlspecialchars($s['value_en']); ?>" required>
                        <?php endif; ?>
                    </div>
                    <div class="admin-form-group" style="margin-bottom: 0;">
                        <label>Thai Value (ภาษาไทย)</label>
                        <?php if ($s['type'] === 'textarea'): ?>
                            <textarea name="settings[<?php echo $s['key_name']; ?>][th]" rows="3" required><?php echo htmlspecialchars($s['value_th']); ?></textarea>
                        <?php else: ?>
                            <input type="text" name="settings[<?php echo $s['key_name']; ?>][th]" value="<?php echo htmlspecialchars($s['value_th']); ?>" required>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Tab: Testimonials -->
    <div id="tab-testimonials" class="settings-tab-content" style="display: none;">
        <h3 style="font-family: var(--font-serif); color: var(--primary-gold); margin-bottom: 20px;">Guest Testimonials Section Settings</h3>
        <?php if (isset($grouped_settings['testimonials'])): foreach ($grouped_settings['testimonials'] as $s): ?>
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-light); padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                <h4 style="color: var(--primary-gold); font-size: 0.85rem; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-edit"></i> Key: <?php echo htmlspecialchars($s['key_name']); ?></h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="admin-form-group" style="margin-bottom: 0;">
                        <label>English Value</label>
                        <?php if ($s['type'] === 'textarea'): ?>
                            <textarea name="settings[<?php echo $s['key_name']; ?>][en]" rows="4" required><?php echo htmlspecialchars($s['value_en']); ?></textarea>
                        <?php else: ?>
                            <input type="text" name="settings[<?php echo $s['key_name']; ?>][en]" value="<?php echo htmlspecialchars($s['value_en']); ?>" required>
                        <?php endif; ?>
                    </div>
                    <div class="admin-form-group" style="margin-bottom: 0;">
                        <label>Thai Value (ภาษาไทย)</label>
                        <?php if ($s['type'] === 'textarea'): ?>
                            <textarea name="settings[<?php echo $s['key_name']; ?>][th]" rows="4" required><?php echo htmlspecialchars($s['value_th']); ?></textarea>
                        <?php else: ?>
                            <input type="text" name="settings[<?php echo $s['key_name']; ?>][th]" value="<?php echo htmlspecialchars($s['value_th']); ?>" required>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Tab: General Coordinates -->
    <div id="tab-general" class="settings-tab-content" style="display: none;">
        <h3 style="font-family: var(--font-serif); color: var(--primary-gold); margin-bottom: 20px;">General Hotel Coordinates</h3>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 20px;">Enter the real-world GPS coordinates for the hotel location. These coordinates serve as the center point of the interactive map on the room details page.</p>
        
        <?php if (isset($grouped_settings['general'])): foreach ($grouped_settings['general'] as $s): ?>
            <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-light); padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                <h4 style="color: var(--primary-gold); font-size: 0.85rem; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-edit"></i> Key: <?php echo htmlspecialchars($s['key_name']); ?></h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="admin-form-group" style="margin-bottom: 0;">
                        <label>English Value</label>
                        <input type="text" name="settings[<?php echo $s['key_name']; ?>][en]" value="<?php echo htmlspecialchars($s['value_en']); ?>" required>
                    </div>
                    <div class="admin-form-group" style="margin-bottom: 0;">
                        <label>Thai Value (ภาษาไทย)</label>
                        <input type="text" name="settings[<?php echo $s['key_name']; ?>][th]" value="<?php echo htmlspecialchars($s['value_th']); ?>" required>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Submit Panel -->
    <div style="margin-top: 30px; border-top: 1px solid var(--border-light); padding-top: 25px; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn-search" style="width: auto; padding: 15px 40px; height: auto;">
            <i class="fas fa-save"></i> Save Configurations
        </button>
    </div>
</form>

<script>
function switchSettingsTab(evt, tabId) {
    // Hide all tab contents
    const contents = document.querySelectorAll('.settings-tab-content');
    contents.forEach(content => {
        content.style.display = 'none';
    });

    // Remove active class from all tab buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => {
        btn.classList.remove('active');
        btn.style.borderBottomColor = 'transparent';
        btn.style.color = 'var(--text-muted)';
    });

    // Show selected content and set active style on button
    document.getElementById(tabId).style.display = 'block';
    evt.currentTarget.classList.add('active');
    evt.currentTarget.style.borderBottomColor = 'var(--primary-gold)';
    evt.currentTarget.style.color = 'var(--primary-gold)';
}
</script>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
