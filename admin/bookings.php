<?php
require_once __DIR__ . '/admin-header.php';

$message = "";
$error = "";

// 1. Process Admin Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $booking_id = intval($_POST['booking_id']);
    
    try {
        if ($action === 'mark_paid') {
            $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'paid', payment_method = 'admin_cash', payment_date = NOW() WHERE id = ?");
            $stmt->execute([$booking_id]);
            $message = "Booking marked as PAID successfully.";
        } 
        elseif ($action === 'cancel_booking') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$booking_id]);
            $message = "Booking cancelled successfully.";
        } 
        elseif ($action === 'delete_booking') {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            $message = "Booking record deleted successfully.";
        }
    } catch (PDOException $e) {
        $error = "Action failed: " . $e->getMessage();
    }
}

// 2. Fetch and filter bookings
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$payment_filter = $_GET['payment_status'] ?? '';

try {
    $sql = "SELECT b.*, r.name as room_name 
            FROM bookings b 
            JOIN rooms r ON b.room_id = r.id 
            WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (b.booking_reference LIKE :search OR b.guest_name LIKE :search2 OR b.guest_email LIKE :search3)";
        $params[':search'] = "%$search%";
        $params[':search2'] = "%$search%";
        $params[':search3'] = "%$search%";
    }
    
    if ($status_filter !== '') {
        $sql .= " AND b.status = :status";
        $params[':status'] = $status_filter;
    }
    
    if ($payment_filter !== '') {
        $sql .= " AND b.payment_status = :payment_status";
        $params[':payment_status'] = $payment_filter;
    }

    $sql .= " ORDER BY b.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query Error: " . $e->getMessage());
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1 class="elegant-title" style="font-size: 2rem; color: var(--primary-gold);">Manage Reservations</h1>
        <p style="color: var(--text-muted); font-size: 0.85rem;">Review, modify booking status, confirm payments or cancel stays.</p>
    </div>
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

<!-- Filters Form -->
<div class="booking-search-bar" style="padding: 20px; margin-bottom: 30px; border-color: var(--border-light);">
    <form action="" method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr 120px; gap: 15px; align-items: flex-end;">
        <div class="search-group">
            <label>Search Query</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Ref, Guest Name, Email">
        </div>
        
        <div class="search-group">
            <label>Stay Status</label>
            <select name="status">
                <option value="">All Statuses</option>
                <option value="confirmed" <?php echo ($status_filter === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        
        <div class="search-group">
            <label>Payment Status</label>
            <select name="payment_status">
                <option value="">All Payments</option>
                <option value="pending" <?php echo ($payment_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="paid" <?php echo ($payment_filter === 'paid') ? 'selected' : ''; ?>>Paid</option>
            </select>
        </div>
        
        <button type="submit" class="btn-search" style="height: 48px; width: 100%;">Filter</button>
    </form>
</div>

<!-- Bookings Table -->
<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Ref</th>
                <th>Guest Details</th>
                <th>Room Type</th>
                <th>Stay Dates</th>
                <th>Total Price</th>
                <th>Stay Status</th>
                <th>Payment Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($bookings) === 0): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">No reservations found matching the filters.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($bookings as $b): 
                $checkInDate = new DateTime($b['check_in']);
                $checkOutDate = new DateTime($b['check_out']);
                $nights = $checkInDate->diff($checkOutDate)->days;
            ?>
                <tr>
                    <td style="font-weight: 600; color: var(--text-light);"><?php echo htmlspecialchars($b['booking_reference']); ?></td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-light);"><?php echo htmlspecialchars($b['guest_name']); ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($b['guest_email']); ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($b['guest_phone']); ?></div>
                    </td>
                    <td>
                        <div><?php echo htmlspecialchars($b['room_name']); ?></div>
                        <?php if (!empty($b['extra_services'])): ?>
                            <div style="font-size: 0.75rem; color: var(--primary-gold); margin-top: 4px;" title="Services Bought">
                                <i class="fas fa-plus-circle"></i> <?php echo htmlspecialchars($b['extra_services']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($b['promo_code'])): ?>
                            <div style="font-size: 0.75rem; color: var(--success); margin-top: 2px;" title="Promo Code Used">
                                <i class="fas fa-tag"></i> Code: <?php echo htmlspecialchars($b['promo_code']); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><strong>In:</strong> <?php echo date('d M Y', strtotime($b['check_in'])); ?></div>
                        <div><strong>Out:</strong> <?php echo date('d M Y', strtotime($b['check_out'])); ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $nights; ?> Night(s) / <?php echo htmlspecialchars($b['guests_count']); ?> Guest(s)</div>
                    </td>
                    <td style="font-weight: 600; color: var(--primary-gold);">฿<?php echo number_format($b['total_price']); ?></td>
                    <td>
                        <?php if ($b['status'] === 'confirmed'): ?>
                            <span class="status-badge status-confirmed">Confirmed</span>
                        <?php else: ?>
                            <span class="status-badge status-cancelled">Cancelled</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($b['payment_status'] === 'paid'): ?>
                            <span class="status-badge status-paid">Paid</span>
                            <div style="font-size: 0.65rem; color: var(--text-muted); margin-top: 4px;">via <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $b['payment_method']))); ?></div>
                        <?php else: ?>
                            <span class="status-badge status-pending">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <?php if ($b['payment_status'] !== 'paid' && $b['status'] === 'confirmed'): ?>
                                <form action="" method="POST" onsubmit="return confirm('Confirm payment for <?php echo $b['booking_reference']; ?>?');">
                                    <input type="hidden" name="action" value="mark_paid">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" class="action-btn" title="Confirm Payment"><i class="fas fa-check"></i> Paid</button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($b['status'] === 'confirmed'): ?>
                                <form action="" method="POST" onsubmit="return confirm('Are you sure you want to CANCEL booking <?php echo $b['booking_reference']; ?>?');">
                                    <input type="hidden" name="action" value="cancel_booking">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" class="action-btn btn-delete" title="Cancel Booking"><i class="fas fa-times"></i> Cancel</button>
                                </form>
                            <?php endif; ?>
                            
                            <form action="" method="POST" onsubmit="return confirm('Are you sure you want to permanently DELETE this booking record?');">
                                <input type="hidden" name="action" value="delete_booking">
                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                <button type="submit" class="action-btn btn-delete" title="Delete Permanent"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
