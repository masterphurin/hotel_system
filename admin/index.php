<?php
require_once __DIR__ . '/admin-header.php';

// Fetch statistics
try {
    // 1. Total Revenue (Paid bookings only)
    $stmt = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'confirmed' AND payment_status = 'paid'");
    $total_revenue = floatval($stmt->fetchColumn());

    // 2. Total Bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'");
    $total_bookings = intval($stmt->fetchColumn());

    // 3. Occupancy calculation
    $stmt = $pdo->query("SELECT SUM(total_rooms) FROM rooms");
    $total_inventory = intval($stmt->fetchColumn()) ?: 1; // Prevent division by zero
    
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND (? BETWEEN check_in AND check_out)");
    $stmt->execute([$today]);
    $occupied_count = intval($stmt->fetchColumn());
    
    $occupancy_rate = round(($occupied_count / $total_inventory) * 100);

    // 4. Total Reviews
    $stmt = $pdo->query("SELECT COUNT(*) FROM reviews");
    $total_reviews = intval($stmt->fetchColumn());

    // 5. Recent Bookings List
    $stmt = $pdo->query("SELECT b.*, r.name as room_name 
                         FROM bookings b 
                         JOIN rooms r ON b.room_id = r.id 
                         ORDER BY b.created_at DESC LIMIT 5");
    $recent_bookings = $stmt->fetchAll();

    // 6. Recent Reviews List
    $stmt = $pdo->query("SELECT rev.*, r.name as room_name 
                         FROM reviews rev 
                         JOIN rooms r ON rev.room_id = r.id 
                         ORDER BY rev.created_at DESC LIMIT 3");
    $recent_reviews = $stmt->fetchAll();

    // 7. Last 6 Months Revenue Query for Visual Charting
    $revenue_data = [];
    $months_labels = [];
    for ($i = 5; $i >= 0; $i--) {
        $month_start = date('Y-m-01 00:00:00', strtotime("-$i months"));
        $month_end = date('Y-m-t 23:59:59', strtotime("-$i months"));
        $month_label = date('M Y', strtotime("-$i months"));
        
        $r_stmt = $pdo->prepare("SELECT SUM(total_price) FROM bookings WHERE status = 'confirmed' AND payment_status = 'paid' AND created_at BETWEEN ? AND ?");
        $r_stmt->execute([$month_start, $month_end]);
        $val = floatval($r_stmt->fetchColumn()) ?: 0;
        
        $revenue_data[] = $val;
        $months_labels[] = $month_label;
    }

} catch (PDOException $e) {
    die("Statistics Fetch Error: " . $e->getMessage());
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1 class="elegant-title" style="font-size: 2rem; color: var(--primary-gold);">Dashboard Overview</h1>
        <p style="color: var(--text-muted); font-size: 0.85rem;">Welcome back, here is the performance metrics for Nom Tuay Resort.</p>
    </div>
    <div style="background: rgba(197, 168, 128, 0.05); border: 1px solid var(--border-light); padding: 10px 20px; font-size: 0.8rem; color: var(--text-muted);">
        <i class="far fa-clock"></i> Today: <strong><?php echo date('d M Y'); ?></strong>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h5>Total Revenue</h5>
            <div>฿<?php echo number_format($total_revenue); ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-coins text-gold"></i></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-info">
            <h5>Active Bookings</h5>
            <div><?php echo $total_bookings; ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-calendar-check text-gold"></i></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-info">
            <h5>Today Occupancy</h5>
            <div><?php echo $occupancy_rate; ?>%</div>
        </div>
        <div class="stat-icon"><i class="fas fa-percent text-gold"></i></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-info">
            <h5>Total Reviews</h5>
            <div><?php echo $total_reviews; ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-star-half-alt text-gold"></i></div>
    </div>
</div>

<!-- Charts Section (No. 4 Analytics recommendation) -->
<div style="background: var(--bg-card); border: 1px solid var(--border-light); padding: 30px; margin-bottom: 40px; border-radius: 4px;">
    <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--primary-gold); margin-bottom: 20px;">Monthly Booking Revenue Trends</h3>
    <div style="height: 320px; position: relative;">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    const labels = <?php echo json_encode($months_labels); ?>;
    const dataValues = <?php echo json_encode($revenue_data); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Monthly Paid Revenue (THB)',
                data: dataValues,
                borderColor: '#c5a880',
                backgroundColor: 'rgba(197, 168, 128, 0.15)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#c5a880',
                pointBorderColor: '#111',
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: '#a0a0a0',
                        font: {
                            family: "'Outfit', sans-serif"
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255,255,255,0.05)'
                    },
                    ticks: {
                        color: '#a0a0a0'
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(255,255,255,0.05)'
                    },
                    ticks: {
                        color: '#a0a0a0',
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    
    <!-- Recent Bookings Table -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--primary-gold);">Recent Booking Orders</h3>
            <a href="bookings.php" style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold); letter-spacing: 1px;">View All</a>
        </div>
        
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Stay Dates</th>
                        <th>Total</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_bookings) === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px;">No bookings made yet.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($recent_bookings as $booking): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--text-light);"><?php echo htmlspecialchars($booking['booking_reference']); ?></td>
                            <td>
                                <div style="font-weight: 500; color: var(--text-light);"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($booking['guest_email']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                            <td>
                                <div><?php echo date('d M', strtotime($booking['check_in'])); ?> - <?php echo date('d M Y', strtotime($booking['check_out'])); ?></div>
                            </td>
                            <td style="font-weight: 600; color: var(--primary-gold);">฿<?php echo number_format($booking['total_price']); ?></td>
                            <td>
                                <?php if ($booking['status'] === 'cancelled'): ?>
                                    <span class="status-badge status-cancelled">Cancelled</span>
                                <?php else: ?>
                                    <?php if ($booking['payment_status'] === 'paid'): ?>
                                        <span class="status-badge status-paid">Paid</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Pending</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Reviews Feed -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--primary-gold);">Recent Reviews</h3>
            <a href="reviews.php" style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold); letter-spacing: 1px;">Moderate</a>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php if (count($recent_reviews) === 0): ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-light); padding: 30px; text-align: center; font-size: 0.85rem; color: var(--text-muted);">
                    No reviews posted yet.
                </div>
            <?php endif; ?>
            <?php foreach ($recent_reviews as $rev): ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-light); padding: 20px; border-radius: 4px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <div>
                            <span style="font-weight: 600; color: var(--text-light); font-size: 0.9rem;"><?php echo htmlspecialchars($rev['guest_name']); ?></span>
                            <div style="font-size: 0.7rem; color: var(--text-muted);"><?php echo htmlspecialchars($rev['room_name']); ?></div>
                        </div>
                        <span style="background: rgba(197, 168, 128, 0.08); border: 1px solid var(--border-color); color: var(--accent-gold); font-size: 0.75rem; padding: 2px 6px;">
                            <i class="fas fa-star"></i> <?php echo number_format($rev['overall_rating'], 1); ?>
                        </span>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; font-style: italic;">
                        "<?php echo htmlspecialchars(substr($rev['comment'], 0, 100)) . (strlen($rev['comment']) > 100 ? '...' : ''); ?>"
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
