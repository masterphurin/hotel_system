<?php
require_once __DIR__ . '/header.php';

$ref = $_GET['ref'] ?? '';
if (empty($ref)) {
    header("Location: rooms.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT b.*, r.name as room_name, r.type as room_type 
                           FROM bookings b 
                           JOIN rooms r ON b.room_id = r.id 
                           WHERE b.booking_reference = ? AND b.status = 'confirmed'");
    $stmt->execute([$ref]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        die("<div class='container' style='padding:50px; text-align:center;'><h3>Invalid Booking Reference</h3><a href='index.php'>Return Home</a></div>");
    }
    
    // If already paid, redirect straight to confirmation
    if ($booking['payment_status'] === 'paid') {
        header("Location: booking-confirmation.php?ref=" . $ref);
        exit;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$payment_error = "";

// Process payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
    $method = $_POST['payment_method'] ?? 'credit_card';
    $transaction_id = 'TXN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
    
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'paid', payment_method = ?, payment_transaction_id = ?, payment_date = NOW() WHERE booking_reference = ?");
        $stmt->execute([$method, $transaction_id, $ref]);
        
        // Redirect to confirmation screen
        header("Location: booking-confirmation.php?ref=" . $ref);
        exit;
    } catch (PDOException $e) {
        $payment_error = "Payment failed: " . $e->getMessage();
    }
}
?>

<section class="section-padding">
    <div class="container" style="max-width: 900px;">
        <div class="section-title-wrapper">
            <span class="section-subtitle"><?php echo __t('pay_secure_gate'); ?></span>
            <h2 class="section-title"><?php echo __t('pay_portal_title'); ?></h2>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px;">
            
            <!-- Left Column: Booking Summary -->
            <div>
                <div class="booking-widget-card" style="position: static; padding: 25px;">
                    <h3 style="font-family: var(--font-serif); color: var(--primary-gold); font-size: 1.2rem; margin-bottom: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 10px;"><?php echo __t('pay_summary'); ?></h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px; font-size: 0.9rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);"><?php echo __t('confirm_booking_ref'); ?>:</span>
                            <span style="font-weight: 600; color: var(--primary-gold);"><?php echo htmlspecialchars($booking['booking_reference']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);"><?php echo __t('my_bookings_room'); ?>:</span>
                            <span style="font-weight: 600;"><?php echo htmlspecialchars($booking['room_name']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);"><?php echo __t('search_check_in'); ?>:</span>
                            <span><?php echo date('d M Y', strtotime($booking['check_in'])); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);"><?php echo __t('search_check_out'); ?>:</span>
                            <span><?php echo date('d M Y', strtotime($booking['check_out'])); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);"><?php echo __t('search_guests'); ?>:</span>
                            <span><?php echo __t('search_guest_count', ['count' => $booking['guests_count']]); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-light); padding-top: 15px; font-size: 1.2rem; font-weight: 700; color: var(--primary-gold);">
                            <span><?php echo __t('my_bookings_price'); ?>:</span>
                            <span>฿<?php echo number_format($booking['total_price']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Payment Tabs & Form -->
            <div>
                <div class="booking-mgmt-card" style="padding: 30px;">
                    
                    <!-- Tabs Headers -->
                    <div style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid var(--border-light); padding-bottom: 15px;">
                        <button type="button" class="tab-btn active" onclick="switchPaymentTab('card')" style="flex: 1; padding: 10px; background: transparent; border: 1px solid var(--border-light); color: var(--text-light); font-size: 0.75rem; text-transform: uppercase; cursor: pointer; transition: var(--transition-smooth);">
                            <i class="fas fa-credit-card"></i> <?php echo __t('pay_card'); ?>
                        </button>
                        <button type="button" class="tab-btn" onclick="switchPaymentTab('promptpay')" style="flex: 1; padding: 10px; background: transparent; border: 1px solid var(--border-light); color: var(--text-light); font-size: 0.75rem; text-transform: uppercase; cursor: pointer; transition: var(--transition-smooth);">
                            <i class="fas fa-qrcode"></i> <?php echo __t('pay_promptpay'); ?>
                        </button>
                        <button type="button" class="tab-btn" onclick="switchPaymentTab('bank')" style="flex: 1; padding: 10px; background: transparent; border: 1px solid var(--border-light); color: var(--text-light); font-size: 0.75rem; text-transform: uppercase; cursor: pointer; transition: var(--transition-smooth);">
                            <i class="fas fa-university"></i> <?php echo __t('pay_transfer'); ?>
                        </button>
                    </div>

                    <?php if ($payment_error): ?>
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); padding: 10px; color: var(--danger); font-size: 0.85rem; margin-bottom: 15px; text-align: center;">
                            <?php echo htmlspecialchars($payment_error); ?>
                        </div>
                    <?php endif; ?>

                    <form id="payment-gateway-form" action="" method="POST" onsubmit="return handleMockPayment(event);">
                        <input type="hidden" name="pay_now" value="1">
                        <input type="hidden" id="payment_method_input" name="payment_method" value="credit_card">

                        <!-- Tab 1: Credit Card -->
                        <div id="tab-card" class="payment-tab-content">
                            <!-- Visual Credit Card Mockup -->
                            <div style="background: linear-gradient(135deg, #1e293b, #0f172a); border: 1px solid var(--primary-gold); border-radius: 8px; padding: 20px; margin-bottom: 25px; box-shadow: var(--shadow-premium); display: flex; flex-direction: column; justify-content: space-between; height: 160px; font-family: monospace; letter-spacing: 2px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <span style="font-size: 0.8rem; color: var(--primary-gold); font-weight: bold;"><?php echo __t('payment_club_title'); ?></span>
                                    <span style="font-size: 1.2rem; color: #fff;"><i class="fab fa-cc-visa"></i></span>
                                </div>
                                <div id="card-display-number" style="font-size: 1.15rem; color: #fff; margin: 15px 0;">•••• •••• •••• ••••</div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted);">
                                    <div>
                                        <div>CARDHOLDER</div>
                                        <div id="card-display-name" style="color: #fff; text-transform: uppercase;">YOUR NAME</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div>EXPIRES</div>
                                        <div id="card-display-expiry" style="color: #fff;">MM/YY</div>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 15px;">
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold);"><?php echo __t('pay_card_num'); ?></label>
                                    <input type="text" id="card_number" required placeholder="4000 1234 5678 9010" maxlength="19" oninput="updateCardNumber(this)" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 12px; outline: none; font-family: monospace;">
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold);"><?php echo __t('pay_card_holder'); ?></label>
                                    <input type="text" id="card_name" required placeholder="JOHN DOE" oninput="updateCardName(this)" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 12px; outline: none;">
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold);"><?php echo __t('pay_expiry'); ?></label>
                                        <input type="text" id="card_expiry" required placeholder="MM/YY" maxlength="5" oninput="updateCardExpiry(this)" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 12px; outline: none; font-family: monospace; text-align: center;">
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold);"><?php echo __t('pay_cvv'); ?></label>
                                        <input type="password" id="card_cvv" required placeholder="•••" maxlength="3" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-light); padding: 12px; outline: none; font-family: monospace; text-align: center;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: PromptPay -->
                        <div id="tab-promptpay" class="payment-tab-content" style="display: none; text-align: center;">
                            <div style="background: #fff; padding: 20px; border-radius: 8px; width: 220px; margin: 0 auto 20px; box-shadow: var(--shadow-premium); border: 2px solid #002e5f;">
                                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #002e5f; padding-bottom: 8px; margin-bottom: 12px;">
                                    <span style="color: #002e5f; font-weight: bold; font-size: 0.85rem;">PromptPay</span>
                                    <span style="background: #e28d00; color: #fff; font-size: 0.55rem; padding: 2px 5px; font-weight: bold; border-radius: 2px;">THAI QR PAYMENT</span>
                                </div>
                                <!-- CSS-based QR code representation -->
                                <div style="width: 150px; height: 150px; margin: 0 auto; background: repeating-conic-gradient(from 45deg, #000 0% 25%, #fff 0% 50%) 50%/20px 20px; border: 4px solid #000; position: relative;">
                                    <div style="position: absolute; top: 35%; left: 35%; width: 30%; height: 30%; background: #002e5f; display: flex; justify-content: center; align-items: center; border: 2px solid #fff;">
                                        <i class="fas fa-qrcode" style="color:#fff; font-size: 1rem;"></i>
                                    </div>
                                </div>
                                <div style="color: #000; font-size: 0.9rem; font-weight: 700; margin-top: 15px;">฿<?php echo number_format($booking['total_price']); ?></div>
                                <div style="color: #64748b; font-size: 0.65rem; margin-top: 5px;"><?php echo __t('pay_scan_qr'); ?></div>
                            </div>
                        </div>

                        <!-- Tab 3: Bank Transfer -->
                        <div id="tab-bank" class="payment-tab-content" style="display: none;">
                            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem;">
                                <div style="margin-bottom: 10px;"><strong><?php echo __t('pay_bank_name_label'); ?>:</strong> Kasikorn Bank (KBank)</div>
                                <div style="margin-bottom: 10px;"><strong><?php echo __t('pay_acc_name_label'); ?>:</strong> <?php echo __t('pay_account_name'); ?></div>
                                <div style="margin-bottom: 10px;"><strong><?php echo __t('pay_acc_num_label'); ?>:</strong> 012-3-45678-9</div>
                                <div><strong><?php echo __t('pay_branch_label'); ?>:</strong> <?php echo __t('pay_bank_branch'); ?></div>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label style="font-size: 0.75rem; text-transform: uppercase; color: var(--primary-gold);"><?php echo __t('pay_upload_slip'); ?></label>
                                <input type="file" id="bank_slip" style="background: var(--bg-dark); border: 1px solid var(--border-light); color: var(--text-muted); padding: 10px; outline: none; width: 100%;">
                            </div>
                        </div>

                        <!-- Processing overlay simulation -->
                        <div id="payment-spinner-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(13, 15, 18, 0.95); z-index: 9999; justify-content: center; align-items: center; flex-direction: column; gap: 20px;">
                            <div style="width: 50px; height: 50px; border: 3px solid rgba(197, 168, 128, 0.2); border-top-color: var(--primary-gold); border-radius: 50%; animation: spin 1s infinite linear;"></div>
                            <span style="font-family: var(--font-serif); color: var(--primary-gold); font-size: 1.2rem; letter-spacing: 1px;"><?php echo __t('pay_processing'); ?></span>
                        </div>

                        <button type="submit" class="btn-book-submit" style="margin-top: 30px;">
                            <?php echo __t('pay_btn', ['price' => number_format($booking['total_price'])]); ?>
                        </button>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</section>

<style>
.tab-btn.active {
    border-color: var(--primary-gold) !important;
    color: var(--primary-gold) !important;
    background: rgba(197, 168, 128, 0.05) !important;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
function switchPaymentTab(tabName) {
    // Hide all contents
    document.querySelectorAll('.payment-tab-content').forEach(el => el.style.display = 'none');
    // Deactivate all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    // Show select tab content
    document.getElementById('tab-' + tabName).style.display = 'block';
    
    // Activate clicked button
    const activeBtn = event.currentTarget;
    activeBtn.classList.add('active');
    
    // Update input value
    document.getElementById('payment_method_input').value = tabName === 'card' ? 'credit_card' : (tabName === 'promptpay' ? 'promptpay' : 'bank_transfer');
    
    // Toggle input required fields
    const cardInputs = document.querySelectorAll('#tab-card input');
    const bankSlipInput = document.getElementById('bank_slip');
    
    if (tabName === 'card') {
        cardInputs.forEach(i => i.setAttribute('required', 'required'));
        bankSlipInput.removeAttribute('required');
    } else if (tabName === 'bank') {
        cardInputs.forEach(i => i.removeAttribute('required'));
        bankSlipInput.setAttribute('required', 'required');
    } else {
        cardInputs.forEach(i => i.removeAttribute('required'));
        bankSlipInput.removeAttribute('required');
    }
}

// Live card update listeners
function updateCardNumber(el) {
    let val = el.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    let matches = val.match(/\d{4,16}/g);
    let match = matches && matches[0] || '';
    let parts = [];

    for (let i=0, len=match.length; i<len; i+=4) {
        parts.push(match.substring(i, i+4));
    }

    if (parts.length > 0) {
        el.value = parts.join(' ');
    } else {
        el.value = val;
    }
    
    document.getElementById('card-display-number').textContent = el.value || '•••• •••• •••• ••••';
}

function updateCardName(el) {
    document.getElementById('card-display-name').textContent = el.value.toUpperCase() || 'YOUR NAME';
}

function updateCardExpiry(el) {
    let val = el.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    if (val.length >= 2) {
        el.value = val.substring(0, 2) + '/' + val.substring(2, 4);
    } else {
        el.value = val;
    }
    document.getElementById('card-display-expiry').textContent = el.value || 'MM/YY';
}

function handleMockPayment(e) {
    e.preventDefault();
    const spinner = document.getElementById('payment-spinner-overlay');
    spinner.style.display = 'flex';
    
    setTimeout(() => {
        document.getElementById('payment-gateway-form').submit();
    }, 2000);
    
    return false;
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
