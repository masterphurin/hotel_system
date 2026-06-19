<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security Check: Redirect to login if not authenticated
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Automatically define $current_page if not set by the individual page
$current_page = $current_page ?? basename($_SERVER['PHP_SELF']);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lang.php';
?>
<!DOCTYPE html>
<html lang="en" class="light-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nom Tuay Resort | Control Panel</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS (Extend base style) -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Admin custom layout expansions */
        .admin-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }
        .admin-sidebar {
            background: var(--bg-card);
            border-right: 1px solid var(--border-light);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            gap: 40px;
        }
        .admin-main {
            padding: 40px;
            background: var(--bg-dark);
            overflow-y: auto;
        }
        .admin-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .admin-menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            font-size: 0.9rem;
            color: var(--text-muted);
            border-left: 3px solid transparent;
        }
        .admin-menu a:hover, .admin-menu li.active a {
            color: var(--primary-gold);
            background: rgba(197, 168, 128, 0.05);
            border-left-color: var(--primary-gold);
        }
        /* Stats dashboard cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            padding: 25px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stat-info h5 {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .stat-info div {
            font-family: var(--font-serif);
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-gold);
        }
        .stat-icon {
            font-size: 2.2rem;
            color: rgba(197, 168, 128, 0.15);
        }
        /* Admin table elements */
        .admin-table-container {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: 4px;
            overflow-x: auto;
            margin-top: 25px;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.85rem;
        }
        .admin-table th {
            background: var(--bg-dark);
            color: var(--primary-gold);
            font-weight: 600;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-light);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.75rem;
        }
        .admin-table td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-light);
            color: var(--text-muted);
            vertical-align: middle;
        }
        .admin-table tr:hover td {
            background: rgba(255, 255, 255, 0.01);
            color: var(--text-light);
        }
        .status-badge {
            padding: 4px 8px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-confirmed { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
        .status-cancelled { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); }
        .status-paid { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
        
        .action-btn {
            background: transparent;
            border: 1px solid var(--border-light);
            color: var(--text-muted);
            padding: 6px 12px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: var(--transition-smooth);
        }
        .action-btn:hover {
            border-color: var(--primary-gold);
            color: var(--primary-gold);
        }
        .action-btn.btn-delete:hover {
            border-color: var(--danger);
            color: var(--danger);
        }
        
        /* Modal or Form Styles */
        .admin-form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
        }
        .admin-form-group label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--primary-gold);
        }
        .admin-form-group input, .admin-form-group select, .admin-form-group textarea {
            background: var(--bg-dark);
            border: 1px solid var(--border-light);
            color: var(--text-light);
            padding: 12px;
            outline: none;
        }
        .admin-form-group input:focus, .admin-form-group select:focus, .admin-form-group textarea:focus {
            border-color: var(--primary-gold);
        }
    </style>
</head>
<body>

<?php if ($current_page !== 'login.php'): ?>
<div class="admin-layout">
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
        <div class="logo">
            <a href="index.php">
                <span class="logo-main" style="font-size: 1.25rem;">NT CONTROL</span>
                <span class="logo-sub" style="font-size: 0.55rem; letter-spacing: 2px;">Administrator Deck</span>
            </a>
        </div>
        
        <ul class="admin-menu">
            <li class="<?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">
                <a href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            </li>
            <li class="<?php echo ($current_page === 'bookings.php') ? 'active' : ''; ?>">
                <a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
            </li>
            <li class="<?php echo ($current_page === 'rooms.php') ? 'active' : ''; ?>">
                <a href="rooms.php"><i class="fas fa-bed"></i> Rooms Management</a>
            </li>
            <li class="<?php echo ($current_page === 'landmarks.php') ? 'active' : ''; ?>">
                <a href="landmarks.php"><i class="fas fa-map-marked-alt"></i> Landmarks & Map</a>
            </li>
            <li class="<?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
                <a href="settings.php"><i class="fas fa-cogs"></i> Homepage Settings</a>
            </li>
            <li class="<?php echo ($current_page === 'reviews.php') ? 'active' : ''; ?>">
                <a href="reviews.php"><i class="fas fa-star-half-alt"></i> Moderation</a>
            </li>
            <li>
                <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Website</a>
            </li>
        </ul>
        
        <div style="margin-top: auto; border-top: 1px solid var(--border-light); padding-top: 20px;">
            <div style="font-size: 0.8rem; color: var(--text-light); margin-bottom: 10px;">
                <i class="fas fa-user-shield text-gold"></i> <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
            </div>
            <a href="logout.php" style="font-size: 0.75rem; text-transform: uppercase; color: var(--danger); letter-spacing: 1px;"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>
    </aside>
    
    <!-- Main workspace -->
    <main class="admin-main">
<?php endif; ?>
