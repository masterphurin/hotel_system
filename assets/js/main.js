document.addEventListener('DOMContentLoaded', function() {
    // 1. Header Scroll Effect
    const header = document.getElementById('main-header');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // 2. Mobile Menu Toggle
    const menuToggle = document.getElementById('menu-toggle');
    const navLinks = document.getElementById('nav-links');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            // Toggle hamburger icon animation
            const spans = menuToggle.querySelectorAll('span');
            spans[0].style.transform = navLinks.classList.contains('active') ? 'rotate(45deg) translate(5px, 5px)' : 'none';
            spans[1].style.opacity = navLinks.classList.contains('active') ? '0' : '1';
            spans[2].style.transform = navLinks.classList.contains('active') ? 'rotate(-45deg) translate(5px, -5px)' : 'none';
        });
    }

    // Theme Toggle Handler
    const themeToggleBtn = document.getElementById('theme-toggle-btn');
    const themeIcon = document.getElementById('theme-icon');
    
    if (themeToggleBtn && themeIcon) {
        // Set initial icon based on applied theme class
        if (document.documentElement.classList.contains('light-theme')) {
            themeIcon.className = 'fas fa-moon';
        } else {
            themeIcon.className = 'fas fa-sun';
        }

        themeToggleBtn.addEventListener('click', function() {
            document.documentElement.classList.toggle('light-theme');
            const isLight = document.documentElement.classList.contains('light-theme');
            
            // Save preference
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
            
            // Toggle icon classes
            themeIcon.className = isLight ? 'fas fa-moon' : 'fas fa-sun';
        });
    }

    // 3. Room Detail Image Gallery Switcher
    const mainImg = document.querySelector('.gallery-main img');
    const thumbs = document.querySelectorAll('.gallery-thumb');
    if (mainImg && thumbs.length > 0) {
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', function() {
                thumbs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const newSrc = this.querySelector('img').src;
                mainImg.style.opacity = '0';
                setTimeout(() => {
                    mainImg.src = newSrc;
                    mainImg.style.opacity = '1';
                }, 200);
            });
        });
        // Add smooth transition to main image
        mainImg.style.transition = 'opacity 0.2s ease-in-out';
    }

    // 4. Price range slider value update
    const priceSlider = document.getElementById('price-range');
    const priceValLabel = document.getElementById('price-range-val');
    if (priceSlider && priceValLabel) {
        priceSlider.addEventListener('input', function() {
            priceValLabel.textContent = parseInt(this.value).toLocaleString();
            // Optional: trigger search dynamic filtering
            filterRoomsDynamic();
        });
    }

    // 5. Dynamic Booking Price Calculator
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const totalNightsSpan = document.getElementById('total-nights');
    const totalPriceSpan = document.getElementById('calc-total-price');
    const priceCalculationBlock = document.getElementById('price-calculation-block');
    
    if (checkInInput && checkOutInput) {
        const roomPrice = parseFloat(document.getElementById('room-price-raw')?.value || 0);

        function calculatePrice() {
            const checkInDate = new Date(checkInInput.value);
            const checkOutDate = new Date(checkOutInput.value);

            if (checkInInput.value && checkOutInput.value && checkOutDate > checkInDate) {
                const timeDiff = checkOutDate.getTime() - checkInDate.getTime();
                const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                if (nights > 0) {
                    const total = nights * roomPrice;
                    if (totalNightsSpan) totalNightsSpan.textContent = nights;
                    if (totalPriceSpan) totalPriceSpan.textContent = total.toLocaleString() + ' THB';
                    if (priceCalculationBlock) priceCalculationBlock.style.display = 'block';
                }
            } else {
                if (priceCalculationBlock) priceCalculationBlock.style.display = 'none';
            }
        }

        checkInInput.addEventListener('change', calculatePrice);
        checkOutInput.addEventListener('change', calculatePrice);
        
        // Run once in case dates are pre-filled
        calculatePrice();
    }

    // 6. Map pin interaction
    const mapPins = document.querySelectorAll('.map-landmark-pin');
    mapPins.forEach(pin => {
        pin.addEventListener('click', function() {
            const label = this.getAttribute('data-label');
            const distance = this.getAttribute('data-distance');
            const isThai = document.documentElement.lang === 'th';
            const hotelLabelElement = document.querySelector('.map-hotel-label');
            const hotelName = hotelLabelElement ? hotelLabelElement.textContent.trim() : (isThai ? 'บ้านหนมถ้วยรีสอร์ท' : 'Nom Tuay Resort');
            const msg = isThai ? `${label}\nระยะห่างจาก ${hotelName}: ${distance}` : `${label}\nDistance from ${hotelName}: ${distance}`;
            alert(msg);
        });
    });

    // 7. Dynamic Filter function for Rooms page (runs on input changes)
    const filters = document.querySelectorAll('.sidebar-filter-input');
    filters.forEach(filter => {
        filter.addEventListener('change', filterRoomsDynamic);
    });

    function filterRoomsDynamic() {
        const roomCards = document.querySelectorAll('.room-card-filterable');
        if (roomCards.length === 0) return;

        // Get filter values
        const selectedTypes = Array.from(document.querySelectorAll('input[name="type[]"]:checked')).map(el => el.value);
        const maxPrice = parseFloat(document.getElementById('price-range')?.value || 99999);
        const guestCount = parseInt(document.getElementById('guests-count-filter')?.value || 0);
        const selectedAmenities = Array.from(document.querySelectorAll('input[name="amenities[]"]:checked')).map(el => el.value);
        const maxDistance = parseFloat(document.getElementById('distance-filter')?.value || 99);

        roomCards.forEach(card => {
            const type = card.getAttribute('data-type');
            const price = parseFloat(card.getAttribute('data-price'));
            const capacity = parseInt(card.getAttribute('data-capacity'));
            const distance = parseFloat(card.getAttribute('data-distance'));
            const amenities = JSON.parse(card.getAttribute('data-amenities') || '[]');

            let matchesType = selectedTypes.length === 0 || selectedTypes.includes(type);
            let matchesPrice = price <= maxPrice;
            let matchesGuests = capacity >= guestCount;
            let matchesDistance = distance <= maxDistance;
            
            let matchesAmenities = true;
            for (let amt of selectedAmenities) {
                if (!amenities.includes(amt)) {
                    matchesAmenities = false;
                    break;
                }
            }

            if (matchesType && matchesPrice && matchesGuests && matchesDistance && matchesAmenities) {
                card.style.display = 'flex';
                // Trigger animation
                card.style.opacity = '1';
                card.style.transform = 'scale(1)';
            } else {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    if (card.style.opacity === '0') card.style.display = 'none';
                }, 300);
            }
        });
    }

    // Scroll Reveal Observer
    const reveals = document.querySelectorAll('.reveal');
    if (reveals.length > 0) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -40px 0px'
        });

        reveals.forEach(el => revealObserver.observe(el));
    }
});
