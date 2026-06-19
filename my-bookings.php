<?php
require_once __DIR__ . '/header.php';

$booking = null;
$my_bookings = [];
$search_error = "";
$search_success = "";

// 1. Require Guest Login
if (!isset($_SESSION['user_id'])) {
    // Show login requirement notice
    ?>
    <section class="section-padding">
        <div class="container" style="max-width: 600px; text-align: center;">
            <div class="booking-mgmt-card" style="padding: 40px 30px;">
                <div style="font-size: 3rem; color: var(--primary-gold); margin-bottom: 20px;">
                    <i class="fas fa-lock"></i>
                </div>
                <h2 class="elegant-title" style="font-size: 1.8rem; margin-bottom: 15px; color: var(--primary-gold);">Membership Access Required</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 30px; line-height: 1.6;">
                    Please sign in to your membership account to view, modify, or cancel your reservations.
                </p>
                <a href="login.php?redirect=my-bookings.php" class="btn-search" style="display: inline-flex; text-decoration: none; align-items: center; justify-content: center; width: auto; padding: 12px 30px; font-size: 0.85rem;">
                    Sign In / Register
                </a>
            </div>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/footer.php';
    exit;
}

$user_email = $_SESSION['user_email'];
$ref_input = $_REQUEST['ref'] ?? '';

// 2. Fetch specific booking if reference requested
if (!empty($ref_input)) {
    try {
        $stmt = $pdo->prepare("SELECT b.*, r.name as room_name, r.type as room_type, r.price_per_night, r.image_url 
                               FROM bookings b 
                               JOIN rooms r ON b.room_id = r.id 
                               WHERE b.booking_reference = ? AND b.guest_email = ?");
        $stmt->execute([$ref_input, $user_email]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            $search_error = "No active booking found matching reference: " . htmlspecialchars($ref_input);
        }
    } catch (PDOException $e) {
        $search_error = "Database Error: " . $e->getMessage();
    }
}

// 3. Cancel Booking Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $cancel_ref = $_POST['cancel_ref'] ?? '';
    
    try {
        // Double check authorization
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_reference = ? AND guest_email = ?");
        $stmt->execute([$cancel_ref, $user_email]);
        $check = $stmt->fetch();
        
        if ($check) {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_reference = ?");
            $stmt->execute([$cancel_ref]);
            $search_success = "Your reservation has been successfully cancelled.";
            $booking = null; // Clear booking display
            $ref_input = '';
        } else {
            $search_error = "Authorization failed. Could not cancel booking.";
        }
    } catch (PDOException $e) {
        $search_error = "Cancellation Error: " . $e->getMessage();
    }
}

// 4. Modify Booking Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modify') {
    $mod_ref = $_POST['mod_ref'] ?? '';
    $new_in = $_POST['new_check_in'] ?? '';
    $new_out = $_POST['new_check_out'] ?? '';
    $new_guests = intval($_POST['new_guests'] ?? 1);
    
    $today = date('Y-m-d');
    
    if (empty($new_in) || empty($new_out) || $new_in < $today || $new_out <= $new_in) {
        $search_error = "Please provide valid future dates. Check-out must be after check-in.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Check original booking with lock
            $stmt = $pdo->prepare("SELECT b.*, r.price_per_night, r.total_rooms 
                                   FROM bookings b 
                                   JOIN rooms r ON b.room_id = r.id 
                                   WHERE b.booking_reference = ? AND b.guest_email = ? FOR UPDATE");
            $stmt->execute([$mod_ref, $user_email]);
            $orig = $stmt->fetch();
            
            if ($orig) {
                // Check room inventory for new dates, excluding this booking itself
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings 
                                       WHERE room_id = ? 
                                         AND status = 'confirmed' 
                                         AND booking_reference != ? 
                                         AND NOT (check_out <= ? OR check_in >= ?)");
                $stmt->execute([$orig['room_id'], $mod_ref, $new_in, $new_out]);
                $booked_count = $stmt->fetchColumn();
                
                if ($booked_count < $orig['total_rooms']) {
                    // Calculate new price
                    $checkInDate = new DateTime($new_in);
                    $checkOutDate = new DateTime($new_out);
                    $nights = $checkInDate->diff($checkOutDate)->days;
                    $new_total = $nights * $orig['price_per_night'];
                    
                    // Update the booking
                    $stmt = $pdo->prepare("UPDATE bookings SET check_in = ?, check_out = ?, guests_count = ?, total_price = ?, status = 'confirmed' WHERE booking_reference = ?");
                    $stmt->execute([$new_in, $new_out, $new_guests, $new_total, $mod_ref]);
                    
                    $pdo->commit();
                    $search_success = "Your reservation has been successfully updated.";
                    
                    // Reload booking info to display updated data
                    $stmt = $pdo->prepare("SELECT b.*, r.name as room_name, r.type as room_type, r.price_per_night, r.image_url 
                                           FROM bookings b 
                                           JOIN rooms r ON b.room_id = r.id 
                                           WHERE b.booking_reference = ? AND b.guest_email = ?");
                    $stmt->execute([$mod_ref, $user_email]);
                    $booking = $stmt->fetch();
                } else {
                    $pdo->rollBack();
                    $search_error = "The room is not available for the newly selected dates.";
                }
            } else {
                $pdo->rollBack();
                $search_error = "Authorization check failed.";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $search_error = "System Error updating reservation: " . $e->getMessage();
        }
    }
}

// 5. Fetch all bookings for the list view if a specific reference is not loaded
if (!$booking) {
    try {
        $stmt = $pdo->prepare("SELECT b.*, r.name as room_name, r.type as room_type 
                               FROM bookings b 
                               JOIN rooms r ON b.room_id = r.id 
                               WHERE b.guest_email = ? 
                               ORDER BY b.created_at DESC");
        $stmt->execute([$user_email]);
        $my_bookings = $stmt->fetchAll();
    } catch (PDOException $e) {
        $search_error = "Database Error fetching listing: " . $e->getMessage();
    }
}
?>

<section class="section-padding">
    <div class="container" style="max-width: 900px;">
        <div class="section-title-wrapper">
            <span class="section-subtitle">Manage Your Stay</span>
            <h2 class="section-title">Reservation Control Panel</h2>
        </div>
        
        <?php if ($search_success): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); padding: 15px; color: var(--success); margin-bottom: 30px; text-align: center;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($search_success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($search_error): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); padding: 15px; color: var(--danger); margin-bottom: 30px; text-align: center;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($search_error); ?>
            </div>
        <?php endif; ?>

        <?php if (!$booking): ?>
            <!-- Bookings List View -->
            <div class="booking-mgmt-card" style="padding: 30px;">
                <h3 style="font-family: var(--font-serif); color: var(--primary-gold); font-size: 1.3rem; margin-bottom: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">My Reservations</h3>
                
                <?php if (count($my_bookings) === 0): ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 30px;">
                        You do not have any active or past bookings. <a href="rooms.php" style="color: var(--primary-gold); font-weight: 600;">Browse Rooms</a>
                    </p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-light); color: var(--primary-gold);">
                                    <th style="padding: 12px 10px;">Reference</th>
                                    <th style="padding: 12px 10px;">Room</th>
                                    <th style="padding: 12px 10px;">Check-In</th>
                                    <th style="padding: 12px 10px;">Check-Out</th>
                                    <th style="padding: 12px 10px;">Total Price</th>
                                    <th style="padding: 12px 10px;">Status</th>
                                    <th style="padding: 12px 10px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_bookings as $mb): ?>
                                    <tr style="border-bottom: 1px solid var(--border-light); color: var(--text-muted);">
                                        <td style="padding: 15px 10px; font-weight: 600; color: var(--text-light);"><?php echo htmlspecialchars($mb['booking_reference']); ?></td>
                                        <td style="padding: 15px 10px;">
                                            <div><?php echo htmlspecialchars($mb['room_name']); ?></div>
                                            <?php if (!empty($mb['extra_services'])): ?>
                                                <div style="font-size: 0.75rem; color: var(--primary-gold); margin-top: 4px;" title="Services Bought">
                                                    <i class="fas fa-plus-circle"></i> <?php echo htmlspecialchars($mb['extra_services']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($mb['promo_code'])): ?>
                                                <div style="font-size: 0.75rem; color: var(--success); margin-top: 2px;" title="Promo Code Used">
                                                    <i class="fas fa-tag"></i> Code: <?php echo htmlspecialchars($mb['promo_code']); ?>
                                                </div>
                                            <?php endif; ?>
                                         </td>
                                        <td style="padding: 15px 10px;"><?php echo date('d M Y', strtotime($mb['check_in'])); ?></td>
                                        <td style="padding: 15px 10px;"><?php echo date('d M Y', strtotime($mb['check_out'])); ?></td>
                                        <td style="padding: 15px 10px; color: var(--primary-gold); font-weight: 600;">฿<?php echo number_format($mb['total_price']); ?></td>
                                        <td style="padding: 15px 10px;">
                                            <?php if ($mb['status'] === 'cancelled'): ?>
                                                <span style="color: var(--danger); font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Cancelled</span>
                                            <?php else: ?>
                                                <?php if ($mb['payment_status'] === 'paid'): ?>
                                                    <span style="color: var(--success); font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Paid</span>
                                                <?php else: ?>
                                                    <span style="color: #f59e0b; font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Pending Payment</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 15px 10px; text-align: right;">
                                            <?php if ($mb['status'] !== 'cancelled'): ?>
                                                <a href="my-bookings.php?ref=<?php echo urlencode($mb['booking_reference']); ?>" class="btn-book-nav" style="padding: 5px 12px; font-size: 0.7rem;">Manage</a>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 0.7rem;">No Action</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: 
            $checkInDate = new DateTime($booking['check_in']);
            $checkOutDate = new DateTime($booking['check_out']);
            $nights = $checkInDate->diff($checkOutDate)->days;
        ?>
            <!-- Specific Reservation Details View -->
            <div class="booking-mgmt-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-family: var(--font-serif); color: var(--primary-gold); font-size: 1.3rem;">Current Reservation Details</h3>
                    <div>
                        <?php if ($booking['status'] === 'confirmed'): ?>
                            <?php if ($booking['payment_status'] === 'paid'): ?>
                                <span style="background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); padding: 4px 10px; font-size: 0.8rem; font-weight: 600;">Confirmed (Paid)</span>
                            <?php else: ?>
                                <span style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); padding: 4px 10px; font-size: 0.8rem; font-weight: 600;">Pending Payment</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); padding: 4px 10px; font-size: 0.8rem; font-weight: 600;">Cancelled</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="booking-voucher" style="margin-bottom: 30px;">
                    <div class="booking-voucher-header">
                        <div>
                            <span style="font-size: 0.65rem; text-transform: uppercase; color: var(--text-muted);">Accommodation</span>
                            <h4 style="font-family: var(--font-serif); font-size: 1.2rem; color: var(--text-light);"><?php echo htmlspecialchars($booking['room_name']); ?></h4>
                        </div>
                        <div class="voucher-ref">
                            <span class="voucher-item-label">Booking Reference</span>
                            <div class="voucher-ref-num" style="font-size: 1.2rem;"><?php echo htmlspecialchars($booking['booking_reference']); ?></div>
                        </div>
                    </div>

                    <div class="voucher-details-grid" style="grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="voucher-item">
                            <span class="voucher-item-label">Check-In</span>
                            <span class="voucher-item-val"><?php echo date('d M Y', strtotime($booking['check_in'])); ?> (14:00)</span>
                        </div>
                        <div class="voucher-item">
                            <span class="voucher-item-label">Check-Out</span>
                            <span class="voucher-item-val"><?php echo date('d M Y', strtotime($booking['check_out'])); ?> (12:00)</span>
                        </div>
                        <div class="voucher-item">
                            <span class="voucher-item-label">Guest Details</span>
                            <span class="voucher-item-val"><?php echo htmlspecialchars($booking['guest_name']); ?> (<?php echo htmlspecialchars($booking['guests_count']); ?> Guest(s))</span>
                        </div>
                        <div class="voucher-item">
                            <span class="voucher-item-label">Total Price</span>
                            <span class="voucher-item-val" style="color: var(--primary-gold);">฿<?php echo number_format($booking['total_price']); ?></span>
                        </div>
                        <?php if (!empty($booking['extra_services'])): ?>
                            <div class="voucher-item" style="grid-column: span 2;">
                                <span class="voucher-item-label">Extra Services</span>
                                <span class="voucher-item-val" style="color: var(--primary-gold); font-size: 0.85rem;"><i class="fas fa-plus-circle"></i> <?php echo htmlspecialchars($booking['extra_services']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($booking['promo_code'])): ?>
                            <div class="voucher-item">
                                <span class="voucher-item-label">Promo Code Applied</span>
                                <span class="voucher-item-val" style="color: var(--success); font-weight: 600;"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($booking['promo_code']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($booking['status'] === 'confirmed'): ?>
                    <!-- Modification & Cancellation forms -->
                    <div style="border-top: 1px solid var(--border-light); padding-top: 30px;">
                        <h4 style="font-family: var(--font-serif); margin-bottom: 20px; color: var(--primary-gold);">Modify Booking Dates</h4>
                        
                        <form action="" method="POST" style="display: grid; grid-template-columns: 1fr 1fr 100px; gap: 15px; align-items: flex-end; margin-bottom: 30px;">
                            <input type="hidden" name="action" value="modify">
                            <input type="hidden" name="mod_ref" value="<?php echo htmlspecialchars($booking['booking_reference']); ?>">
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label style="font-size: 0.75rem; text-transform: uppercase;">New Check-In</label>
                                <input type="date" name="new_check_in" min="<?php echo date('Y-m-d'); ?>" value="<?php echo $booking['check_in']; ?>" required style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 10px; outline: none;">
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label style="font-size: 0.75rem; text-transform: uppercase;">New Check-Out</label>
                                <input type="date" name="new_check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" value="<?php echo $booking['check_out']; ?>" required style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 10px; outline: none;">
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label style="font-size: 0.75rem; text-transform: uppercase;">Guests</label>
                                <select name="new_guests" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 10px; outline: none; height: 38px;">
                                    <option value="1" <?php echo ($booking['guests_count'] == 1) ? 'selected' : ''; ?>>1</option>
                                    <option value="2" <?php echo ($booking['guests_count'] == 2) ? 'selected' : ''; ?>>2</option>
                                    <option value="3" <?php echo ($booking['guests_count'] == 3) ? 'selected' : ''; ?>>3</option>
                                    <option value="4" <?php echo ($booking['guests_count'] == 4) ? 'selected' : ''; ?>>4</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn-secondary" style="grid-column: span 3; padding: 12px; margin-top: 10px;">Update Stay Dates</button>
                        </form>

                        <div style="display: flex; gap: 15px; border-top: 1px dashed var(--border-light); padding-top: 25px; align-items: center; justify-content: space-between;">
                            <div>
                                <h4 style="font-family: var(--font-serif); color: var(--danger); font-size: 1.1rem; margin-bottom: 5px;"><?php echo __t('my_bookings_cancel_stay'); ?></h4>
                                <p style="font-size: 0.8rem; color: var(--text-muted);"><?php echo __t('my_bookings_cancel_msg'); ?></p>
                            </div>
                            
                            <form action="" method="POST" onsubmit="return confirm('<?php echo addslashes(__t('my_bookings_cancel_confirm')); ?>');">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="cancel_ref" value="<?php echo htmlspecialchars($booking['booking_reference']); ?>">
                                <button type="submit" class="btn-danger"><?php echo __t('my_bookings_cancel_stay'); ?></button>
                            </form>
                        </div>
                        
                        <?php if ($booking['payment_status'] !== 'paid'): ?>
                            <div style="margin-top: 25px; border-top: 1px dashed var(--border-light); padding-top: 20px; text-align: center;">
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;"><?php echo __t('my_bookings_unpaid'); ?></p>
                                <a href="payment.php?ref=<?php echo urlencode($booking['booking_reference']); ?>" class="btn-search" style="display: inline-flex; text-decoration: none; padding: 10px 25px; font-size: 0.8rem; height: auto; width: auto; align-items: center; justify-content: center;"><?php echo __t('my_bookings_complete_pay'); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 30px; text-align: center; border-top: 1px solid var(--border-light); padding-top: 20px;">
                    <a href="my-bookings.php" style="color: var(--primary-gold); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1.5px;"><i class="fas fa-list"></i> <?php echo __t('my_bookings_back_list'); ?></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
