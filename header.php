<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lang.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __t('meta_title'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(__t('meta_description'), ENT_QUOTES); ?>">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        // Immediately apply theme before document rendering to prevent flash of dark theme
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            if (savedTheme === 'light') {
                document.documentElement.classList.add('light-theme');
            }
        })();
    </script>
</head>
<body>

<div class="luxury-pattern-bg"></div>
<div class="luxury-bg-ambient">
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="ambient-orb orb-3"></div>
</div>

<header id="main-header">
    <div class="container">
        <nav class="navbar">
            <div class="logo">
                <a href="index.php">
                    <span class="logo-main"><?php echo __t('logo_main'); ?></span>
                    <span class="logo-sub"><?php echo __t('logo_sub'); ?></span>
                </a>
            </div>
            
            <ul class="nav-links" id="nav-links">
                <li><a href="index.php"><?php echo __t('nav_home'); ?></a></li>
                <li><a href="rooms.php"><?php echo __t('nav_rooms'); ?></a></li>
                <li><a href="my-bookings.php"><?php echo __t('nav_my_bookings'); ?></a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li style="color: var(--primary-gold); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; align-self: center; letter-spacing: 1px;"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?></li>
                    <li><a href="logout.php" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> <?php echo __t('nav_logout'); ?></a></li>
                <?php else: ?>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> <?php echo __t('nav_login'); ?></a></li>
                <?php endif; ?>
            </ul>
            
            <div style="display: flex; align-items: center; gap: 15px;">
                <!-- Language Switcher -->
                <?php if ($current_lang === 'th'): ?>
                    <a href="?lang=en" style="font-size: 0.8rem; font-weight: 700; color: var(--primary-gold); border: 1px solid var(--border-light); padding: 5px 10px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none;"><i class="fas fa-globe"></i> EN</a>
                <?php else: ?>
                    <a href="?lang=th" style="font-size: 0.8rem; font-weight: 700; color: var(--primary-gold); border: 1px solid var(--border-light); padding: 5px 10px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none;"><i class="fas fa-globe"></i> TH</a>
                <?php endif; ?>

                <!-- Theme Toggle Button -->
                <button id="theme-toggle-btn" style="background: transparent; border: 1px solid var(--border-light); color: var(--primary-gold); width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition-smooth);">
                    <i class="fas fa-sun" id="theme-icon"></i>
                </button>

                <a href="rooms.php" class="btn-book-nav"><?php echo __t('nav_book_now'); ?></a>
            </div>
            
            <div class="menu-toggle" id="menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </div>
</header>

<div style="height: 80px;"></div> <!-- Spacer for fixed header -->
