<?php
require_once __DIR__ . '/header.php';

// Fetch rooms from DB
try {
    $stmt = $pdo->query("SELECT * FROM rooms ORDER BY price_per_night ASC");
    $rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    $rooms = [];
}
?>

<!-- Hero Section -->
<section class="hero" style="background-image: url('<?php echo htmlspecialchars(__t('hero_bg_image')); ?>');">
    <div class="hero-content">
        <span class="hero-subtitle"><?php echo __t('hero_subtitle'); ?></span>
        <h1 class="hero-title"><?php echo __t('hero_title'); ?></h1>
        <p class="hero-desc"><?php echo __t('hero_desc'); ?></p>
    </div>
</section>

<!-- Floating Booking Search Bar -->
<div class="search-bar-container">
    <div class="container">
        <div class="booking-search-bar">
            <form action="rooms.php" method="GET" class="search-grid">
                <div class="search-group">
                    <label for="check_in_search"><?php echo __t('search_check_in'); ?></label>
                    <input type="date" id="check_in_search" name="check_in" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="search-group">
                    <label for="check_out_search"><?php echo __t('search_check_out'); ?></label>
                    <input type="date" id="check_out_search" name="check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
                
                <div class="search-group">
                    <label for="guests_search"><?php echo __t('search_guests'); ?></label>
                    <select id="guests_search" name="guests">
                        <option value="1"><?php echo __t('search_guest_count', ['count' => 1]); ?></option>
                        <option value="2" selected><?php echo __t('search_guest_count', ['count' => 2]); ?></option>
                        <option value="3"><?php echo __t('search_guest_count', ['count' => 3]); ?></option>
                        <option value="4"><?php echo __t('search_guest_count', ['count' => 4]); ?></option>
                    </select>
                </div>
                
                <div class="search-group">
                    <label for="type_search"><?php echo __t('search_room_type'); ?></label>
                    <select id="type_search" name="type">
                        <option value=""><?php echo __t('search_all_types'); ?></option>
                        <option value="Deluxe"><?php echo __t('search_deluxe'); ?></option>
                        <option value="Suite"><?php echo __t('search_suite'); ?></option>
                        <option value="Penthouse"><?php echo __t('search_penthouse'); ?></option>
                    </select>
                </div>
                
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i> <?php echo __t('search_check_btn'); ?>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Welcome & Philosophy -->
<section class="section-padding">
    <div class="container reveal reveal-scale" style="text-align: center; max-width: 800px;">
        <span class="section-subtitle"><?php echo __t('index_phil_sub'); ?></span>
        <h2 class="section-title"><?php echo __t('index_phil_title'); ?></h2>
        <p style="color: var(--text-muted); font-size: 1.05rem; margin-top: 30px; line-height: 1.8; font-weight: 300;">
            <?php echo __t('index_phil_desc'); ?>
        </p>
    </div>
</section>

<!-- Featured Rooms -->
<section class="section-padding" style="background: rgba(197, 168, 128, 0.02); border-top: 1px solid var(--border-light); border-bottom: 1px solid var(--border-light);">
    <div class="container">
        <div class="section-title-wrapper reveal">
            <span class="section-subtitle"><?php echo __t('index_rooms_sub'); ?></span>
            <h2 class="section-title"><?php echo __t('index_rooms_title'); ?></h2>
        </div>
        
        <div class="rooms-grid">
            <?php $idx = 1; foreach ($rooms as $room): ?>
                <div class="room-card reveal reveal-delay-<?php echo $idx; ?>">
                    <div class="room-img-wrapper">
                        <img src="<?php echo htmlspecialchars($room['image_url']); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>">
                        <div class="room-price-badge">
                            <?php echo __t('index_from'); ?> <span>฿<?php echo number_format($room['price_per_night']); ?></span> / <?php echo __t('index_night'); ?>
                        </div>
                    </div>
                    
                    <div class="room-content">
                        <span class="room-type"><?php echo __t('search_' . strtolower($room['type'])); ?></span>
                        <h3 class="room-name">
                            <a href="room-detail.php?id=<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['name']); ?></a>
                        </h3>
                        
                        <div class="room-meta">
                            <span><i class="fas fa-ruler-combined"></i> <?php echo htmlspecialchars($room['size_sqm']); ?> <?php echo __t('rooms_sqm'); ?></span>
                            <span><i class="fas fa-users"></i> <?php echo __t('rooms_max_guests', ['count' => $room['capacity']]); ?></span>
                        </div>
                        
                        <p class="room-desc"><?php echo __t('room_desc_' . $room['id']); ?></p>
                        
                        <div class="room-card-bottom">
                            <span class="room-landmark">
                                <i class="fas fa-map-marker-alt text-gold"></i> <?php echo $room['distance_landmark'] . ' ' . __t('rooms_km_from', ['landmark' => __t('landmark_' . $room['landmark_name'])]); ?>
                            </span>
                            <a href="room-detail.php?id=<?php echo $room['id']; ?>" class="btn-details">
                                <?php echo __t('index_view_details'); ?> <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php $idx++; endforeach; ?>
        </div>
    </div>
</section>

<!-- Signature Services -->
<section class="section-padding">
    <div class="container">
        <div class="section-title-wrapper reveal">
            <span class="section-subtitle"><?php echo __t('index_services_sub'); ?></span>
            <h2 class="section-title"><?php echo __t('index_services_title'); ?></h2>
        </div>
        
        <div class="rooms-grid">
            <div class="room-card reveal reveal-left reveal-delay-1" style="text-align: center; padding: 40px 30px; align-items: center;">
                <div style="font-size: 3rem; color: var(--primary-gold); margin-bottom: 20px;">
                    <i class="fas fa-spa"></i>
                </div>
                <h3 style="margin-bottom: 15px; font-family: var(--font-serif);"><?php echo __t('index_service_spa'); ?></h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;"><?php echo __t('index_service_spa_desc'); ?></p>
            </div>
            
            <div class="room-card reveal reveal-scale reveal-delay-2" style="text-align: center; padding: 40px 30px; align-items: center;">
                <div style="font-size: 3rem; color: var(--primary-gold); margin-bottom: 20px;">
                    <i class="fas fa-utensils"></i>
                </div>
                <h3 style="margin-bottom: 15px; font-family: var(--font-serif);"><?php echo __t('index_service_dining'); ?></h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;"><?php echo __t('index_service_dining_desc'); ?></p>
            </div>
            
            <div class="room-card reveal reveal-right reveal-delay-3" style="text-align: center; padding: 40px 30px; align-items: center;">
                <div style="font-size: 3rem; color: var(--primary-gold); margin-bottom: 20px;">
                    <i class="fas fa-concierge-bell"></i>
                </div>
                <h3 style="margin-bottom: 15px; font-family: var(--font-serif);"><?php echo __t('index_service_butler'); ?></h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;"><?php echo __t('index_service_butler_desc'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials / Reviews Summary -->
<section class="section-padding" style="background: var(--bg-dark); border-top: 1px solid var(--border-light);">
    <div class="container">
        <div class="section-title-wrapper reveal">
            <span class="section-subtitle"><?php echo __t('index_testi_sub'); ?></span>
            <h2 class="section-title"><?php echo __t('index_testi_title'); ?></h2>
        </div>
        
        <div class="reveal reveal-scale" style="max-width: 800px; margin: 0 auto; background: var(--bg-card); border: 1px solid var(--border-light); padding: 50px; text-align: center; position: relative;">
            <div style="font-size: 4rem; color: rgba(197, 168, 128, 0.1); position: absolute; top: 10px; left: 30px; font-family: var(--font-serif);">“</div>
            <p style="font-size: 1.1rem; font-style: italic; color: var(--text-muted); margin-bottom: 30px; line-height: 1.8;">
                "<?php echo __t('index_testi_quote'); ?>"
            </p>
            <div style="font-weight: 600; color: var(--primary-gold); letter-spacing: 1px; text-transform: uppercase; font-size: 0.85rem;">Victoria Sterling</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 5px;"><?php echo __t('search_penthouse'); ?> Guest</div>
        </div>
    </div>
</section>

<!-- Auto Search Dates Setting Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0];
    
    const checkIn = document.getElementById('check_in_search');
    const checkOut = document.getElementById('check_out_search');
    
    if (checkIn && checkOut) {
        checkIn.value = today;
        checkOut.value = tomorrow;
        
        checkIn.addEventListener('change', function() {
            checkOut.min = new Date(new Date(this.value).getTime() + 86400000).toISOString().split('T')[0];
            if (checkOut.value <= this.value) {
                checkOut.value = checkOut.min;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
