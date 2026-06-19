<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''; // Default XAMPP password is empty
$dbname = getenv('DB_NAME') ?: 'hotel_db';

try {
    // Attempt to connect to MySQL without specifying a database first
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Check if database exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    $dbExists = $stmt->fetch();

    if (!$dbExists) {
        // Create database
        $pdo->exec("CREATE DATABASE `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    // Connect to the specific database
    $pdo->exec("USE `$dbname`");

    // Check if rooms, admins and users tables exist
    $tableCheckRooms = $pdo->query("SHOW TABLES LIKE 'rooms'");
    $tableCheckAdmins = $pdo->query("SHOW TABLES LIKE 'admins'");
    $tableCheckUsers = $pdo->query("SHOW TABLES LIKE 'users'");
    
    if (!$tableCheckRooms->fetch() || !$tableCheckAdmins->fetch() || !$tableCheckUsers->fetch()) {
        // Run the setup.sql file
        $sqlPath = __DIR__ . '/setup.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            // Execute multi-query using exec
            $pdo->exec($sql);
        } else {
            throw new Exception("Database setup.sql file not found.");
        }
    }

    // Always guarantee that the admin user exists with the correct hash ('adminpassword')
    $adminPasswordHash = '$2y$10$ol1HyCvqKwIsLxUgA40Pu.o8lWysxE94Ylg1xmqFCbK1q3viSk/Ra';
    $syncAdmin = $pdo->prepare("INSERT INTO admins (id, username, password, name) 
                                VALUES (1, 'admin@gmail.com', ?, 'Hotel Administrator') 
                                ON DUPLICATE KEY UPDATE username = 'admin@gmail.com', password = ?, name = 'Hotel Administrator'");
    $syncAdmin->execute([$adminPasswordHash, $adminPasswordHash]);

    // Always guarantee that the sample guest exists with the correct hash ('guestpassword')
    $guestPasswordHash = '$2y$10$qVig2dY7i0Pz1n7rWj3vpeo5k1p2o6w0C2e9R9rFzK0z2z7t3a1Kq';
    $syncGuest = $pdo->prepare("INSERT INTO users (id, email, password, name, phone) 
                                VALUES (1, 'guest@example.com', ?, 'John Guest', '+66 81 111 2222') 
                                ON DUPLICATE KEY UPDATE password = ?, name = 'John Guest', phone = '+66 81 111 2222'");
    $syncGuest->execute([$guestPasswordHash, $guestPasswordHash]);

    // 1. Create settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `key_name` VARCHAR(100) UNIQUE NOT NULL,
      `value_en` TEXT NOT NULL,
      `value_th` TEXT NOT NULL,
      `type` VARCHAR(20) NOT NULL DEFAULT 'text',
      `category` VARCHAR(50) NOT NULL DEFAULT 'general'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed default settings if empty
    $settingsCount = $pdo->query("SELECT COUNT(*) FROM `settings`")->fetchColumn();
    if ($settingsCount == 0) {
        $defaultSettings = [
            ['hero_title', 'Nom Tuay Resort', 'บ้านหนมถ้วยรีสอร์ท', 'text', 'hero'],
            ['hero_subtitle', 'Welcome to Sanctuary of Luxury', 'ยินดีต้อนรับสู่ดินแดนแห่งความหรูหรา', 'text', 'hero'],
            ['hero_desc', 'Indulge in an unparalleled retreat where modern luxury meets timeless elegance. Nestled on pristine shores, discover your home away from home.', 'ดื่มด่ำกับสถานที่พักผ่อนที่เหนือระดับ ที่ซึ่งความหรูหราทันสมัยผสมผสานกับความสง่างามเหนือกาลเวลา ค้นพบการพักผ่อนเสมือนบ้านของคุณบนชายฝั่งที่บริสุทธิ์', 'textarea', 'hero'],
            ['hero_bg_image', 'assets/images/hero_bg.jpg', 'assets/images/hero_bg.jpg', 'image', 'hero'],
            
            ['index_phil_sub', 'Our Philosophy', 'ปรัชญาของเรา', 'text', 'philosophy'],
            ['index_phil_title', 'A Symphony of Exclusivity & Comfort', 'ท่วงทำนองแห่งความพิเศษและความสะดวกสบาย', 'text', 'philosophy'],
            ['index_phil_desc', 'At Nom Tuay Resort, we believe that true luxury is personal. Every detail, from the hand-woven Italian linens to the bespoke dining experiences curated by Michelin-starred chefs, is designed to elevate your senses. Experience world-class hospitality tailored to your highest expectations.', 'ที่ บ้านหนมถ้วยรีสอร์ท เราเชื่อว่าความหรูหราที่แท้จริงคือเรื่องส่วนบุคคล ทุกๆ รายละเอียด ตั้งแต่ผ้าปูที่นอนอิตาลีทอมือไปจนถึงประสบการณ์การรับประทานอาหารสุดพิเศษที่คัดสรรโดยเชฟระดับมิชลินสตาร์ ได้รับการออกแบบมาเพื่อยกระดับประสาทสัมผัสของคุณ สัมผัสประสบการณ์การต้อนรับระดับโลกที่ปรับให้ตอบสนองความคาดหวังสูงสุดของคุณ', 'textarea', 'philosophy'],
            
            ['index_services_sub', 'Elevated Indulgence', 'บริการระดับพรีเมียม', 'text', 'services'],
            ['index_services_title', 'Signature Services', 'บริการอันเป็นเอกลักษณ์', 'text', 'services'],
            ['index_service_spa', 'The Serenity Spa', 'เดอะ เซเรนิตี้ สปา', 'text', 'services'],
            ['index_service_spa_desc', 'Rejuvenate your body and soul with bespoke therapies, thermal suites, and healing mineral baths led by world-class specialists.', 'ฟื้นฟูร่างกายและจิตวิญญาณของคุณด้วยการบำบัดเฉพาะบุคคล ห้องอบไอน้ำความร้อน และการอาบน้ำแร่บำบัดโดยผู้เชี่ยวชาญระดับโลก', 'textarea', 'services'],
            ['index_service_dining', 'Michelin-Starred Dining', 'การรับประทานอาหารระดับมิชลินสตาร์', 'text', 'services'],
            ['index_service_dining_desc', 'Embark on a culinary journey at L\'Horizon, presenting modern French-Asian fusion gastronomy paired with rare vintage wines.', 'เริ่มต้นการเดินทางสู่อาหารรสเลิศที่ L\'Horizon นำเสนออาหารฟิวชั่นฝรั่งเศส-เอเชียสมัยใหม่ ควบคู่กับไวน์วินเทจหายาก', 'textarea', 'services'],
            ['index_service_butler', '24/7 Butler Service', 'บริการบัตเลอร์ส่วนตัวตลอด 24 ชั่วโมง', 'text', 'services'],
            ['index_service_butler_desc', 'Experience absolute convenience. Our dedicated butler service is at your command, anticipating your every need at any hour.', 'สัมผัสความสะดวกสบายอย่างสมบูรณ์แบบ บริการบัตเลอร์เฉพาะทุ่มเทของเราพร้อมดูแลตามที่คุณต้องการ คาดการณ์และจัดการทุกสิ่งให้คุณตลอด 24 ชั่วโมง', 'textarea', 'services'],
            
            ['index_testi_sub', 'Guest Testimonials', 'ความประทับใจจากผู้เข้าพัก', 'text', 'testimonials'],
            ['index_testi_title', 'Whispers of Satisfaction', 'เสียงสะท้อนแห่งความพึงพอใจ', 'text', 'testimonials'],
            ['index_testi_quote', 'Simply magnificent. The butler service was impeccable, and the private pool overlooking the entire city at night is unforgettable. The definition of 5-star luxury.', 'งดงามตระการตาอย่างแท้จริง บริการของบัตเลอร์ไร้ที่ติ และสระว่ายน้ำส่วนตัวที่มองเห็นวิวเมืองทั้งเมืองในตอนกลางคืนเป็นสิ่งที่ลืมไม่ลง คำจำกัดความของความหรูหราระดับ 5 ดาว', 'textarea', 'testimonials'],
            ['index_testi_author', 'Victoria Sterling', 'วิคตอเรีย สเตอร์ลิง', 'text', 'testimonials'],
            ['index_testi_author_subtitle', 'Royal Penthouse Guest', 'ผู้เข้าพักห้องรอยัล เพนท์เฮ้าส์', 'text', 'testimonials'],
            ['hotel_latitude', '7.8969000', '7.8969000', 'text', 'general'],
            ['hotel_longitude', '98.2966000', '98.2966000', 'text', 'general']
        ];
        
        $stmtInsertSetting = $pdo->prepare("INSERT INTO `settings` (`key_name`, `value_en`, `value_th`, `type`, `category`) VALUES (?, ?, ?, ?, ?)");
        foreach ($defaultSettings as $ds) {
            $stmtInsertSetting->execute($ds);
        }
    }

    // 2. Create landmarks table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `landmarks` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `name_en` VARCHAR(100) UNIQUE NOT NULL,
      `name_th` VARCHAR(100) NOT NULL,
      `distance_km` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
      `map_top_percent` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
      `map_left_percent` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
      `latitude` DECIMAL(10,7) NOT NULL DEFAULT 7.8969000,
      `longitude` DECIMAL(10,7) NOT NULL DEFAULT 98.2966000,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed default landmarks if empty
    $landmarksCount = $pdo->query("SELECT COUNT(*) FROM `landmarks`")->fetchColumn();
    if ($landmarksCount == 0) {
        $defaultLandmarks = [
            ['Patong Beach', 'หาดป่าตอง', 0.10, 25.00, 35.00, 7.8920000, 98.2950000],
            ['Old Town Shopping District', 'ย่านช้อปปิ้งเมืองเก่า', 1.50, 65.00, 20.00, 7.8849000, 98.3904000],
            ['Skyline Viewpoint', 'จุดชมวิวขอบฟ้าเมือง', 2.00, 40.00, 75.00, 7.8184000, 98.3020000]
        ];
        
        $stmtInsertLandmark = $pdo->prepare("INSERT INTO `landmarks` (`name_en`, `name_th`, `distance_km`, `map_top_percent`, `map_left_percent`, `latitude`, `longitude`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($defaultLandmarks as $dl) {
            $stmtInsertLandmark->execute($dl);
        }
    }

    // Migration logic for existing databases
    // 1. Enforce latitude and longitude columns in landmarks table
    $columnCheckLat = $pdo->query("SHOW COLUMNS FROM `landmarks` LIKE 'latitude'");
    if (!$columnCheckLat->fetch()) {
        $pdo->exec("ALTER TABLE `landmarks` ADD COLUMN `latitude` DECIMAL(10,7) NOT NULL DEFAULT 7.8969000");
        $pdo->exec("ALTER TABLE `landmarks` ADD COLUMN `longitude` DECIMAL(10,7) NOT NULL DEFAULT 98.2966000");
        
        // Update coordinates for default landmarks
        $pdo->exec("UPDATE `landmarks` SET `latitude` = 7.8920000, `longitude` = 98.2950000 WHERE `name_en` = 'Patong Beach'");
        $pdo->exec("UPDATE `landmarks` SET `latitude` = 7.8849000, `longitude` = 98.3904000 WHERE `name_en` = 'Old Town Shopping District'");
        $pdo->exec("UPDATE `landmarks` SET `latitude` = 7.8184000, `longitude` = 98.3020000 WHERE `name_en` = 'Skyline Viewpoint'");
    }

    // 2. Enforce hotel_latitude and hotel_longitude settings
    $checkHotelLat = $pdo->query("SELECT COUNT(*) FROM `settings` WHERE `key_name` = 'hotel_latitude'")->fetchColumn();
    if ($checkHotelLat == 0) {
        $stmtInsertSetting = $pdo->prepare("INSERT INTO `settings` (`key_name`, `value_en`, `value_th`, `type`, `category`) VALUES (?, ?, ?, ?, ?)");
        $stmtInsertSetting->execute(['hotel_latitude', '7.8969000', '7.8969000', 'text', 'general']);
        $stmtInsertSetting->execute(['hotel_longitude', '98.2966000', '98.2966000', 'text', 'general']);
    }

    // 3. Enforce extra_services and promo_code columns in bookings table
    $columnCheckExtra = $pdo->query("SHOW COLUMNS FROM `bookings` LIKE 'extra_services'");
    if (!$columnCheckExtra->fetch()) {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `extra_services` TEXT NULL");
    }
    $columnCheckPromo = $pdo->query("SHOW COLUMNS FROM `bookings` LIKE 'promo_code'");
    if (!$columnCheckPromo->fetch()) {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `promo_code` VARCHAR(50) NULL");
    }

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
