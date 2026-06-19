<?php
require_once __DIR__ . '/header.php';

// Fetch room ID
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($room_id <= 0) {
    header("Location: rooms.php");
    exit;
}

// Pre-fill query parameters for dates if present
$check_in_pre = $_GET['check_in'] ?? '';
$check_out_pre = $_GET['check_out'] ?? '';
$guests_pre = isset($_GET['guests']) ? intval($_GET['guests']) : 2;

// Handle Review Submission
$review_success = false;
$review_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $guest_name = trim($_POST['guest_name']);
    $r_clean = floatval($_POST['rating_cleanliness']);
    $r_serv = floatval($_POST['rating_service']);
    $r_val = floatval($_POST['rating_value']);
    $comment = trim($_POST['comment']);
    
    if (empty($guest_name) || empty($comment)) {
        $review_error = "Please fill in all required fields.";
    } else {
        $overall = ($r_clean + $r_serv + $r_val) / 3.0;
        $image_dest_db = null;
        
        // Handle guest photo upload
        if (isset($_FILES['review_image']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['review_image']['tmp_name'];
            $file_name = $_FILES['review_image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($file_ext, $allowed_exts)) {
                $upload_dir = __DIR__ . '/assets/images/uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $new_file_name = uniqid('rev_') . '.' . $file_ext;
                $dest_path = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $dest_path)) {
                    $image_dest_db = 'assets/images/uploads/' . $new_file_name;
                }
            } else {
                $review_error = "Invalid image file type. Allowed: JPG, PNG, WEBP.";
            }
        }
        
        if (empty($review_error)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO reviews (room_id, guest_name, rating_cleanliness, rating_service, rating_value, overall_rating, comment, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$room_id, $guest_name, $r_clean, $r_serv, $r_val, $overall, $comment, $image_dest_db]);
                $review_success = true;
            } catch (PDOException $e) {
                $review_error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch room details
try {
    // 1. Get room data
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();
    
    if (!$room) {
        header("Location: rooms.php");
        exit;
    }
    
    // 2. Check bookings to calculate current inventory
    $check_in_check = $check_in_pre ?: date('Y-m-d');
    $check_out_check = $check_out_pre ?: date('Y-m-d', strtotime('+1 day'));
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status = 'confirmed' AND NOT (check_out <= ? OR check_in >= ?)");
    $stmt->execute([$room_id, $check_in_check, $check_out_check]);
    $booked_count = $stmt->fetchColumn();
    $remaining_rooms = $room['total_rooms'] - $booked_count;
    
    // 3. Get reviews
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE room_id = ? ORDER BY created_at DESC");
    $stmt->execute([$room_id]);
    $reviews = $stmt->fetchAll();
    
    // Calculate review scores averages
    $total_reviews = count($reviews);
    $avg_overall = 0;
    $avg_clean = 0;
    $avg_service = 0;
    $avg_val = 0;
    
    if ($total_reviews > 0) {
        $sum_overall = 0;
        $sum_clean = 0;
        $sum_service = 0;
        $sum_val = 0;
        foreach ($reviews as $rev) {
            $sum_overall += $rev['overall_rating'];
            $sum_clean += $rev['rating_cleanliness'];
            $sum_service += $rev['rating_service'];
            $sum_val += $rev['rating_value'];
        }
        $avg_overall = round($sum_overall / $total_reviews, 1);
        $avg_clean = round($sum_clean / $total_reviews, 1);
        $avg_service = round($sum_service / $total_reviews, 1);
        $avg_val = round($sum_val / $total_reviews, 1);
    }
} catch (PDOException $e) {
    die("Query Error: " . $e->getMessage());
}

$amenities = json_decode($room['amenities'], true) ?: [];
?>

<section class="section-padding">
    <div class="container">
        
        <!-- Room Title Header -->
        <div class="room-detail-header">
            <span class="room-type"><?php echo __t('search_' . strtolower($room['type'])); ?></span>
            <h1 class="elegant-title" style="font-size: 3rem; margin-top: 10px; margin-bottom: 15px; color: var(--primary-gold);">
                <?php echo htmlspecialchars($room['name']); ?>
            </h1>
            <div style="display: flex; gap: 20px; align-items: center; color: var(--text-muted); font-size: 0.9rem;">
                <span><i class="fas fa-ruler-combined"></i> <?php echo htmlspecialchars($room['size_sqm']); ?> <?php echo __t('rooms_sqm'); ?></span>
                <span>|</span>
                <span><i class="fas fa-users"></i> <?php echo __t('rooms_max_guests', ['count' => $room['capacity']]); ?></span>
                <span>|</span>
                <span>
                    <i class="fas fa-map-marker-alt"></i> <?php echo $room['distance_landmark'] . ' ' . __t('rooms_km_from', ['landmark' => __t('landmark_' . $room['landmark_name'])]); ?>
                </span>
                <span>|</span>
                <span>
                    <?php if ($remaining_rooms > 0): ?>
                        <span style="color: var(--success); font-weight: 600;"><i class="fas fa-check-circle"></i> <?php echo __t('detail_room_avail', ['count' => $remaining_rooms]); ?></span>
                    <?php else: ?>
                        <span style="color: var(--danger); font-weight: 600;"><i class="fas fa-times-circle"></i> <?php echo __t('rooms_fully_booked'); ?></span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        
        <!-- Room Detail Main Layout -->
        <div class="room-detail-grid">
            
            <!-- Left Column: Images, Info, Amenities, Reviews, Map -->
            <div>
                <!-- Gallery component -->
                <div class="gallery-container">
                    <div class="gallery-main">
                        <img src="<?php echo htmlspecialchars($room['image_url']); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>">
                    </div>
                    <!-- Mock Thumbnail Images (repeating primary with filters or overlays) -->
                    <div class="gallery-thumb active">
                        <img src="<?php echo htmlspecialchars($room['image_url']); ?>" alt="Main View">
                    </div>
                    <div class="gallery-thumb">
                        <img src="assets/images/hero_bg.jpg" alt="Lobby View">
                    </div>
                    <div class="gallery-thumb">
                        <img src="<?php echo htmlspecialchars($room['image_url']); ?>" style="filter: brightness(0.85) contrast(1.1);" alt="Detail View">
                    </div>
                </div>

                <!-- Room Description -->
                <div class="detail-info-block">
                    <h3><?php echo __t('detail_desc'); ?></h3>
                    <p style="color: var(--text-muted); line-height: 1.8; font-size: 0.95rem; font-weight: 300;">
                        <?php echo nl2br(htmlspecialchars(__t('room_desc_' . $room['id']))); ?>
                    </p>
                </div>

                <!-- Room Amenities -->
                <div class="detail-info-block">
                    <h3><?php echo __t('detail_amenities'); ?></h3>
                    <div class="amenities-list">
                        <?php 
                        $icon_mapping = [
                            'wifi' => 'fa-wifi',
                            'pool' => 'fa-swimming-pool',
                            'parking' => 'fa-parking',
                            'gym' => 'fa-dumbbell',
                            'spa' => 'fa-spa',
                            'room_service' => 'fa-concierge-bell',
                            'mini_bar' => 'fa-glass-martini-alt',
                            'smart_tv' => 'fa-tv',
                            'air_con' => 'fa-wind',
                            'bathtub' => 'fa-bath',
                            'private_jacuzzi' => 'fa-hot-tub',
                            'kitchen' => 'fa-utensils'
                        ];
                        foreach ($amenities as $amt):
                            $icon = $icon_mapping[$amt] ?? 'fa-check';
                            $label = __t('amt_' . $amt);
                        ?>
                            <div class="amenity-item">
                                <i class="fas <?php echo $icon; ?>"></i>
                                <span><?php echo htmlspecialchars($label); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Policies -->
                <div class="detail-info-block">
                    <h3><?php echo __t('detail_policy'); ?></h3>
                    <ul class="policy-list">
                        <li>
                            <strong><?php echo __t('policy_checkin_title'); ?></strong>
                            <span><?php echo __t('policy_checkin_desc'); ?></span>
                        </li>
                        <li>
                            <strong><?php echo __t('policy_checkout_title'); ?></strong>
                            <span><?php echo __t('policy_checkout_desc'); ?></span>
                        </li>
                        <li>
                            <strong><?php echo __t('policy_cancel_title'); ?></strong>
                            <span style="color: var(--primary-gold);"><?php echo __t('policy_cancel_desc'); ?></span>
                        </li>
                        <li>
                            <strong><?php echo __t('policy_children_title'); ?></strong>
                            <span><?php echo __t('policy_children_desc'); ?></span>
                        </li>
                    </ul>
                </div>

                <!-- Map & Nearby Attractions (No.5 Map Requirement) -->
                <div class="detail-info-block">
                    <h3><?php echo __t('detail_location'); ?></h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                        <?php echo __t('detail_location_desc'); ?>
                    </p>
                    <div class="location-section">
                        <!-- Leaflet Real Map Integration -->
                        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                        
                        <div class="map-wrapper" style="border-radius: 4px; box-shadow: var(--shadow-premium); overflow: hidden; margin-bottom: 25px; height: 400px; border: 1px solid var(--border-light);">
                            <div id="hotel-real-map" style="width: 100%; height: 100%; z-index: 10;"></div>
                        </div>

                        <?php
                        $hotel_lat = floatval(__t('hotel_latitude') ?: '7.8969000');
                        $hotel_lng = floatval(__t('hotel_longitude') ?: '98.2966000');
                        
                        $landmarks = [];
                        try {
                            $lm_stmt = $pdo->query("SELECT * FROM landmarks ORDER BY id ASC");
                            $landmarks = $lm_stmt->fetchAll();
                        } catch (PDOException $e) {
                            // Fallback
                        }
                        ?>

                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const map = L.map('hotel-real-map').setView([<?php echo $hotel_lat; ?>, <?php echo $hotel_lng; ?>], 13);
                            
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                            }).addTo(map);

                            // Add hotel marker
                            const hotelMarker = L.marker([<?php echo $hotel_lat; ?>, <?php echo $hotel_lng; ?>]).addTo(map);
                            hotelMarker.bindPopup("<b><?php echo addslashes(__t('hero_title')); ?></b><br>Our Luxury Resort").openPopup();

                            // Add landmarks markers
                            <?php foreach ($landmarks as $lm): 
                                $lm_name = ($current_lang === 'th') ? $lm['name_th'] : $lm['name_en'];
                                $distance = $lm['distance_km'];
                                if ($room['landmark_name'] === $lm['name_en'] || $room['landmark_name'] === $lm['name_th']) {
                                    $distance = $room['distance_landmark'];
                                }
                            ?>
                                L.marker([<?php echo $lm['latitude']; ?>, <?php echo $lm['longitude']; ?>])
                                    .addTo(map)
                                    .bindPopup("<b><?php echo addslashes($lm_name); ?></b><br>Distance: <?php echo htmlspecialchars($distance); ?> km");
                            <?php endforeach; ?>
                        });
                        </script>

                        <!-- Attractions list -->
                        <div class="attractions-list">
                            <?php foreach ($landmarks as $lm): 
                                $lm_name = ($current_lang === 'th') ? $lm['name_th'] : $lm['name_en'];
                                $distance = $lm['distance_km'];
                                if ($room['landmark_name'] === $lm['name_en'] || $room['landmark_name'] === $lm['name_th']) {
                                    $distance = $room['distance_landmark'];
                                }
                            ?>
                                <div class="attraction-item">
                                    <div class="attraction-info">
                                        <span class="attraction-name"><?php echo htmlspecialchars($lm_name); ?></span>
                                    </div>
                                    <span class="attraction-distance"><?php echo htmlspecialchars($distance); ?> km</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Reviews (No.4 Review Requirement) -->
                <div class="detail-info-block">
                    <h3><?php echo __t('detail_reviews'); ?></h3>
                    
                    <?php if ($total_reviews > 0): ?>
                        <div class="reviews-summary">
                            <div class="score-circle">
                                <span class="score-num"><?php echo $avg_overall; ?></span>
                                <span class="score-label">out of 5</span>
                            </div>
                            
                            <div class="sub-ratings-grid">
                                <div class="rating-progress-bar">
                                    <div class="rating-progress-label">
                                        <span><?php echo __t('detail_cleanliness'); ?></span>
                                        <span><?php echo $avg_clean; ?>/5</span>
                                    </div>
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: <?php echo ($avg_clean/5)*100; ?>%;"></div>
                                    </div>
                                </div>
                                
                                <div class="rating-progress-bar">
                                    <div class="rating-progress-label">
                                        <span><?php echo __t('detail_service_rating'); ?></span>
                                        <span><?php echo $avg_service; ?>/5</span>
                                    </div>
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: <?php echo ($avg_service/5)*100; ?>%;"></div>
                                    </div>
                                </div>
                                
                                <div class="rating-progress-bar">
                                    <div class="rating-progress-label">
                                        <span><?php echo __t('detail_value'); ?></span>
                                        <span><?php echo $avg_val; ?>/5</span>
                                    </div>
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: <?php echo ($avg_val/5)*100; ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Review items -->
                        <div class="reviews-list">
                            <?php foreach ($reviews as $rev): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <span class="reviewer-name"><?php echo htmlspecialchars($rev['guest_name']); ?></span>
                                            <span class="review-date"><?php echo date('d M Y', strtotime($rev['created_at'])); ?></span>
                                        </div>
                                        <div class="review-stars">
                                            <i class="fas fa-star"></i> <?php echo number_format($rev['overall_rating'], 1); ?>
                                        </div>
                                    </div>
                                    <p class="review-comment"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                                    
                                    <?php if ($rev['image_url']): ?>
                                        <div class="review-media">
                                            <img src="<?php echo htmlspecialchars($rev['image_url']); ?>" alt="Review Image">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 0.95rem;"><?php echo __t('detail_review_no_reviews'); ?></p>
                    <?php endif; ?>

                    <!-- Submit Review Form -->
                    <div class="review-form">
                        <h4 style="font-family: var(--font-serif); font-size: 1.3rem; margin-bottom: 25px; color: var(--primary-gold);"><?php echo __t('detail_share_exp'); ?></h4>
                        
                        <?php if ($review_success): ?>
                            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); padding: 15px; color: var(--success); margin-bottom: 20px;">
                                <?php echo __t('detail_review_success'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($review_error): ?>
                            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); padding: 15px; color: var(--danger); margin-bottom: 20px;">
                                <?php echo htmlspecialchars($review_error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 20px;">
                            <input type="hidden" name="submit_review" value="1">
                            
                            <div class="rating-input-row">
                                <div class="rating-select-group">
                                    <label><?php echo __t('detail_cleanliness'); ?></label>
                                    <select name="rating_cleanliness" required>
                                        <option value="5">5 Excellent</option>
                                        <option value="4">4 Very Good</option>
                                        <option value="3">3 Average</option>
                                        <option value="2">2 Poor</option>
                                        <option value="1">1 Terrible</option>
                                    </select>
                                </div>
                                
                                <div class="rating-select-group">
                                    <label><?php echo __t('detail_service_rating'); ?></label>
                                    <select name="rating_service" required>
                                        <option value="5">5 Excellent</option>
                                        <option value="4">4 Very Good</option>
                                        <option value="3">3 Average</option>
                                        <option value="2">2 Poor</option>
                                        <option value="1">1 Terrible</option>
                                    </select>
                                </div>
                                
                                <div class="rating-select-group">
                                    <label><?php echo __t('detail_value'); ?></label>
                                    <select name="rating_value" required>
                                        <option value="5">5 Excellent</option>
                                        <option value="4">4 Very Good</option>
                                        <option value="3">3 Average</option>
                                        <option value="2">2 Poor</option>
                                        <option value="1">1 Terrible</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label for="reviewer_name" style="font-size: 0.8rem; color: var(--primary-gold); text-transform: uppercase;"><?php echo __t('detail_your_name'); ?></label>
                                <input type="text" id="reviewer_name" name="guest_name" required style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 12px 15px; outline: none;">
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label for="review_comment" style="font-size: 0.8rem; color: var(--primary-gold); text-transform: uppercase;"><?php echo __t('detail_your_comments'); ?></label>
                                <textarea id="review_comment" name="comment" rows="5" required style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 12px 15px; outline: none; resize: vertical;"></textarea>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label for="review_image" style="font-size: 0.8rem; color: var(--primary-gold); text-transform: uppercase;"><?php echo __t('detail_upload_photo'); ?></label>
                                <input type="file" id="review_image" name="review_image" accept="image/*" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-muted); padding: 10px; outline: none;">
                            </div>
                            
                            <button type="submit" class="btn-search" style="height: auto; padding: 15px; font-size: 0.85rem;"><?php echo __t('detail_submit_review'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Sticky Booking Widget -->
            <div>
                <div class="booking-widget-card">
                    <div class="widget-price">
                        <span class="price-val">฿<?php echo number_format($room['price_per_night']); ?></span> / <?php echo __t('index_night'); ?>
                    </div>
                    
                    <?php if ($remaining_rooms > 0): ?>
                        <form action="book.php" method="POST" class="widget-form">
                            <!-- Raw price for dynamic JS calculations -->
                            <input type="hidden" id="room-price-raw" value="<?php echo $room['price_per_night']; ?>">
                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                            
                            <!-- Hidden inputs for calculated fields (secure POST) -->
                            <input type="hidden" id="hidden_extras_total" name="extras_total" value="0">
                            <input type="hidden" id="hidden_discount" name="discount_applied" value="0">
                            <input type="hidden" id="hidden_grand_total" name="grand_total" value="0">
                            <input type="hidden" id="hidden_promo_code" name="applied_promo_code" value="">

                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label for="check_in_guest" style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold); font-weight: 500;"><?php echo __t('detail_check_in_date'); ?></label>
                                    <input type="text" id="check_in_guest" name="check_in" value="<?php echo htmlspecialchars($check_in_pre); ?>" required style="background: var(--bg-dark); border:1px solid var(--border-light); color: var(--text-light); padding: 12px 15px; outline: none; width: 100%;">
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label for="check_out_guest" style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold); font-weight: 500;"><?php echo __t('detail_check_out_date'); ?></label>
                                    <input type="text" id="check_out_guest" name="check_out" value="<?php echo htmlspecialchars($check_out_pre); ?>" required style="background: var(--bg-dark); border:1px solid var(--border-light); color: var(--text-light); padding: 12px 15px; outline: none; width: 100%;">
                                </div>

                                <div id="price-calculation-block-guest" style="display: none; background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); padding: 15px; margin-top: 10px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px;">
                                        <span><?php echo __t('detail_nights'); ?></span>
                                        <span id="total-nights-guest" style="color: var(--text-light); font-weight: 600;">0</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 0.95rem; color: var(--primary-gold); font-weight: 600;">
                                        <span><?php echo __t('detail_total_price'); ?></span>
                                        <span id="calc-total-price-guest">0 THB</span>
                                    </div>
                                </div>

                                <div style="background: rgba(197, 168, 128, 0.05); border: 1px dashed var(--primary-gold); padding: 20px; text-align: center; margin-top: 10px;">
                                    <i class="fas fa-lock" style="color: var(--primary-gold); font-size: 1.5rem; margin-bottom: 10px; display: block;"></i>
                                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 15px; line-height: 1.5;"><?php echo __t('detail_lock_msg'); ?></p>
                                    <a href="login.php?redirect=<?php echo urlencode('room-detail.php?id=' . $room['id'] . '&check_in=' . $check_in_pre . '&check_out=' . $check_out_pre . '&guests=' . $guests_pre); ?>" class="btn-search" style="display: flex; text-decoration: none; align-items: center; justify-content: center; height: auto; padding: 12px; font-size: 0.8rem;">
                                        <?php echo __t('nav_login'); ?>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label for="check_in" style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold); font-weight: 500;"><?php echo __t('detail_check_in_date'); ?></label>
                                    <input type="text" id="check_in" name="check_in" value="<?php echo htmlspecialchars($check_in_pre); ?>" required style="background: var(--bg-dark); border:1px solid var(--border-light); color: var(--text-light); padding: 12px 15px; outline: none; width: 100%;">
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label for="check_out" style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold); font-weight: 500;"><?php echo __t('detail_check_out_date'); ?></label>
                                    <input type="text" id="check_out" name="check_out" value="<?php echo htmlspecialchars($check_out_pre); ?>" required style="background: var(--bg-dark); border:1px solid var(--border-light); color: var(--text-light); padding: 12px 15px; outline: none; width: 100%;">
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label for="guests_widget" style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold); font-weight: 500;"><?php echo __t('detail_num_guests'); ?></label>
                                    <select id="guests_widget" name="guests_count" style="background: var(--bg-dark); border:1px solid var(--border-light); color: var(--text-light); padding: 12px 15px; outline: none;">
                                        <?php for ($i=1; $i<=$room['capacity']; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($i == $guests_pre) ? 'selected' : ''; ?>><?php echo __t('search_guest_count', ['count' => $i]); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <!-- Premium Extra Services (No. 2 Add-ons Recommendation) -->
                                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px; border-top: 1px solid var(--border-light); padding-top: 15px;">
                                    <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold); font-weight: 600; margin-bottom: 5px;"><?php echo __t('detail_extras_title'); ?></label>
                                    
                                    <div style="display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text-light);">
                                        <input type="checkbox" id="extra_breakfast" name="extra_breakfast" value="1" onchange="calculatePriceDetailed()" style="width: auto; accent-color: var(--primary-gold);">
                                        <label for="extra_breakfast" style="cursor: pointer; text-transform: none; color: var(--text-muted);"><?php echo __t('detail_extra_breakfast'); ?></label>
                                    </div>
                                    
                                    <div style="display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text-light); margin-top: 5px;">
                                        <input type="checkbox" id="extra_shuttle" name="extra_shuttle" value="1" onchange="calculatePriceDetailed()" style="width: auto; accent-color: var(--primary-gold);">
                                        <label for="extra_shuttle" style="cursor: pointer; text-transform: none; color: var(--text-muted);"><?php echo __t('detail_extra_shuttle'); ?></label>
                                    </div>
                                    
                                    <div style="display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text-light); margin-top: 5px;">
                                        <input type="checkbox" id="extra_spa" name="extra_spa" value="1" onchange="calculatePriceDetailed()" style="width: auto; accent-color: var(--primary-gold);">
                                        <label for="extra_spa" style="cursor: pointer; text-transform: none; color: var(--text-muted);"><?php echo __t('detail_extra_spa'); ?></label>
                                    </div>
                                </div>

                                <!-- Promo Code Input (No. 1 Promo Code Recommendation) -->
                                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px; border-top: 1px solid var(--border-light); padding-top: 15px;">
                                    <label for="promo_code" style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold); font-weight: 600;"><?php echo __t('detail_promo_code'); ?></label>
                                    <div style="display: flex; gap: 10px;">
                                        <input type="text" id="promo_code" name="promo_code_input" placeholder="WELCOME10, PROMO15" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 10px; outline: none; flex: 1; font-size: 0.85rem; text-transform: uppercase;">
                                        <button type="button" onclick="applyPromoCode()" class="btn-secondary" style="padding: 10px 15px; font-size: 0.8rem; height: auto; margin: 0; background: var(--primary-gold); color: var(--bg-dark); border: none; font-weight: 600; cursor: pointer;"><?php echo __t('detail_promo_apply'); ?></button>
                                    </div>
                                    <div id="promo-message" style="font-size: 0.8rem; margin-top: 4px; display: none;"></div>
                                </div>

                                <!-- Guest Personal Info (Pre-filled and locked) -->
                                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px; border-top: 1px solid var(--border-light); padding-top: 15px;">
                                    <label for="guest_name_w" style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold); font-weight: 500;"><?php echo __t('detail_full_name'); ?></label>
                                    <input type="text" id="guest_name_w" name="guest_name" required value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" readonly style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); color: var(--text-muted); padding: 12px 15px; outline: none; cursor: not-allowed;">
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label for="guest_email_w" style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold); font-weight: 500;"><?php echo __t('detail_email'); ?></label>
                                    <input type="email" id="guest_email_w" name="guest_email" required value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" readonly style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); color: var(--text-muted); padding: 12px 15px; outline: none; cursor: not-allowed;">
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label for="guest_phone_w" style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold); font-weight: 500;"><?php echo __t('detail_phone'); ?></label>
                                    <input type="tel" id="guest_phone_w" name="guest_phone" required value="<?php echo htmlspecialchars($_SESSION['user_phone']); ?>" readonly style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); color: var(--text-muted); padding: 12px 15px; outline: none; cursor: not-allowed;">
                                </div>
                                
                                <!-- Live calculation details (No. 5 Premium Receipt display) -->
                                <div id="price-calculation-block" style="display: none; background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); padding: 15px; margin-top: 15px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px;">
                                        <span><?php echo __t('detail_nights'); ?></span>
                                        <span id="total-nights" style="color: var(--text-light); font-weight: 600;">0</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px;">
                                        <span>Room Cost</span>
                                        <span id="calc-room-total" style="color: var(--text-light); font-weight: 600;">0 THB</span>
                                    </div>
                                    <div id="calc-extras-row" style="display: none; justify-content: space-between; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px;">
                                        <span><?php echo __t('detail_extras_total'); ?></span>
                                        <span id="calc-extras-total" style="color: var(--text-light); font-weight: 600;">0 THB</span>
                                    </div>
                                    <div id="calc-discount-row" style="display: none; justify-content: space-between; font-size: 0.85rem; color: var(--success); margin-bottom: 8px; border-bottom: 1px dashed var(--border-light); padding-bottom: 8px;">
                                        <span><?php echo __t('detail_promo_discount'); ?> (<span id="discount-pct">0</span>%)</span>
                                        <span id="calc-discount-val">-0 THB</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 0.95rem; color: var(--primary-gold); font-weight: 600; padding-top: 5px;">
                                        <span><?php echo __t('detail_total_price'); ?></span>
                                        <span id="calc-total-price">0 THB</span>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn-book-submit" style="margin-top: 10px;">
                                    <?php echo __t('detail_proceed_book'); ?>
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid var(--danger); padding: 20px; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                            <i class="fas fa-exclamation-circle" style="color: var(--danger); font-size: 1.5rem; margin-bottom: 10px; display: block;"></i>
                            We apologize, this room is fully booked for your selected dates. Please try another room type or adjust your dates.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        
    </div>
</section>

<!-- Flatpickr CSS & JS (CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isThai = "<?php echo $current_lang; ?>" === 'th';
    const roomPrice = parseFloat(document.getElementById('room-price-raw')?.value || 0);
    
    // Variables for calculations
    let discountPct = 0;
    let promoCodeApplied = "";
    
    // Check if flatpickr inputs exist (logged in)
    const checkInEl = document.getElementById('check_in');
    const checkOutEl = document.getElementById('check_out');
    
    let fpCheckIn, fpCheckOut;
    
    if (checkInEl && checkOutEl) {
        fpCheckIn = flatpickr("#check_in", {
            minDate: "today",
            dateFormat: "Y-m-d",
            locale: isThai ? "th" : "default",
            onChange: function(selectedDates, dateStr, instance) {
                if (dateStr) {
                    const nextDay = new Date(selectedDates[0].getTime() + 24 * 60 * 60 * 1000);
                    fpCheckOut.set("minDate", nextDay);
                    if (fpCheckOut.selectedDates.length > 0 && fpCheckOut.selectedDates[0] <= selectedDates[0]) {
                        fpCheckOut.setDate(nextDay);
                    }
                }
                calculatePriceDetailed();
            }
        });

        fpCheckOut = flatpickr("#check_out", {
            minDate: new Date().getTime() + 24 * 60 * 60 * 1000,
            dateFormat: "Y-m-d",
            locale: isThai ? "th" : "default",
            onChange: function(selectedDates, dateStr, instance) {
                calculatePriceDetailed();
            }
        });
    }
    
    // Guest view flatpickr (just indicators)
    const checkInGuestEl = document.getElementById('check_in_guest');
    const checkOutGuestEl = document.getElementById('check_out_guest');
    
    if (checkInGuestEl && checkOutGuestEl) {
        flatpickr("#check_in_guest", {
            minDate: "today",
            dateFormat: "Y-m-d",
            locale: isThai ? "th" : "default",
            onChange: function(selectedDates, dateStr) {
                calculateGuestPrice();
            }
        });
        flatpickr("#check_out_guest", {
            minDate: new Date().getTime() + 24 * 60 * 60 * 1000,
            dateFormat: "Y-m-d",
            locale: isThai ? "th" : "default",
            onChange: function(selectedDates, dateStr) {
                calculateGuestPrice();
            }
        });
    }

    function calculateGuestPrice() {
        const ci = document.getElementById('check_in_guest')?.value;
        const co = document.getElementById('check_out_guest')?.value;
        const totalNightsSpan = document.getElementById('total-nights-guest');
        const totalPriceSpan = document.getElementById('calc-total-price-guest');
        const calcBlock = document.getElementById('price-calculation-block-guest');
        
        if (ci && co) {
            const checkInDate = new Date(ci);
            const checkOutDate = new Date(co);
            if (checkOutDate > checkInDate) {
                const timeDiff = checkOutDate.getTime() - checkInDate.getTime();
                const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                if (nights > 0) {
                    const total = nights * roomPrice;
                    if (totalNightsSpan) totalNightsSpan.textContent = nights;
                    if (totalPriceSpan) totalPriceSpan.textContent = total.toLocaleString() + ' THB';
                    if (calcBlock) calcBlock.style.display = 'block';
                }
            }
        }
    }

    // Dynamic price breakdown calculator
    window.calculatePriceDetailed = function() {
        const ci = document.getElementById('check_in')?.value;
        const co = document.getElementById('check_out')?.value;
        
        const calcBlock = document.getElementById('price-calculation-block');
        const totalNightsSpan = document.getElementById('total-nights');
        const calcRoomTotalSpan = document.getElementById('calc-room-total');
        const calcExtrasTotalSpan = document.getElementById('calc-extras-total');
        const calcExtrasRow = document.getElementById('calc-extras-row');
        const calcDiscountRow = document.getElementById('calc-discount-row');
        const discountPctSpan = document.getElementById('discount-pct');
        const calcDiscountValSpan = document.getElementById('calc-discount-val');
        const calcTotalPriceSpan = document.getElementById('calc-total-price');

        if (ci && co) {
            const checkInDate = new Date(ci);
            const checkOutDate = new Date(co);
            
            if (checkOutDate > checkInDate) {
                const timeDiff = checkOutDate.getTime() - checkInDate.getTime();
                const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                if (nights > 0) {
                    // 1. Base Room Cost
                    const roomTotal = nights * roomPrice;
                    
                    // 2. Extra Services
                    let extrasTotal = 0;
                    if (document.getElementById('extra_breakfast')?.checked) {
                        extrasTotal += 500 * nights;
                    }
                    if (document.getElementById('extra_shuttle')?.checked) {
                        extrasTotal += 1200;
                    }
                    if (document.getElementById('extra_spa')?.checked) {
                        extrasTotal += 2000;
                    }
                    
                    // 3. Discount
                    const discountVal = (roomTotal * discountPct) / 100;
                    
                    // 4. Grand Total
                    const grandTotal = (roomTotal - discountVal) + extrasTotal;
                    
                    // Update DOM
                    if (totalNightsSpan) totalNightsSpan.textContent = nights;
                    if (calcRoomTotalSpan) calcRoomTotalSpan.textContent = roomTotal.toLocaleString() + ' THB';
                    
                    if (extrasTotal > 0) {
                        if (calcExtrasTotalSpan) calcExtrasTotalSpan.textContent = extrasTotal.toLocaleString() + ' THB';
                        if (calcExtrasRow) calcExtrasRow.style.display = 'flex';
                    } else {
                        if (calcExtrasRow) calcExtrasRow.style.display = 'none';
                    }
                    
                    if (discountPct > 0) {
                        if (discountPctSpan) discountPctSpan.textContent = discountPct;
                        if (calcDiscountValSpan) calcDiscountValSpan.textContent = '-' + discountVal.toLocaleString() + ' THB';
                        if (calcDiscountRow) calcDiscountRow.style.display = 'flex';
                    } else {
                        if (calcDiscountRow) calcDiscountRow.style.display = 'none';
                    }
                    
                    if (calcTotalPriceSpan) calcTotalPriceSpan.textContent = grandTotal.toLocaleString() + ' THB';
                    if (calcBlock) calcBlock.style.display = 'block';
                    
                    // Write to hidden inputs
                    document.getElementById('hidden_extras_total').value = extrasTotal;
                    document.getElementById('hidden_discount').value = discountVal;
                    document.getElementById('hidden_grand_total').value = grandTotal;
                }
            } else {
                if (calcBlock) calcBlock.style.display = 'none';
            }
        }
    }

    // Promo code apply handler
    window.applyPromoCode = function() {
        const codeInput = document.getElementById('promo_code');
        const msgDiv = document.getElementById('promo-message');
        if (!codeInput) return;
        const code = codeInput.value.trim().toUpperCase();
        
        if (!code) {
            discountPct = 0;
            promoCodeApplied = "";
            document.getElementById('hidden_promo_code').value = "";
            msgDiv.style.display = 'none';
            calculatePriceDetailed();
            return;
        }
        
        if (code === 'WELCOME10') {
            discountPct = 10;
            promoCodeApplied = code;
            msgDiv.textContent = isThai ? "ใช้ส่วนลด WELCOME10 สำเร็จ (ลด 10% สำหรับยอดห้องพัก)" : "Promo code WELCOME10 applied! (10% Off room rates)";
            msgDiv.style.color = "var(--success)";
            msgDiv.style.display = "block";
            document.getElementById('hidden_promo_code').value = code;
        } else if (code === 'PROMO15') {
            discountPct = 15;
            promoCodeApplied = code;
            msgDiv.textContent = isThai ? "ใช้ส่วนลด PROMO15 สำเร็จ (ลด 15% สำหรับยอดห้องพัก)" : "Promo code PROMO15 applied! (15% Off room rates)";
            msgDiv.style.color = "var(--success)";
            msgDiv.style.display = "block";
            document.getElementById('hidden_promo_code').value = code;
        } else {
            discountPct = 0;
            promoCodeApplied = "";
            document.getElementById('hidden_promo_code').value = "";
            msgDiv.textContent = isThai ? "รหัสส่วนลดไม่ถูกต้อง" : "Invalid promo code";
            msgDiv.style.color = "var(--danger)";
            msgDiv.style.display = "block";
        }
        
        calculatePriceDetailed();
    }
    
    // Initial run
    calculateGuestPrice();
    calculatePriceDetailed();
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
