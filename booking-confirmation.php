<?php
require_once __DIR__ . '/header.php';

$ref = $_GET['ref'] ?? '';

if (empty($ref)) {
    header("Location: index.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT b.*, r.name as room_name, r.type as room_type, r.image_url 
                           FROM bookings b 
                           JOIN rooms r ON b.room_id = r.id 
                           WHERE b.booking_reference = ?");
    $stmt->execute([$ref]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        die("<div class='container' style='padding:50px; text-align:center;'><h3>Booking Reference Not Found</h3><a href='index.php'>Return Home</a></div>");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Calculate nights
$checkInDate = new DateTime($booking['check_in']);
$checkOutDate = new DateTime($booking['check_out']);
$nights = $checkInDate->diff($checkOutDate)->days;
?>

<section class="section-padding">
    <div class="container" style="max-width: 800px;">
        <div style="text-align: center; margin-bottom: 40px;">
            <div style="width: 80px; height: 80px; background: rgba(16, 185, 129, 0.1); border: 2px solid var(--success); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; color: var(--success); font-size: 2.5rem;">
                <i class="fas fa-check"></i>
            </div>
            <span class="section-subtitle"><?php echo __t('confirm_success_title'); ?></span>
            <h2 class="elegant-title"><?php echo __t('confirm_secured'); ?></h2>
            <p style="color: var(--text-muted); margin-top: 10px;"><?php echo __t('confirm_email_sent', ['email' => htmlspecialchars($booking['guest_email'])]); ?></p>
        </div>

        <div class="booking-mgmt-card" id="printable-voucher">
            <div class="booking-voucher">
                <div class="booking-voucher-header">
                    <div>
                        <h3 style="font-family: var(--font-serif); color: var(--primary-gold); font-size: 1.4rem;"><?php echo strtoupper(__t('logo_main') . ' ' . __t('logo_sub')); ?></h3>
                        <span style="font-size: 0.65rem; letter-spacing: 2px; text-transform: uppercase; color: var(--text-muted);"><?php echo __t('confirm_voucher_title'); ?></span>
                    </div>
                    <div class="voucher-ref">
                        <span class="voucher-item-label"><?php echo __t('confirm_booking_ref'); ?></span>
                        <div class="voucher-ref-num"><?php echo htmlspecialchars($booking['booking_reference']); ?></div>
                    </div>
                </div>

                <div class="voucher-details-grid">
                    <div class="voucher-item">
                        <span class="voucher-item-label"><?php echo __t('confirm_guest_name'); ?></span>
                        <span class="voucher-item-val"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                    </div>
                    
                    <div class="voucher-item">
                        <span class="voucher-item-label"><?php echo __t('confirm_phone'); ?></span>
                        <span class="voucher-item-val"><?php echo htmlspecialchars($booking['guest_phone']); ?></span>
                    </div>
                    
                    <div class="voucher-item">
                        <span class="voucher-item-label"><?php echo __t('confirm_in'); ?></span>
                        <span class="voucher-item-val"><?php echo date('D, d M Y', strtotime($booking['check_in'])); ?> (14:00)</span>
                    </div>
                    
                    <div class="voucher-item">
                        <span class="voucher-item-label"><?php echo __t('confirm_out'); ?></span>
                        <span class="voucher-item-val"><?php echo date('D, d M Y', strtotime($booking['check_out'])); ?> (12:00)</span>
                    </div>
                    
                    <div class="voucher-item">
                        <span class="voucher-item-label"><?php echo __t('confirm_reserved'); ?></span>
                        <span class="voucher-item-val"><?php echo htmlspecialchars($booking['room_name']); ?></span>
                    </div>
                    
                    <div class="voucher-item">
                        <span class="voucher-item-label"><?php echo __t('confirm_count_nights'); ?></span>
                        <span class="voucher-item-val"><?php echo htmlspecialchars($booking['guests_count']); ?> Guest(s) / <?php echo $nights; ?> Night(s)</span>
                    </div>
                    
                    <?php if (!empty($booking['extra_services'])): ?>
                        <div class="voucher-item" style="grid-column: span 2;">
                            <span class="voucher-item-label"><?php echo __t('detail_extras_title'); ?></span>
                            <span class="voucher-item-val" style="color: var(--primary-gold); font-size: 0.85rem;"><i class="fas fa-plus-circle"></i> <?php echo htmlspecialchars($booking['extra_services']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($booking['promo_code'])): ?>
                        <div class="voucher-item">
                            <span class="voucher-item-label"><?php echo __t('detail_promo_code'); ?></span>
                            <span class="voucher-item-val" style="color: var(--success); font-weight: 600;"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($booking['promo_code']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="voucher-total">
                    <span class="voucher-total-label"><?php echo __t('confirm_total_paid'); ?></span>
                    <span class="voucher-total-val">฿<?php echo number_format($booking['total_price']); ?></span>
                </div>
            </div>
            
            <div class="btn-group no-print">
                <button onclick="window.print();" class="btn-secondary">
                    <i class="fas fa-print"></i> <?php echo __t('confirm_print'); ?>
                </button>
                <a href="my-bookings.php" class="btn-book-submit" style="display: inline-block; text-align: center; width: auto; text-decoration: none;">
                    <?php echo __t('my_bookings_manage'); ?>
                </a>
            </div>
        </div>

        <div style="text-align: center;" class="no-print">
            <a href="index.php" style="color: var(--primary-gold); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-home"></i> <?php echo __t('confirm_back_home'); ?></a>
        </div>
    </div>
</section>

<!-- Print style support -->
<style>
@media print {
    body {
        background: white !important;
        color: black !important;
    }
    header, footer, .no-print, div[style*="height: 80px"] {
        display: none !important;
    }
    .booking-voucher {
        border: 2px solid #000 !important;
        color: #000 !important;
        background: #fff !important;
    }
    .booking-voucher * {
        color: #000 !important;
    }
    .voucher-ref-num, .voucher-total-val {
        color: #d4af37 !important;
    }
    #printable-voucher {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        background: transparent !important;
    }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
