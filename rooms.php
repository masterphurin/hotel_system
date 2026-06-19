<?php
require_once __DIR__ . '/header.php';

// Get query parameters
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : 1;
$selected_type = $_GET['type'] ?? '';

// Build rooms query checking booking overlaps
try {
    $sql = "SELECT r.*, 
            COALESCE(
                (SELECT COUNT(*) FROM bookings b 
                 WHERE b.room_id = r.id 
                   AND b.status = 'confirmed' 
                   AND (:check_in != '' AND :check_out != '' AND NOT (b.check_out <= :check_in2 OR b.check_in >= :check_out2))
                ), 0
            ) as booked_count 
            FROM rooms r 
            WHERE 1=1";
            
    $params = [
        ':check_in' => $check_in,
        ':check_out' => $check_out,
        ':check_in2' => $check_in,
        ':check_out2' => $check_out
    ];

    if ($selected_type) {
        $sql .= " AND r.type = :type";
        $params[':type'] = $selected_type;
    }

    $sql .= " ORDER BY r.price_per_night ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query Error: " . $e->getMessage());
}
?>

<section class="section-padding">
    <div class="container">
        <div class="section-title-wrapper" style="text-align: left; margin-bottom: 40px;">
            <span class="section-subtitle"><?php echo __t('rooms_catalog_sub'); ?></span>
            <h2 class="section-title"><?php echo __t('rooms_catalog_title'); ?></h2>
            <?php if ($check_in && $check_out): ?>
                <p style="color: var(--primary-gold); font-size: 0.9rem; margin-top: 10px;">
                    <?php echo __t('rooms_showing_available', ['in' => htmlspecialchars($check_in), 'out' => htmlspecialchars($check_out), 'guests' => htmlspecialchars($guests)]); ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="rooms-page-layout">
            <!-- Sidebar Filters -->
            <aside class="filter-sidebar">
                <h3 style="font-family: var(--font-serif); font-size: 1.2rem; color: var(--primary-gold); margin-bottom: 25px; border-bottom: 1px solid var(--border-light); padding-bottom: 10px;"><?php echo __t('rooms_filter_title'); ?></h3>
                
                <!-- Room Type -->
                <div class="filter-group">
                    <h4 class="filter-title"><?php echo __t('search_room_type'); ?></h4>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="type[]" value="Deluxe" class="sidebar-filter-input" <?php echo ($selected_type == 'Deluxe') ? 'checked' : ''; ?>>
                            <?php echo __t('search_deluxe'); ?>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="type[]" value="Suite" class="sidebar-filter-input" <?php echo ($selected_type == 'Suite') ? 'checked' : ''; ?>>
                            <?php echo __t('search_suite'); ?>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="type[]" value="Penthouse" class="sidebar-filter-input" <?php echo ($selected_type == 'Penthouse') ? 'checked' : ''; ?>>
                            <?php echo __t('search_penthouse'); ?>
                        </label>
                    </div>
                </div>

                <!-- Price Range -->
                <div class="filter-group">
                    <h4 class="filter-title"><?php echo __t('rooms_filter_max_price'); ?></h4>
                    <div class="range-slider-container">
                        <div class="range-inputs">
                            <span>฿1,000</span>
                            <span>฿<span id="price-range-val">20,000</span></span>
                        </div>
                        <input type="range" id="price-range" min="1000" max="20000" step="500" value="20000" class="range-slider">
                    </div>
                </div>

                <!-- Capacity / Guests -->
                <div class="filter-group">
                    <h4 class="filter-title"><?php echo __t('rooms_filter_capacity'); ?></h4>
                    <select id="guests-count-filter" class="sidebar-filter-input" style="width:100%; background:var(--bg-dark); border:1px solid var(--border-light); color:var(--text-light); padding:10px; outline:none;">
                        <option value="1" <?php echo ($guests == 1) ? 'selected' : ''; ?>><?php echo __t('search_guest_count', ['count' => 1]); ?></option>
                        <option value="2" <?php echo ($guests == 2) ? 'selected' : ''; ?>><?php echo __t('search_guest_count', ['count' => 2]); ?></option>
                        <option value="3" <?php echo ($guests == 3) ? 'selected' : ''; ?>><?php echo __t('search_guest_count', ['count' => 3]); ?></option>
                        <option value="4" <?php echo ($guests == 4) ? 'selected' : ''; ?>><?php echo __t('search_guest_count', ['count' => 4]); ?></option>
                    </select>
                </div>

                <!-- Distance from landmark -->
                <div class="filter-group">
                    <h4 class="filter-title"><?php echo __t('rooms_filter_distance'); ?></h4>
                    <select id="distance-filter" class="sidebar-filter-input" style="width:100%; background:var(--bg-dark); border:1px solid var(--border-light); color:var(--text-light); padding:10px; outline:none;">
                        <option value="99"><?php echo __t('rooms_any_distance'); ?></option>
                        <option value="0.5"><?php echo __t('rooms_within_dist', ['dist' => '0.5']); ?></option>
                        <option value="1.5"><?php echo __t('rooms_within_dist', ['dist' => '1.5']); ?></option>
                        <option value="2.5"><?php echo __t('rooms_within_dist', ['dist' => '2.5']); ?></option>
                    </select>
                </div>

                <!-- Amenities Checkboxes -->
                <div class="filter-group">
                    <h4 class="filter-title"><?php echo __t('rooms_filter_amenities'); ?></h4>
                    <div class="checkbox-group">
                        <label class="checkbox-label"><input type="checkbox" name="amenities[]" value="wifi" class="sidebar-filter-input"> <?php echo __t('amt_wifi'); ?></label>
                        <label class="checkbox-label"><input type="checkbox" name="amenities[]" value="pool" class="sidebar-filter-input"> <?php echo __t('amt_pool'); ?></label>
                        <label class="checkbox-label"><input type="checkbox" name="amenities[]" value="parking" class="sidebar-filter-input"> <?php echo __t('amt_parking'); ?></label>
                        <label class="checkbox-label"><input type="checkbox" name="amenities[]" value="gym" class="sidebar-filter-input"> <?php echo __t('amt_gym'); ?></label>
                        <label class="checkbox-label"><input type="checkbox" name="amenities[]" value="spa" class="sidebar-filter-input"> <?php echo __t('amt_spa'); ?></label>
                        <label class="checkbox-label"><input type="checkbox" name="amenities[]" value="room_service" class="sidebar-filter-input"> <?php echo __t('amt_room_service'); ?></label>
                        <label class="checkbox-label"><input type="checkbox" name="amenities[]" value="bathtub" class="sidebar-filter-input"> <?php echo __t('amt_bathtub'); ?></label>
                        <label class="checkbox-label"><input type="checkbox" name="amenities[]" value="private_jacuzzi" class="sidebar-filter-input"> <?php echo __t('amt_private_jacuzzi'); ?></label>
                    </div>
                </div>
            </aside>

            <!-- Rooms List Grid -->
            <main class="rooms-list-column">
                <div class="rooms-grid" style="grid-template-columns: 1fr; gap: 30px;">
                    <?php if (count($rooms) === 0): ?>
                        <div style="background: var(--bg-card); border: 1px solid var(--border-light); padding: 50px; text-align: center;">
                            <p style="color: var(--text-muted); font-size: 1.1rem;"><?php echo __t('rooms_no_match'); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($rooms as $room): 
                        $remaining = $room['total_rooms'] - $room['booked_count'];
                        $is_available = $remaining > 0;
                        // Format query parameter string for dates
                        $date_query = "";
                        if ($check_in && $check_out) {
                            $date_query = "&check_in=" . urlencode($check_in) . "&check_out=" . urlencode($check_out) . "&guests=" . urlencode($guests);
                        }
                    ?>
                        <div class="room-card room-card-filterable" 
                             style="flex-direction: row; min-height: 280px;"
                             data-type="<?php echo htmlspecialchars($room['type']); ?>"
                             data-price="<?php echo htmlspecialchars($room['price_per_night']); ?>"
                             data-capacity="<?php echo htmlspecialchars($room['capacity']); ?>"
                             data-distance="<?php echo htmlspecialchars($room['distance_landmark']); ?>"
                             data-amenities='<?php echo htmlspecialchars($room['amenities']); ?>'>
                             
                            <div class="room-img-wrapper" style="width: 40%; height: auto; min-height: 280px;">
                                <img src="<?php echo htmlspecialchars($room['image_url']); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>" style="height: 100%;">
                                <div class="room-price-badge">
                                    <?php echo __t('index_from'); ?> <span>฿<?php echo number_format($room['price_per_night']); ?></span> / <?php echo __t('index_night'); ?>
                                </div>
                            </div>
                            
                            <div class="room-content" style="width: 60%;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <span class="room-type"><?php echo __t('search_' . strtolower($room['type'])); ?></span>
                                        <h3 class="room-name" style="margin-bottom: 5px;">
                                            <a href="room-detail.php?id=<?php echo $room['id'] . $date_query; ?>"><?php echo htmlspecialchars($room['name']); ?></a>
                                        </h3>
                                    </div>
                                    <div style="text-align: right;">
                                        <?php if ($is_available): ?>
                                            <span style="background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); padding: 4px 8px; font-size: 0.75rem; font-weight: 600;">
                                                <?php echo __t('rooms_left', ['count' => $remaining]); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); padding: 4px 8px; font-size: 0.75rem; font-weight: 600;">
                                                <?php echo __t('rooms_fully_booked'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="room-meta" style="margin-top: 10px;">
                                    <span><i class="fas fa-ruler-combined"></i> <?php echo htmlspecialchars($room['size_sqm']); ?> <?php echo __t('rooms_sqm'); ?></span>
                                    <span><i class="fas fa-users"></i> <?php echo __t('rooms_max_guests', ['count' => $room['capacity']]); ?></span>
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo $room['distance_landmark'] . ' ' . __t('rooms_km_from', ['landmark' => __t('landmark_' . $room['landmark_name'])]); ?></span>
                                </div>
                                
                                <p class="room-desc" style="-webkit-line-clamp: 2; margin-bottom: 15px;"><?php echo __t('room_desc_' . $room['id']); ?></p>
                                
                                <!-- Amenities badges -->
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px;">
                                    <?php 
                                    $amenity_list = json_decode($room['amenities'], true) ?: [];
                                    foreach ($amenity_list as $amt): 
                                        $amt_name = __t('amt_' . $amt);
                                    ?>
                                        <span style="font-size: 0.7rem; background: rgba(255,255,255,0.03); border: 1px solid var(--border-light); padding: 3px 8px; color: var(--text-muted);">
                                            <?php echo htmlspecialchars($amt_name); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>

                                <div class="room-card-bottom" style="margin-top: auto;">
                                    <div></div>
                                    <a href="room-detail.php?id=<?php echo $room['id'] . $date_query; ?>" class="btn-search" style="padding: 10px 20px; font-size: 0.75rem; height: auto;">
                                        <?php echo __t('rooms_book_btn'); ?> <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
