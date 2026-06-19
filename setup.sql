-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `hotel_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hotel_db`;

-- Drop tables in order if they exist
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `landmarks`;

-- 1. Create Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL, -- BCrypt Hash
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create Admins Table
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL, -- BCrypt Hash
  `name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2b. Create Settings Table
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key_name` VARCHAR(100) UNIQUE NOT NULL,
  `value_en` TEXT NOT NULL,
  `value_th` TEXT NOT NULL,
  `type` VARCHAR(20) NOT NULL DEFAULT 'text',
  `category` VARCHAR(50) NOT NULL DEFAULT 'general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2c. Create Landmarks Table
CREATE TABLE `landmarks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name_en` VARCHAR(100) UNIQUE NOT NULL,
  `name_th` VARCHAR(100) NOT NULL,
  `distance_km` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `map_top_percent` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
  `map_left_percent` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create Rooms Table
CREATE TABLE `rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `type` VARCHAR(50) NOT NULL, -- 'Deluxe', 'Suite', 'Penthouse'
  `price_per_night` DECIMAL(10,2) NOT NULL,
  `size_sqm` INT NOT NULL,
  `capacity` INT NOT NULL,
  `total_rooms` INT NOT NULL,
  `description` TEXT NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `amenities` TEXT NOT NULL, -- JSON array of strings e.g., ["wifi", "pool", "parking", "gym", "spa", "room_service"]
  `distance_landmark` DECIMAL(5,2) NOT NULL, -- Distance in km
  `landmark_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create Bookings Table
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_reference` VARCHAR(20) UNIQUE NOT NULL, -- e.g., RSV-12345
  `room_id` INT NOT NULL,
  `guest_name` VARCHAR(100) NOT NULL,
  `guest_email` VARCHAR(100) NOT NULL,
  `guest_phone` VARCHAR(20) NOT NULL,
  `check_in` DATE NOT NULL,
  `check_out` DATE NOT NULL,
  `guests_count` INT NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'confirmed', -- 'confirmed', 'modified', 'cancelled'
  `payment_status` VARCHAR(20) NOT NULL DEFAULT 'pending', -- 'pending', 'paid', 'refunded'
  `payment_method` VARCHAR(50) DEFAULT NULL, -- 'credit_card', 'promptpay', 'bank_transfer'
  `payment_transaction_id` VARCHAR(100) DEFAULT NULL,
  `payment_date` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Create Reviews Table
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT NOT NULL,
  `guest_name` VARCHAR(100) NOT NULL,
  `rating_cleanliness` DECIMAL(3,1) NOT NULL,
  `rating_service` DECIMAL(3,1) NOT NULL,
  `rating_value` DECIMAL(3,1) NOT NULL,
  `overall_rating` DECIMAL(3,1) NOT NULL,
  `comment` TEXT NOT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Rooms Data
INSERT INTO `rooms` (`id`, `name`, `type`, `price_per_night`, `size_sqm`, `capacity`, `total_rooms`, `description`, `image_url`, `amenities`, `distance_landmark`, `landmark_name`) VALUES
(1, 'Deluxe Ocean View Room', 'Deluxe', 4500.00, 45, 2, 10, 'Experience ultimate relaxation in our Deluxe Room, featuring a private balcony with panoramic ocean views, a plush king-size bed, and a modern marble bathroom.', 'assets/images/room_deluxe.jpg', '["wifi", "pool", "parking", "mini_bar", "smart_tv", "air_con"]', 0.10, 'Patong Beach'),
(2, 'Grand Executive Suite', 'Suite', 8500.00, 75, 3, 5, 'The Grand Executive Suite offers spacious sophistication with a separate living area, curated artworks, a deep soaking tub, and exclusive access to the Club Lounge.', 'assets/images/room_suite.jpg', '["wifi", "pool", "parking", "gym", "spa", "room_service", "mini_bar", "smart_tv", "air_con", "bathtub"]', 1.50, 'Old Town Shopping District'),
(3, 'The Royal Penthouse', 'Penthouse', 18000.00, 150, 4, 2, 'Indulge in unmatched luxury. The Royal Penthouse spans two floors, offering a private infinity pool, a state-of-the-art kitchen, 24/7 butler service, and a breathtaking 360-degree skyline view.', 'assets/images/room_penthouse.jpg', '["wifi", "pool", "parking", "gym", "spa", "room_service", "mini_bar", "smart_tv", "air_con", "bathtub", "private_jacuzzi", "kitchen"]', 2.00, 'Skyline Viewpoint');

-- Seed Reviews Data
INSERT INTO `reviews` (`room_id`, `guest_name`, `rating_cleanliness`, `rating_service`, `rating_value`, `overall_rating`, `comment`, `image_url`) VALUES
(1, 'Sarah Connor', 5.0, 4.8, 4.5, 4.8, 'The view of the ocean is absolutely spectacular. The room was sparkling clean and the staff were very accommodating.', 'assets/images/review_ocean_view.jpg'),
(1, 'Michael Chang', 4.5, 4.5, 4.0, 4.3, 'Great location, literally steps away from the beach. Highly recommend the sunset views from the balcony.', NULL),
(2, 'Alexander Mercer', 5.0, 5.0, 4.8, 4.9, 'An exceptional experience. The club lounge access was worth every penny. The executive suite layout is perfect for business trips.', 'assets/images/review_suite_luxury.jpg'),
(3, 'Victoria Sterling', 5.0, 5.0, 5.0, 5.0, 'Simply magnificent. The butler service was impeccable, and the private pool overlooking the entire city at night is unforgettable. The definition of 5-star luxury.', NULL);

-- Seed Admin Account (username: admin, password: adminpassword)
INSERT INTO `admins` (`username`, `password`, `name`) VALUES
('admin@gmail.com', '$2y$10$ol1HyCvqKwIsLxUgA40Pu.o8lWysxE94Ylg1xmqFCbK1q3viSk/Ra', 'Hotel Administrator');

-- Seed Sample User Account (email: guest@example.com, password: guestpassword)
-- bcrypt hash of 'guestpassword': $2y$10$qVig2dY7i0Pz1n7rWj3vpeo5k1p2o6w0C2e9R9rFzK0z2z7t3a1Kq
INSERT INTO `users` (`email`, `password`, `name`, `phone`) VALUES
('guest@example.com', '$2y$10$qVig2dY7i0Pz1n7rWj3vpeo5k1p2o6w0C2e9R9rFzK0z2z7t3a1Kq', 'John Guest', '+66 81 111 2222');

-- Seed Settings Data
INSERT INTO `settings` (`key_name`, `value_en`, `value_th`, `type`, `category`) VALUES
('hero_title', 'Nom Tuay Resort', 'บ้านหนมถ้วยรีสอร์ท', 'text', 'hero'),
('hero_subtitle', 'Welcome to Sanctuary of Luxury', 'ยินดีต้อนรับสู่ดินแดนแห่งความหรูหรา', 'text', 'hero'),
('hero_desc', 'Indulge in an unparalleled retreat where modern luxury meets timeless elegance. Nestled on pristine shores, discover your home away from home.', 'ดื่มด่ำกับสถานที่พักผ่อนที่เหนือระดับ ที่ซึ่งความหรูหราทันสมัยผสมผสานกับความสง่างามเหนือกาลเวลา ค้นพบการพักผ่อนเสมือนบ้านของคุณบนชายฝั่งที่บริสุทธิ์', 'textarea', 'hero'),
('hero_bg_image', 'assets/images/hero_bg.jpg', 'assets/images/hero_bg.jpg', 'image', 'hero'),
('index_phil_sub', 'Our Philosophy', 'ปรัชญาของเรา', 'text', 'philosophy'),
('index_phil_title', 'A Symphony of Exclusivity & Comfort', 'ท่วงทำนองแห่งความพิเศษและความสะดวกสบาย', 'text', 'philosophy'),
('index_phil_desc', 'At Nom Tuay Resort, we believe that true luxury is personal. Every detail, from the hand-woven Italian linens to the bespoke dining experiences curated by Michelin-starred chefs, is designed to elevate your senses. Experience world-class hospitality tailored to your highest expectations.', 'ที่ บ้านหนมถ้วยรีสอร์ท เราเชื่อว่าความหรูหราที่แท้จริงคือเรื่องส่วนบุคคล ทุกๆ รายละเอียด ตั้งแต่ผ้าปูที่นอนอิตาลีทอมือไปจนถึงประสบการณ์การรับประทานอาหารสุดพิเศษที่คัดสรรโดยเชฟระดับมิชลินสตาร์ ได้รับการออกแบบมาเพื่อยกระดับประสาทสัมผัสของคุณ สัมผัสประสบการณ์การต้อนรับระดับโลกที่ปรับให้ตอบสนองความคาดหวังสูงสุดของคุณ', 'textarea', 'philosophy'),
('index_services_sub', 'Elevated Indulgence', 'บริการระดับพรีเมียม', 'text', 'services'),
('index_services_title', 'Signature Services', 'บริการอันเป็นเอกลักษณ์', 'text', 'services'),
('index_service_spa', 'The Serenity Spa', 'เดอะ เซเรนิตี้ สปา', 'text', 'services'),
('index_service_spa_desc', 'Rejuvenate your body and soul with bespoke therapies, thermal suites, and healing mineral baths led by world-class specialists.', 'ฟื้นฟูร่างกายและจิตวิญญาณของคุณด้วยการบำบัดเฉพาะบุคคล ห้องอบไอน้ำความร้อน และการอาบน้ำแร่บำบัดโดยผู้เชี่ยวชาญระดับโลก', 'textarea', 'services'),
('index_service_dining', 'Michelin-Starred Dining', 'การรับประทานอาหารระดับมิชลินสตาร์', 'text', 'services'),
('index_service_dining_desc', 'Embark on a culinary journey at L\'Horizon, presenting modern French-Asian fusion gastronomy paired with rare vintage wines.', 'เริ่มต้นการเดินทางสู่อาหารรสเลิศที่ L\'Horizon นำเสนออาหารฟิวชั่นฝรั่งเศส-เอเชียสมัยใหม่ ควบคู่กับไวน์วินเทจหายาก', 'textarea', 'services'),
('index_service_butler', '24/7 Butler Service', 'บริการบัตเลอร์ส่วนตัวตลอด 24 ชั่วโมง', 'text', 'services'),
('index_service_butler_desc', 'Experience absolute convenience. Our dedicated butler service is at your command, anticipating your every need at any hour.', 'สัมผัสความสะดวกสบายอย่างสมบูรณ์แบบ บริการบัตเลอร์เฉพาะทุ่มเทของเราพร้อมดูแลตามที่คุณต้องการ คาดการณ์และจัดการทุกสิ่งให้คุณตลอด 24 ชั่วโมง', 'textarea', 'services'),
('index_testi_sub', 'Guest Testimonials', 'ความประทับใจจากผู้เข้าพัก', 'text', 'testimonials'),
('index_testi_title', 'Whispers of Satisfaction', 'เสียงสะท้อนแห่งความพึงพอใจ', 'text', 'testimonials'),
('index_testi_quote', 'Simply magnificent. The butler service was impeccable, and the private pool overlooking the entire city at night is unforgettable. The definition of 5-star luxury.', 'งดงามตระการตาอย่างแท้จริง บริการของบัตเลอร์ไร้ที่ติ และสระว่ายน้ำส่วนตัวที่มองเห็นวิวเมืองทั้งเมืองในตอนกลางคืนเป็นสิ่งที่ลืมไม่ลง คำจำกัดความของความหรูหราระดับ 5 ดาว', 'textarea', 'testimonials'),
('index_testi_author', 'Victoria Sterling', 'วิคตอเรีย สเตอร์ลิง', 'text', 'testimonials'),
('index_testi_author_subtitle', 'Royal Penthouse Guest', 'ผู้เข้าพักห้องรอยัล เพนท์เฮ้าส์', 'text', 'testimonials');

-- Seed Landmarks Data
INSERT INTO `landmarks` (`name_en`, `name_th`, `distance_km`, `map_top_percent`, `map_left_percent`) VALUES
('Patong Beach', 'หาดป่าตอง', 0.10, 25.00, 35.00),
('Old Town Shopping District', 'ย่านช้อปปิ้งเมืองเก่า', 1.50, 65.00, 20.00),
('Skyline Viewpoint', 'จุดชมวิวขอบฟ้าเมือง', 2.00, 40.00, 75.00);
