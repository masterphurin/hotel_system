<?php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: rooms.php");
    exit;
}

// Enforce guest login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['booking_error'] = "You must be signed in to make a reservation.";
    header("Location: login.php");
    exit;
}

$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
$check_in = $_POST['check_in'] ?? '';
$check_out = $_POST['check_out'] ?? '';
$guests_count = isset($_POST['guests_count']) ? intval($_POST['guests_count']) : 1;

// Override details using secure session values
$guest_name = $_SESSION['user_name'];
$guest_email = $_SESSION['user_email'];
$guest_phone = $_SESSION['user_phone'];

// Simple validations
if ($room_id <= 0 || empty($check_in) || empty($check_out)) {
    $_SESSION['booking_error'] = "Please fill in all booking fields correctly.";
    header("Location: room-detail.php?id=" . $room_id);
    exit;
}

// Date validation
$today = date('Y-m-d');
if ($check_in < $today) {
    $_SESSION['booking_error'] = "Check-in date cannot be in the past.";
    header("Location: room-detail.php?id=" . $room_id);
    exit;
}
if ($check_out <= $check_in) {
    $_SESSION['booking_error'] = "Check-out date must be after the check-in date.";
    header("Location: room-detail.php?id=" . $room_id);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Fetch room details (with write lock to prevent race conditions during real-time booking)
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();

    if (!$room) {
        $pdo->rollBack();
        $_SESSION['booking_error'] = "Invalid room selected.";
        header("Location: rooms.php");
        exit;
    }

    // 2. Count overlapping active bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status = 'confirmed' AND NOT (check_out <= ? OR check_in >= ?)");
    $stmt->execute([$room_id, $check_in, $check_out]);
    $booked_count = $stmt->fetchColumn();

    // Check remaining inventory
    if ($booked_count >= $room['total_rooms']) {
        $pdo->rollBack();
        $_SESSION['booking_error'] = "We are sorry, but this room is no longer available for your selected dates.";
        header("Location: room-detail.php?id=" . $room_id);
        exit;
    }

    // 3. Calculate Nights and Total Price
    $checkInDate = new DateTime($check_in);
    $checkOutDate = new DateTime($check_out);
    $nights = $checkInDate->diff($checkOutDate)->days;
    
    // Calculate extra services
    $extra_services_selected = [];
    $extras_price = 0;
    if (isset($_POST['extra_breakfast']) && $_POST['extra_breakfast'] == '1') {
        $extras_price += 500 * $nights;
        $extra_services_selected[] = "Breakfast (฿" . (500 * $nights) . ")";
    }
    if (isset($_POST['extra_shuttle']) && $_POST['extra_shuttle'] == '1') {
        $extras_price += 1200;
        $extra_services_selected[] = "Airport Shuttle (฿1,200)";
    }
    if (isset($_POST['extra_spa']) && $_POST['extra_spa'] == '1') {
        $extras_price += 2000;
        $extra_services_selected[] = "Spa Access (฿2,000)";
    }
    
    $extra_services_string = !empty($extra_services_selected) ? implode(", ", $extra_services_selected) : null;
    
    // Calculate base room price
    $room_total = $nights * $room['price_per_night'];
    
    // Calculate promo code discount
    $promo_code = trim($_POST['applied_promo_code'] ?? '');
    $discount_pct = 0;
    if (strtoupper($promo_code) === 'WELCOME10') {
        $discount_pct = 10;
    } elseif (strtoupper($promo_code) === 'PROMO15') {
        $discount_pct = 15;
    }
    
    $discount_value = ($room_total * $discount_pct) / 100;
    $total_price = ($room_total - $discount_value) + $extras_price;

    // 4. Generate unique Booking Reference
    $ref_prefix = "RSV-";
    $ref_suffix = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    $booking_reference = $ref_prefix . $ref_suffix;

    // Double check unique constraint (highly unlikely collision but safe)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_reference = ?");
    $stmt->execute([$booking_reference]);
    while ($stmt->fetchColumn() > 0) {
        $ref_suffix = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        $booking_reference = $ref_prefix . $ref_suffix;
        $stmt->execute([$booking_reference]);
    }

    // 5. Insert Booking record
    $stmt = $pdo->prepare("INSERT INTO bookings (booking_reference, room_id, guest_name, guest_email, guest_phone, check_in, check_out, guests_count, total_price, extra_services, promo_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
    $stmt->execute([
        $booking_reference,
        $room_id,
        $guest_name,
        $guest_email,
        $guest_phone,
        $check_in,
        $check_out,
        $guests_count,
        $total_price,
        $extra_services_string,
        !empty($promo_code) ? strtoupper($promo_code) : null
    ]);

    $pdo->commit();
    
    // Redirect to payment screen
    header("Location: payment.php?ref=" . $booking_reference);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['booking_error'] = "System Error processing your booking. Please try again later. Details: " . $e->getMessage();
    header("Location: room-detail.php?id=" . $room_id);
    exit;
}
?>
