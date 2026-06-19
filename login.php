<?php
require_once __DIR__ . '/header.php';

// If already logged in, redirect to homepage/dashboard
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin/index.php");
    exit;
}
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$login_error = "";
$register_error = "";
$register_success = "";

$redirect = $_GET['redirect'] ?? 'index.php';

// Process Sign In (Consolidated for Guest & Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $login_error = "Please fill in all fields.";
    } else {
        try {
            // First check if matching admin credential
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['name'];
                
                header("Location: admin/index.php");
                exit;
            }
            
            // Second check if matching user credential
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_phone'] = $user['phone'];
                
                header("Location: " . $redirect);
                exit;
            } else {
                $login_error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $login_error = "Database Error: " . $e->getMessage();
        }
    }
}

// Process User Registration (Sign Up)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $register_error = "Please fill in all registration fields.";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $register_error = "This email is already registered.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password, name, phone) VALUES (?, ?, ?, ?)");
                $stmt->execute([$email, $hash, $name, $phone]);
                
                $register_success = "Registration successful! You can now log in below.";
            }
        } catch (PDOException $e) {
            $register_error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<section class="section-padding">
    <div class="container" style="max-width: 900px;">
        <div class="section-title-wrapper">
            <span class="section-subtitle"><?php echo __t('login_club_title'); ?></span>
            <h2 class="section-title"><?php echo __t('login_access_account'); ?></h2>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            
            <!-- Sign In Panel -->
            <div class="booking-mgmt-card" style="padding: 30px;">
                <h3 style="font-family: var(--font-serif); color: var(--primary-gold); font-size: 1.3rem; margin-bottom: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 10px;"><?php echo __t('login_signin_title'); ?></h3>
                
                <?php if ($login_error): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); padding: 10px; color: var(--danger); font-size: 0.85rem; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($login_error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($register_success): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); padding: 10px; color: var(--success); font-size: 0.85rem; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($register_success); ?>
                    </div>
                <?php endif; ?>

                <!-- Unified Login Form -->
                <form id="form-login" action="" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                    <input type="hidden" name="action" value="login">
                    
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold);"><?php echo __t('detail_email'); ?></label>
                        <input type="email" name="email" required placeholder="email@example.com" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 12px; outline: none;">
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold);"><?php echo __t('login_password'); ?></label>
                        <input type="password" name="password" required placeholder="••••••••" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 12px; outline: none;">
                    </div>
                    
                    <button type="submit" class="btn-search" style="height: auto; padding: 15px; margin-top: 10px;"><?php echo __t('login_signin_title'); ?></button>
                </form>
            </div>

            <!-- Sign Up Panel -->
            <div class="booking-mgmt-card" style="padding: 30px;">
                <h3 style="font-family: var(--font-serif); color: var(--primary-gold); font-size: 1.3rem; margin-bottom: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 10px;"><?php echo __t('login_signup_title'); ?></h3>
                
                <?php if ($register_error): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); padding: 10px; color: var(--danger); font-size: 0.85rem; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($register_error); ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                    <input type="hidden" name="action" value="register">
                    
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold);"><?php echo __t('detail_full_name'); ?></label>
                        <input type="text" name="name" required placeholder="John Doe" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 10px; outline: none;">
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold);"><?php echo __t('detail_email'); ?></label>
                        <input type="email" name="email" required placeholder="john@example.com" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 10px; outline: none;">
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold);"><?php echo __t('detail_phone'); ?></label>
                        <input type="tel" name="phone" required placeholder="+66 81 234 5678" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 10px; outline: none;">
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold);"><?php echo __t('login_password'); ?></label>
                        <input type="password" name="password" required placeholder="Minimum 6 characters" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 10px; outline: none;">
                    </div>
                    
                    <button type="submit" class="btn-secondary" style="padding: 12px; margin-top: 10px; font-size: 0.8rem;"><?php echo __t('login_signup_title'); ?></button>
                </form>
            </div>
            
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
