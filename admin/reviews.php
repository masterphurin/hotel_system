<?php
require_once __DIR__ . '/admin-header.php';

$message = "";
$error = "";

// 1. Handle deletion of review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $review_id = intval($_POST['review_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $message = "Review deleted successfully.";
    } catch (PDOException $e) {
        $error = "Failed to delete review: " . $e->getMessage();
    }
}

// 2. Fetch all reviews
try {
    $stmt = $pdo->query("SELECT rev.*, r.name as room_name 
                         FROM reviews rev 
                         JOIN rooms r ON rev.room_id = r.id 
                         ORDER BY rev.created_at DESC");
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query Error: " . $e->getMessage());
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1 class="elegant-title" style="font-size: 2rem; color: var(--primary-gold);">Guest Review Moderation</h1>
        <p style="color: var(--text-muted); font-size: 0.85rem;">Read guest feedback, monitor rating metrics, and moderate comments.</p>
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

<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Guest</th>
                <th>Room Type</th>
                <th>Ratings (Clean / Service / Value)</th>
                <th>Comment</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($reviews) === 0): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">No reviews found in database.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($reviews as $rev): ?>
                <tr>
                    <td><?php echo date('d M Y H:i', strtotime($rev['created_at'])); ?></td>
                    <td style="font-weight: 600; color: var(--text-light);"><?php echo htmlspecialchars($rev['guest_name']); ?></td>
                    <td><?php echo htmlspecialchars($rev['room_name']); ?></td>
                    <td>
                        <div style="margin-bottom: 5px;">
                            <span style="background: rgba(197, 168, 128, 0.08); border: 1px solid var(--border-color); color: var(--accent-gold); font-size: 0.85rem; padding: 2px 6px; font-weight: bold;">
                                <i class="fas fa-star"></i> <?php echo number_format($rev['overall_rating'], 1); ?>
                            </span>
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">
                            Clean: <?php echo number_format($rev['rating_cleanliness'], 1); ?> | 
                            Service: <?php echo number_format($rev['rating_service'], 1); ?> | 
                            Value: <?php echo number_format($rev['rating_value'], 1); ?>
                        </div>
                    </td>
                    <td>
                        <p style="font-size: 0.85rem; line-height: 1.5; color: var(--text-muted); max-width: 400px;"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                        <?php if ($rev['image_url']): ?>
                            <div style="margin-top: 10px;">
                                <a href="../<?php echo htmlspecialchars($rev['image_url']); ?>" target="_blank">
                                    <img src="../<?php echo htmlspecialchars($rev['image_url']); ?>" alt="Review attachment" style="width: 60px; height: 60px; object-fit: cover; border: 1px solid var(--border-light);">
                                </a>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <form action="" method="POST" onsubmit="return confirm('Are you sure you want to permanently DELETE this guest review?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="review_id" value="<?php echo $rev['id']; ?>">
                            <button type="submit" class="action-btn btn-delete" title="Delete Review"><i class="fas fa-trash-alt"></i> Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
