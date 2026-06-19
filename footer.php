<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col footer-about">
                <h4><?php echo __t('logo_main') . ' ' . __t('logo_sub'); ?></h4>
                <p><?php echo __t('footer_desc'); ?></p>
                <div style="display: flex; gap: 15px; font-size: 1.2rem; color: var(--primary-gold);">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="rooms.php">Rooms & Suites</a></li>
                    <li><a href="my-bookings.php">Manage Bookings</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h4>Accommodations</h4>
                <ul class="footer-links">
                    <li><a href="rooms.php?type=Deluxe">Deluxe Rooms</a></li>
                    <li><a href="rooms.php?type=Suite">Executive Suites</a></li>
                    <li><a href="rooms.php?type=Penthouse">Royal Penthouses</a></li>
                </ul>
            </div>
            
            <div class="footer-col footer-contact">
                <h4>Contact Us</h4>
                <p><i class="fas fa-map-marker-alt"></i> 88 Luxury Beach Road, Phuket 83150, Thailand</p>
                <p><i class="fas fa-phone-alt"></i> +66 (0) 76 123 456</p>
                <p><i class="fas fa-envelope"></i> reservations@nomtuayresort.com</p>
                
                <h4 style="margin-top: 25px; margin-bottom: 10px; font-size: 0.9rem;">Newsletter</h4>
                <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Thank you for subscribing to our newsletter.');">
                    <input type="email" placeholder="Your Email Address" required>
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p><?php echo __t('footer_all_rights', ['year' => date('Y')]); ?></p>
            <p>Designed for Ultimate Luxury Experiences</p>
        </div>
    </div>
</footer>

<!-- Main JS -->
<script src="assets/js/main.js"></script>
</body>
</html>
