<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Determine language selection
if (isset($_GET['lang'])) {
    $selected_lang = $_GET['lang'] === 'th' ? 'th' : 'en';
    $_SESSION['lang'] = $selected_lang;
    
    // Redirect to clean URL without the query parameter to prevent refresh loops
    $clean_url = strtok($_SERVER['REQUEST_URI'], '?');
    // Re-attach other GET parameters if any (except lang)
    $get_params = $_GET;
    unset($get_params['lang']);
    if (!empty($get_params)) {
        $clean_url .= '?' . http_build_query($get_params);
    }
    header("Location: " . $clean_url);
    exit;
}

// Default language is English
$current_lang = $_SESSION['lang'] ?? 'en';

// Dictionary of translations
$translations = [
    // Brand Logos & Meta
    'logo_main' => [
        'en' => 'NOM TUAY',
        'th' => 'บ้านหนมถ้วย'
    ],
    'logo_sub' => [
        'en' => 'Resort',
        'th' => 'รีสอร์ท'
    ],
    'meta_title' => [
        'en' => 'Nom Tuay Resort | Luxury Hotel & Spa',
        'th' => 'บ้านหนมถ้วยรีสอร์ท | โรงแรมหรูและสปา'
    ],
    'meta_description' => [
        'en' => 'Experience absolute luxury at Nom Tuay Resort. Book your high-end suites, deluxe ocean view rooms and penthouses with real-time availability.',
        'th' => 'สัมผัสความหรูหราที่แท้จริงที่บ้านหนมถ้วยรีสอร์ท จองห้องสูท ห้องดีลักซ์วิวทะเล และห้องเพนท์เฮ้าส์พร้อมข้อมูลว่างแบบเรียลไทม์'
    ],
    // Navigation
    'nav_home' => [
        'en' => 'Home',
        'th' => 'หน้าแรก'
    ],
    'nav_rooms' => [
        'en' => 'Rooms & Suites',
        'th' => 'ห้องพัก & สูท'
    ],
    'nav_my_bookings' => [
        'en' => 'My Bookings',
        'th' => 'การจองของฉัน'
    ],
    'nav_login' => [
        'en' => 'Sign In',
        'th' => 'เข้าสู่ระบบ'
    ],
    'nav_logout' => [
        'en' => 'Logout',
        'th' => 'ออกจากระบบ'
    ],
    'nav_book_now' => [
        'en' => 'Book Now',
        'th' => 'จองห้องพัก'
    ],
    
    // Hero Section
    'hero_subtitle' => [
        'en' => 'Welcome to Sanctuary of Luxury',
        'th' => 'ยินดีต้อนรับสู่ดินแดนแห่งความหรูหรา'
    ],
    'hero_title' => [
        'en' => 'Nom Tuay Resort',
        'th' => 'บ้านหนมถ้วยรีสอร์ท'
    ],
    'hero_desc' => [
        'en' => 'Indulge in an unparalleled retreat where modern luxury meets timeless elegance. Nestled on pristine shores, discover your home away from home.',
        'th' => 'ดื่มด่ำกับสถานที่พักผ่อนที่เหนือระดับ ที่ซึ่งความหรูหราทันสมัยผสมผสานกับความสง่างามเหนือกาลเวลา ค้นพบการพักผ่อนเสมือนบ้านของคุณบนชายฝั่งที่บริสุทธิ์'
    ],
    
    // Search Widget
    'search_check_in' => [
        'en' => 'Check-In',
        'th' => 'วันที่เช็คอิน'
    ],
    'search_check_out' => [
        'en' => 'Check-Out',
        'th' => 'วันที่เช็คเอาท์'
    ],
    'search_guests' => [
        'en' => 'Guests',
        'th' => 'จำนวนผู้เข้าพัก'
    ],
    'search_room_type' => [
        'en' => 'Room Type',
        'th' => 'ประเภทห้อง'
    ],
    'search_all_types' => [
        'en' => 'All Suites & Rooms',
        'th' => 'ห้องพักและสูททั้งหมด'
    ],
    'search_deluxe' => [
        'en' => 'Deluxe Room',
        'th' => 'ห้องดีลักซ์'
    ],
    'search_suite' => [
        'en' => 'Grand Executive Suite',
        'th' => 'แกรนด์ เอ็กเซกคิวทีฟ สูท'
    ],
    'search_penthouse' => [
        'en' => 'Royal Penthouse',
        'th' => 'รอยัล เพนท์เฮ้าส์'
    ],
    'search_check_btn' => [
        'en' => 'Check Availability',
        'th' => 'ตรวจสอบห้องว่าง'
    ],
    'search_guest_count' => [
        'en' => '{count} Guest(s)',
        'th' => 'ผู้เข้าพัก {count} ท่าน'
    ],
    
    // Index Content
    'index_phil_sub' => [
        'en' => 'Our Philosophy',
        'th' => 'ปรัชญาของเรา'
    ],
    'index_phil_title' => [
        'en' => 'A Symphony of Exclusivity & Comfort',
        'th' => 'ท่วงทำนองแห่งความพิเศษและความสะดวกสบาย'
    ],
    'index_phil_desc' => [
        'en' => 'At Nom Tuay Resort, we believe that true luxury is personal. Every detail, from the hand-woven Italian linens to the bespoke dining experiences curated by Michelin-starred chefs, is designed to elevate your senses. Experience world-class hospitality tailored to your highest expectations.',
        'th' => 'ที่ บ้านหนมถ้วยรีสอร์ท เราเชื่อว่าความหรูหราที่แท้จริงคือเรื่องส่วนบุคคล ทุกๆ รายละเอียด ตั้งแต่ผ้าปูที่นอนอิตาลีทอมือไปจนถึงประสบการณ์การรับประทานอาหารสุดพิเศษที่คัดสรรโดยเชฟระดับมิชลินสตาร์ ได้รับการออกแบบมาเพื่อยกระดับประสาทสัมผัสของคุณ สัมผัสประสบการณ์การต้อนรับระดับโลกที่ปรับให้ตอบสนองความคาดหวังสูงสุดของคุณ'
    ],
    'index_rooms_sub' => [
        'en' => 'Luxury Accommodations',
        'th' => 'ห้องพักสุดหรู'
    ],
    'index_rooms_title' => [
        'en' => 'Rooms & Suites',
        'th' => 'ห้องพัก & ห้องสูท'
    ],
    'index_from' => [
        'en' => 'From',
        'th' => 'เริ่มต้น'
    ],
    'index_night' => [
        'en' => 'night',
        'th' => 'คืน'
    ],
    'index_view_details' => [
        'en' => 'View Details',
        'th' => 'ดูรายละเอียด'
    ],
    'index_services_sub' => [
        'en' => 'Elevated Indulgence',
        'th' => 'บริการระดับพรีเมียม'
    ],
    'index_services_title' => [
        'en' => 'Signature Services',
        'th' => 'บริการอันเป็นเอกลักษณ์'
    ],
    'index_service_spa' => [
        'en' => 'The Serenity Spa',
        'th' => 'เดอะ เซเรนิตี้ สปา'
    ],
    'index_service_spa_desc' => [
        'en' => 'Rejuvenate your body and soul with bespoke therapies, thermal suites, and healing mineral baths led by world-class specialists.',
        'th' => 'ฟื้นฟูร่างกายและจิตวิญญาณของคุณด้วยการบำบัดเฉพาะบุคคล ห้องอบไอน้ำความร้อน และการอาบน้ำแร่บำบัดโดยผู้เชี่ยวชาญระดับโลก'
    ],
    'index_service_dining' => [
        'en' => 'Michelin-Starred Dining',
        'th' => 'การรับประทานอาหารระดับมิชลินสตาร์'
    ],
    'index_service_dining_desc' => [
        'en' => 'Embark on a culinary journey at L\'Horizon, presenting modern French-Asian fusion gastronomy paired with rare vintage wines.',
        'th' => 'เริ่มต้นการเดินทางสู่อาหารรสเลิศที่ L\'Horizon นำเสนออาหารฟิวชั่นฝรั่งเศส-เอเชียสมัยใหม่ ควบคู่กับไวน์วินเทจหายาก'
    ],
    'index_service_butler' => [
        'en' => '24/7 Butler Service',
        'th' => 'บริการบัตเลอร์ส่วนตัวตลอด 24 ชั่วโมง'
    ],
    'index_service_butler_desc' => [
        'en' => 'Experience absolute convenience. Our dedicated butler service is at your command, anticipating your every need at any hour.',
        'th' => 'สัมผัสความสะดวกสบายอย่างสมบูรณ์แบบ บริการบัตเลอร์เฉพาะทุ่มเทของเราพร้อมดูแลตามที่คุณต้องการ คาดการณ์และจัดการทุกสิ่งให้คุณตลอด 24 ชั่วโมง'
    ],
    'index_testi_sub' => [
        'en' => 'Guest Testimonials',
        'th' => 'ความประทับใจจากผู้เข้าพัก'
    ],
    'index_testi_title' => [
        'en' => 'Whispers of Satisfaction',
        'th' => 'เสียงสะท้อนแห่งความพึงพอใจ'
    ],
    'index_testi_quote' => [
        'en' => 'Simply magnificent. The butler service was impeccable, and the private pool overlooking the entire city at night is unforgettable. The definition of 5-star luxury.',
        'th' => 'งดงามตระการตาอย่างแท้จริง บริการของบัตเลอร์ไร้ที่ติ และสระว่ายน้ำส่วนตัวที่มองเห็นวิวเมืองทั้งเมืองในตอนกลางคืนเป็นสิ่งที่ลืมไม่ลง คำจำกัดความของความหรูหราระดับ 5 ดาว'
    ],
    
    // Rooms Page
    'rooms_catalog_sub' => [
        'en' => 'Exquisite Living Spaces',
        'th' => 'พื้นที่การอยู่อาศัยที่สวยงาม'
    ],
    'rooms_catalog_title' => [
        'en' => 'Rooms & Suites Catalog',
        'th' => 'รายการห้องพัก & ห้องสูท'
    ],
    'rooms_showing_available' => [
        'en' => 'Showing available accommodations from <strong>{in}</strong> to <strong>{out}</strong> for <strong>{guests}</strong> guest(s).',
        'th' => 'แสดงห้องพักว่างตั้งแต่วันที่ <strong>{in}</strong> ถึง <strong>{out}</strong> สำหรับผู้เข้าพัก <strong>{guests}</strong> ท่าน'
    ],
    'rooms_left' => [
        'en' => '{count} Room(s) Left',
        'th' => 'เหลือห้องว่าง {count} ห้อง'
    ],
    'rooms_fully_booked' => [
        'en' => 'Fully Booked',
        'th' => 'ห้องพักเต็ม'
    ],
    'rooms_book_btn' => [
        'en' => 'Book Room',
        'th' => 'จองห้องนี้'
    ],
    'rooms_filter_title' => [
        'en' => 'Filters',
        'th' => 'ตัวกรองห้องพัก'
    ],
    'rooms_filter_max_price' => [
        'en' => 'Max Price (per night)',
        'th' => 'ราคาสูงสุดต่อคืน'
    ],
    'rooms_filter_capacity' => [
        'en' => 'Guests Capacity',
        'th' => 'ความจุผู้เข้าพัก'
    ],
    'rooms_filter_distance' => [
        'en' => 'Distance to Landmark',
        'th' => 'ระยะทางจากสถานที่สำคัญ'
    ],
    'rooms_any_distance' => [
        'en' => 'Any Distance',
        'th' => 'ทุกระยะทาง'
    ],
    'rooms_within_dist' => [
        'en' => 'Within {dist} km',
        'th' => 'ภายใน {dist} กม.'
    ],
    'rooms_filter_amenities' => [
        'en' => 'Amenities',
        'th' => 'สิ่งอำนวยความสะดวก'
    ],
    'rooms_no_match' => [
        'en' => 'No suites or rooms match your criteria. Please refine your search dates or parameters.',
        'th' => 'ไม่พบห้องพักที่ตรงกับเงื่อนไขของคุณ โปรดลองปรับปรุงวันที่หรือเงื่อนไขการค้นหาใหม่อีกครั้ง'
    ],
    'rooms_sqm' => [
        'en' => 'sqm',
        'th' => 'ตร.ม.'
    ],
    'rooms_max_guests' => [
        'en' => 'Up to {count} Guests',
        'th' => 'ผู้เข้าพักสูงสุด {count} ท่าน'
    ],
    'rooms_km_from' => [
        'en' => 'km from {landmark}',
        'th' => 'กม. จาก {landmark}'
    ],
    
    // Room Detail Page
    'detail_desc' => [
        'en' => 'Description',
        'th' => 'รายละเอียดห้องพัก'
    ],
    'detail_amenities' => [
        'en' => 'Amenities',
        'th' => 'สิ่งอำนวยความสะดวกในห้อง'
    ],
    'detail_policy' => [
        'en' => 'Stay Policies',
        'th' => 'นโยบายการเข้าพัก'
    ],
    'detail_location' => [
        'en' => 'Location & Nearby Attractions',
        'th' => 'ที่ตั้ง & สถานที่ท่องเที่ยวใกล้เคียง'
    ],
    'detail_reviews' => [
        'en' => 'Guest Reviews',
        'th' => 'คะแนนและรีวิวจากผู้เข้าพัก'
    ],
    'detail_cleanliness' => [
        'en' => 'Cleanliness',
        'th' => 'ความสะอาด'
    ],
    'detail_service_rating' => [
        'en' => 'Service Quality',
        'th' => 'การบริการ'
    ],
    'detail_value' => [
        'en' => 'Value for Money',
        'th' => 'ความคุ้มค่า'
    ],
    'detail_share_exp' => [
        'en' => 'Share Your Luxury Experience',
        'th' => 'แบ่งปันประสบการณ์การเข้าพักของคุณ'
    ],
    'detail_submit_review' => [
        'en' => 'Submit Review',
        'th' => 'ส่งความคิดเห็น'
    ],
    'detail_proceed_book' => [
        'en' => 'Proceed to Book',
        'th' => 'ดำเนินการจองห้องพัก'
    ],
    'detail_lock_msg' => [
        'en' => 'Reservations are exclusive to club members. Sign in to secure this room.',
        'th' => 'การจองห้องพักสงวนไว้สำหรับสมาชิกคลับเท่านั้น โปรดเข้าสู่ระบบเพื่อดำเนินการจองห้องนี้'
    ],
    'detail_room_avail' => [
        'en' => '{count} Room(s) Available',
        'th' => 'เหลือห้องว่าง {count} ห้อง'
    ],
    'detail_check_in_date' => [
        'en' => 'Check-In Date',
        'th' => 'วันที่เช็คอิน'
    ],
    'detail_check_out_date' => [
        'en' => 'Check-Out Date',
        'th' => 'วันที่เช็คเอาท์'
    ],
    'detail_num_guests' => [
        'en' => 'Number of Guests',
        'th' => 'จำนวนผู้เข้าพัก'
    ],
    'detail_full_name' => [
        'en' => 'Full Name',
        'th' => 'ชื่อ-นามสกุล'
    ],
    'detail_email' => [
        'en' => 'Email Address',
        'th' => 'ที่อยู่อีเมล'
    ],
    'detail_phone' => [
        'en' => 'Phone Number',
        'th' => 'เบอร์โทรศัพท์'
    ],
    'detail_nights' => [
        'en' => 'Nights:',
        'th' => 'จำนวนคืน:'
    ],
    'detail_total_price' => [
        'en' => 'Total Price:',
        'th' => 'ราคารวมทั้งหมด:'
    ],
    'detail_upload_photo' => [
        'en' => 'Upload Room Photo (Optional)',
        'th' => 'อัปโหลดรูปภาพห้องพัก (ถ้ามี)'
    ],
    'detail_your_comments' => [
        'en' => 'Comments',
        'th' => 'ความคิดเห็นของคุณ'
    ],
    'detail_your_name' => [
        'en' => 'Your Name',
        'th' => 'ชื่อของคุณ'
    ],
    'detail_review_success' => [
        'en' => 'Thank you! Your review has been submitted successfully.',
        'th' => 'ขอบคุณ! ความคิดเห็นของคุณได้รับการส่งเรียบร้อยแล้ว'
    ],
    'detail_review_no_reviews' => [
        'en' => 'No reviews yet. Be the first to share your experience!',
        'th' => 'ยังไม่มีรีวิวสำหรับห้องนี้ ร่วมแบ่งปันประสบการณ์เป็นคนแรกได้เลย!'
    ],
    'detail_location_desc' => [
        'en' => 'Nom Tuay Resort is situated in a premium luxury coastal enclave. View our interactive locator map and distance to nearby landmarks below:',
        'th' => 'บ้านหนมถ้วยรีสอร์ท ตั้งอยู่ในย่านชายฝั่งทะเลสุดหรูระดับพรีเมียม ท่านสามารถดูแผนที่ระบุพิกัดและระยะทางไปยังสถานที่ท่องเที่ยวสำคัญใกล้เคียงได้ด้านล่าง:'
    ],
    
    // Policies details
    'policy_checkin_title' => [ 'en' => 'Check-In:', 'th' => 'เช็คอิน:' ],
    'policy_checkin_desc' => [ 'en' => 'From 2:00 PM (Early check-in subject to availability)', 'th' => 'ตั้งแต่เวลา 14:00 น. เป็นต้นไป (การเช็คอินก่อนเวลาขึ้นอยู่กับห้องว่างขณะนั้น)' ],
    'policy_checkout_title' => [ 'en' => 'Check-Out:', 'th' => 'เช็คเอาท์:' ],
    'policy_checkout_desc' => [ 'en' => 'Until 12:00 PM (Late check-out may incur surcharges)', 'th' => 'ก่อนเวลา 12:00 น. (การเช็คเอาท์ล่าช้าอาจมีค่าบริการเพิ่มเติม)' ],
    'policy_cancel_title' => [ 'en' => 'Cancellation:', 'th' => 'การยกเลิก:' ],
    'policy_cancel_desc' => [ 'en' => 'Free cancellation up to 48 hours before check-in. Non-refundable after that.', 'th' => 'ยกเลิกฟรีไม่เสียค่าธรรมเนียมได้สูงสุด 48 ชั่วโมงก่อนเช็คอิน หลังจากนั้นจะไม่สามารถคืนเงินได้' ],
    'policy_children_title' => [ 'en' => 'Children & Extra Beds:', 'th' => 'เด็ก & เตียงเสริม:' ],
    'policy_children_desc' => [ 'en' => 'Children under 12 stay free using existing beds. Extra beds are available at 1,500 THB/night.', 'th' => 'เด็กอายุต่ำกว่า 12 ปีเข้าพักฟรีเมื่อใช้เตียงที่มีอยู่ บริการเตียงเสริมราคา 1,500 บาท/คืน' ],

    // My Bookings Page
    'my_bookings_sub' => [
        'en' => 'Manage Your Stay',
        'th' => 'จัดการการเข้าพักของคุณ'
    ],
    'my_bookings_title' => [
        'en' => 'Reservation Control Panel',
        'th' => 'แผงควบคุมการจองพัก'
    ],
    'my_bookings_my_res' => [
        'en' => 'My Reservations',
        'th' => 'ประวัติการจองของฉัน'
    ],
    'my_bookings_ref' => [
        'en' => 'Reference',
        'th' => 'รหัสอ้างอิง'
    ],
    'my_bookings_room' => [
        'en' => 'Room',
        'th' => 'ห้องพัก'
    ],
    'my_bookings_price' => [
        'en' => 'Total Price',
        'th' => 'ราคารวม'
    ],
    'my_bookings_status' => [
        'en' => 'Status',
        'th' => 'สถานะ'
    ],
    'my_bookings_action' => [
        'en' => 'Action',
        'th' => 'การดำเนินการ'
    ],
    'my_bookings_manage' => [
        'en' => 'Manage',
        'th' => 'จัดการการจอง'
    ],
    'my_bookings_modify_dates' => [
        'en' => 'Modify Booking Dates',
        'th' => 'แก้ไขวันเข้าพัก'
    ],
    'my_bookings_update_stay' => [
        'en' => 'Update Stay Dates',
        'th' => 'อัปเดตวันเดินทาง'
    ],
    'my_bookings_cancel_stay' => [
        'en' => 'Cancel Stay',
        'th' => 'ยกเลิกการเข้าพัก'
    ],
    'my_bookings_cancel_msg' => [
        'en' => 'Cancel reservation. Re-allocation checks are applied.',
        'th' => 'ยกเลิกการจองห้องพักห้องนี้ และคืนห้องกลับเข้าสู่ระบบทันที'
    ],
    'my_bookings_no_res' => [
        'en' => 'You do not have any active or past bookings. {link}',
        'th' => 'คุณยังไม่มีประวัติการจองห้องพักในระบบ {link}'
    ],
    'my_bookings_browse' => [
        'en' => 'Browse Rooms',
        'th' => 'เลือกดูห้องพักว่าง'
    ],
    'my_bookings_cancel_confirm' => [
        'en' => 'Are you absolutely sure you want to cancel your luxury stay at Nom Tuay Resort?',
        'th' => 'คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการเข้าพักที่บ้านหนมถ้วยรีสอร์ท?'
    ],
    'my_bookings_unpaid' => [
        'en' => 'This reservation is currently unpaid.',
        'th' => 'การจองนี้ยังไม่ได้รับการชำระเงิน'
    ],
    'my_bookings_complete_pay' => [
        'en' => 'Complete Payment',
        'th' => 'ชำระเงินให้เสร็จสิ้น'
    ],
    'my_bookings_guest_details' => [
        'en' => 'Guest Details',
        'th' => 'รายละเอียดแขกผู้เข้าพัก'
    ],
    'my_bookings_current_details' => [
        'en' => 'Current Reservation Details',
        'th' => 'รายละเอียดการจองปัจจุบัน'
    ],
    'my_bookings_pending_pay' => [
        'en' => 'Pending Payment',
        'th' => 'รอการชำระเงิน'
    ],
    'my_bookings_confirmed_paid' => [
        'en' => 'Confirmed (Paid)',
        'th' => 'ยืนยันการจองแล้ว (ชำระเงินแล้ว)'
    ],
    'my_bookings_back_list' => [
        'en' => 'Back to Reservations List',
        'th' => 'กลับไปยังรายการประวัติการจอง'
    ],
    'my_bookings_login_title' => [
        'en' => 'Membership Access Required',
        'th' => 'กรุณาเข้าสู่ระบบสมาชิก'
    ],
    'my_bookings_login_desc' => [
        'en' => 'Please sign in to your membership account to view, modify, or cancel your reservations.',
        'th' => 'โปรดลงชื่อเข้าสู่ระบบสมาชิกของคุณเพื่อตรวจสอบ แก้ไขรายละเอียด หรือยกเลิกการเข้าพัก'
    ],
    
    // Login / Sign Up Page
    'login_club_title' => [
        'en' => 'Nom Tuay Club Membership',
        'th' => 'คลับสมาชิก บ้านหนมถ้วยรีสอร์ท'
    ],
    'login_access_account' => [
        'en' => 'Access Your Account',
        'th' => 'เข้าสู่ระบบบัญชีผู้ใช้'
    ],
    'login_member_tab' => [
        'en' => 'Member Login',
        'th' => 'เข้าสู่ระบบสมาชิก'
    ],
    'login_staff_tab' => [
        'en' => 'Staff Login',
        'th' => 'เข้าสู่ระบบเจ้าหน้าที่'
    ],
    'login_signin_title' => [
        'en' => 'Sign In',
        'th' => 'ลงชื่อเข้าใช้'
    ],
    'login_signup_title' => [
        'en' => 'Create Account',
        'th' => 'สมัครสมาชิกใหม่'
    ],
    'login_password' => [
        'en' => 'Password',
        'th' => 'รหัสผ่าน'
    ],
    
    // Payment Portal
    'pay_secure_gate' => [
        'en' => 'Secure Gateway',
        'th' => 'ช่องทางการชำระเงินที่ปลอดภัย'
    ],
    'pay_portal_title' => [
        'en' => 'Luxury Payment Portal',
        'th' => 'หน้าชำระเงินอิเล็กทรอนิกส์'
    ],
    'pay_summary' => [
        'en' => 'Booking Summary',
        'th' => 'สรุปรายละเอียดการจอง'
    ],
    'pay_card' => [
        'en' => 'Credit Card',
        'th' => 'บัตรเครดิต/เดบิต'
    ],
    'pay_promptpay' => [
        'en' => 'PromptPay',
        'th' => 'พร้อมเพย์ QR'
    ],
    'pay_transfer' => [
        'en' => 'Bank Transfer',
        'th' => 'โอนผ่านบัญชีธนาคาร'
    ],
    'pay_btn' => [
        'en' => 'Pay ฿{price} Now',
        'th' => 'ชำระเงินจำนวน ฿{price} ทันที'
    ],
    'pay_card_num' => [
        'en' => 'Card Number',
        'th' => 'หมายเลขบัตรเครดิต'
    ],
    'pay_card_holder' => [
        'en' => 'Cardholder Name',
        'th' => 'ชื่อที่ปรากฏบนบัตร'
    ],
    'pay_expiry' => [
        'en' => 'Expiration Date',
        'th' => 'วันหมดอายุของบัตร (MM/YY)'
    ],
    'pay_cvv' => [
        'en' => 'CVV/CVC',
        'th' => 'รหัส CVV (หลังบัตร)'
    ],
    'pay_scan_qr' => [
        'en' => 'Scan QR with your mobile banking app',
        'th' => 'สแกนคิวอาร์โค้ดนี้ด้วยแอปธนาคารบนมือถือของคุณ'
    ],
    'pay_bank_info' => [
        'en' => 'Bank Account Details',
        'th' => 'ข้อมูลบัญชีธนาคารสำหรับโอนเงิน'
    ],
    'pay_upload_slip' => [
        'en' => 'Upload Transfer Slip',
        'th' => 'อัปโหลดรูปภาพหลักฐานการโอนเงิน (สลิป)'
    ],
    'pay_processing' => [
        'en' => 'Processing Secure Transaction...',
        'th' => 'กำลังดำเนินการทำธุรกรรมอย่างปลอดภัย โปรดรอสักครู่...'
    ],
    'payment_club_title' => [
        'en' => 'NOM TUAY CLUB',
        'th' => 'บ้านหนมถ้วย คลับ'
    ],
    'pay_account_name' => [
        'en' => 'Nom Tuay Resort Group',
        'th' => 'กลุ่มรีสอร์ทบ้านหนมถ้วย'
    ],
    'pay_bank_branch' => [
        'en' => 'Patong Coastal Branch',
        'th' => 'สาขาชายฝั่งป่าตอง'
    ],
    'pay_bank_name_label' => [
        'en' => 'Bank Name',
        'th' => 'ธนาคาร'
    ],
    'pay_acc_name_label' => [
        'en' => 'Account Name',
        'th' => 'ชื่อบัญชี'
    ],
    'pay_acc_num_label' => [
        'en' => 'Account Number',
        'th' => 'เลขที่บัญชี'
    ],
    'pay_branch_label' => [
        'en' => 'Branch',
        'th' => 'สาขา'
    ],
    
    // Booking Confirmation Page
    'confirm_success_title' => [
        'en' => 'Reservation Confirmed',
        'th' => 'ยืนยันการจองห้องพักเสร็จสิ้น'
    ],
    'confirm_secured' => [
        'en' => 'Your Booking is Secured',
        'th' => 'การจองห้องพักของท่านได้รับการคุ้มครองแล้ว'
    ],
    'confirm_email_sent' => [
        'en' => 'A confirmation email has been sent to <strong>{email}</strong>',
        'th' => 'ระบบส่งข้อมูลการจองหลักฐานใบเสร็จเข้าอีเมล <strong>{email}</strong> ของท่านแล้ว'
    ],
    'confirm_voucher_title' => [
        'en' => 'Luxury Hotel & Spa Voucher',
        'th' => 'ใบเสร็จรับเงิน & บัตรเข้าพักโรงแรมหรู'
    ],
    'confirm_booking_ref' => [
        'en' => 'Booking Reference',
        'th' => 'รหัสอ้างอิงการจอง'
    ],
    'confirm_guest_name' => [
        'en' => 'Guest Name',
        'th' => 'ชื่อผู้เข้าพัก'
    ],
    'confirm_phone' => [
        'en' => 'Contact Phone',
        'th' => 'เบอร์โทรศัพท์ติดต่อ'
    ],
    'confirm_in' => [
        'en' => 'Check-In Date',
        'th' => 'วันที่เข้าพัก'
    ],
    'confirm_out' => [
        'en' => 'Check-Out Date',
        'th' => 'วันที่เช็คเอาท์'
    ],
    'confirm_reserved' => [
        'en' => 'Room Reserved',
        'th' => 'ห้องพักที่ทำการจอง'
    ],
    'confirm_count_nights' => [
        'en' => 'Guests Count / Nights',
        'th' => 'จำนวนผู้เข้าพัก / จำนวนคืนที่นอน'
    ],
    'confirm_total_paid' => [
        'en' => 'Grand Total Paid',
        'th' => 'ยอดเงินรวมชำระจริงทั้งหมด'
    ],
    'confirm_print' => [
        'en' => 'Print Voucher',
        'th' => 'พิมพ์ใบรับเงิน'
    ],
    'confirm_back_home' => [
        'en' => 'Back to Homepage',
        'th' => 'กลับไปยังหน้าหลัก'
    ],
    
    // Database Seed Mappings for Rooms/Amenities/Landmarks
    'room_desc_1' => [
        'en' => 'Experience ultimate relaxation in our Deluxe Room, featuring a private balcony with panoramic ocean views, a plush king-size bed, and a modern marble bathroom.',
        'th' => 'สัมผัสประสบการณ์การพักผ่อนขั้นสุดยอดในห้องดีลักซ์ของเรา โดดเด่นด้วยระเบียงส่วนตัวพร้อมทิวทัศน์มหาสมุทรแบบพาโนรามา เตียงนอนขนาดคิงไซส์สุดนุ่ม และห้องน้ำหินอ่อนที่หรูหราทันสมัย'
    ],
    'room_desc_2' => [
        'en' => 'The Grand Executive Suite offers spacious sophistication with a separate living area, curated artworks, a deep soaking tub, and exclusive access to the Club Lounge.',
        'th' => 'แกรนด์ เอ็กเซกคิวทีฟ สูท มอบความหรูหราที่กว้างขวางด้วยพื้นที่นั่งเล่นที่แยกเป็นสัดส่วน ตกแต่งด้วยงานศิลปะที่คัดสรรมาอย่างดี อ่างอาบน้ำทรงลึกที่ให้คุณแช่ตัวได้อย่างสบาย และสิทธิพิเศษในการเข้าใช้คลับเลานจ์'
    ],
    'room_desc_3' => [
        'en' => 'Indulge in unmatched luxury. The Royal Penthouse spans two floors, offering a private infinity pool, a state-of-the-art kitchen, 24/7 butler service, and a breathtaking 360-degree skyline view.',
        'th' => 'ดื่มด่ำกับความหรูหราที่ไม่มีใครเทียบได้ รอยัล เพนท์เฮ้าส์ ครอบคลุมพื้นที่สองชั้น พร้อมสระว่ายน้ำอินฟินิตี้ส่วนตัว ห้องครัวที่ทันสมัย บริการบัตเลอร์ส่วนตัวตลอด 24 ชั่วโมง และทัศนียภาพเส้นขอบฟ้าแบบ 360 องศาที่น่าทึ่ง'
    ],
    'amt_wifi' => [ 'en' => 'Free Wi-Fi', 'th' => 'ฟรี Wi-Fi' ],
    'amt_pool' => [ 'en' => 'Swimming Pool', 'th' => 'สระว่ายน้ำ' ],
    'amt_parking' => [ 'en' => 'Free Parking', 'th' => 'ที่จอดรถฟรี' ],
    'amt_gym' => [ 'en' => 'Fitness Gym', 'th' => 'ห้องฟิตเนส' ],
    'amt_spa' => [ 'en' => 'Luxury Spa', 'th' => 'สปาสุดหรู' ],
    'amt_room_service' => [ 'en' => 'Room Service', 'th' => 'รูมเซอร์วิส' ],
    'amt_mini_bar' => [ 'en' => 'Mini Bar', 'th' => 'มินิบาร์' ],
    'amt_smart_tv' => [ 'en' => 'Smart TV', 'th' => 'สมาร์ททีวี' ],
    'amt_air_con' => [ 'en' => 'Air Conditioning', 'th' => 'เครื่องปรับอากาศ' ],
    'amt_bathtub' => [ 'en' => 'Marble Bathtub', 'th' => 'อ่างอาบน้ำหินอ่อน' ],
    'amt_private_jacuzzi' => [ 'en' => 'Private Jacuzzi', 'th' => 'จากุซซี่ส่วนตัว' ],
    'amt_kitchen' => [ 'en' => 'Kitchen', 'th' => 'ห้องครัวปรุงอาหาร' ],
    
    'landmark_Patong Beach' => [ 'en' => 'Patong Beach', 'th' => 'หาดป่าตอง' ],
    'landmark_Old Town Shopping District' => [ 'en' => 'Old Town Shopping District', 'th' => 'ย่านช้อปปิ้งเมืองเก่า' ],
    'landmark_Skyline Viewpoint' => [ 'en' => 'Skyline Viewpoint', 'th' => 'จุดชมวิวขอบฟ้าเมือง' ],
    'footer_desc' => [
        'en' => 'An oasis of sophistication and luxury, offering five-star oceanfront accommodation, award-winning spa experiences, and Michelin-starred dining.',
        'th' => 'โอเอซิสแห่งความหรูหราและความประณีต นำเสนอห้องพักระดับห้าดาวติดชายหาด ประสบการณ์สปาที่ได้รับรางวัล และการรับประทานอาหารระดับมิชลินสตาร์'
    ],
    'footer_all_rights' => [
        'en' => '&copy; {year} Nom Tuay Resort. All rights reserved.',
        'th' => '&copy; {year} บ้านหนมถ้วยรีสอร์ท สงวนลิขสิทธิ์.'
    ],
    'detail_extras_title' => [
        'en' => 'Premium Extra Services',
        'th' => 'บริการเสริมพิเศษระดับพรีเมียม'
    ],
    'detail_extra_breakfast' => [
        'en' => 'Breakfast Buffet (+฿500 / night)',
        'th' => 'อาหารเช้าแบบบุฟเฟต์ (+฿500 / คืน)'
    ],
    'detail_extra_shuttle' => [
        'en' => 'Private Airport Shuttle (+฿1,200 / way)',
        'th' => 'รถตู้หรูรับ-ส่งสนามบินส่วนตัว (+฿1,200 / เที่ยว)'
    ],
    'detail_extra_spa' => [
        'en' => 'Luxury Beachfront Spa Access (+฿2,000)',
        'th' => 'แพ็กเกจสปาคู่รักริมชายหาด (+฿2,000)'
    ],
    'detail_promo_code' => [
        'en' => 'Promotion Code',
        'th' => 'รหัสส่วนลด / คูปอง'
    ],
    'detail_promo_apply' => [
        'en' => 'Apply',
        'th' => 'ใช้โค้ด'
    ],
    'detail_promo_discount' => [
        'en' => 'Special Discount',
        'th' => 'ส่วนลดพิเศษ'
    ],
    'detail_extras_total' => [
        'en' => 'Services Total',
        'th' => 'ราคารวมบริการเสริม'
    ],
    'detail_invalid_promo' => [
        'en' => 'Invalid Promo Code',
        'th' => 'รหัสส่วนลดไม่ถูกต้อง'
    ]
];

// Helper function to translate keys
function __t($key, $replacements = []) {
    global $translations, $current_lang, $pdo;
    static $db_settings = null;
    
    if ($db_settings === null) {
        $db_settings = [];
        if (isset($pdo)) {
            try {
                $stmt = $pdo->query("SELECT key_name, value_en, value_th FROM settings");
                while ($row = $stmt->fetch()) {
                    $db_settings[$row['key_name']] = [
                        'en' => $row['value_en'],
                        'th' => $row['value_th']
                    ];
                }
            } catch (Exception $e) {
                // Fallback if table not ready
            }
        }
    }
    
    $text = null;
    if (isset($db_settings[$key])) {
        $text = $db_settings[$key][$current_lang] ?? null;
    }
    
    if ($text === null) {
        $text = $translations[$key][$current_lang] ?? $key;
    }
    
    foreach ($replacements as $search => $replace) {
        $text = str_replace('{' . $search . '}', $replace, $text);
    }
    
    return $text;
}
?>
