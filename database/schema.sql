CREATE DATABASE IF NOT EXISTS `T@ke9ine`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `T@ke9ine`;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'guest') NOT NULL DEFAULT 'guest',
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role ENUM('admin', 'staff', 'guest') NOT NULL DEFAULT 'guest'
    AFTER password_hash;

CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_username (username),
    UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB;

ALTER TABLE admins
    ADD COLUMN IF NOT EXISTS username VARCHAR(50) NULL AFTER id,
    ADD UNIQUE INDEX IF NOT EXISTS uq_admins_username (username);

UPDATE admins
SET username = LEFT(LOWER(REPLACE(REPLACE(SUBSTRING_INDEX(email, '@', 1), '.', '-'), '_', '-')), 50)
WHERE username IS NULL OR username = '';

UPDATE admins AS a
JOIN (
    SELECT id,
           CASE
               WHEN ROW_NUMBER() OVER (PARTITION BY username ORDER BY id) = 1
                   THEN username
               ELSE CONCAT(LEFT(username, 44), '-', id)
           END AS migrated_username
    FROM admins
) AS mapped ON mapped.id = a.id
SET a.username = mapped.migrated_username;

ALTER TABLE admins
    MODIFY COLUMN username VARCHAR(50) NOT NULL;

INSERT IGNORE INTO admins (username, email, password_hash, role, created_at, updated_at)
SELECT
    CONCAT(LEFT(LOWER(REPLACE(REPLACE(SUBSTRING_INDEX(email, '@', 1), '.', '-'), '_', '-')), 44), '-', id),
    email,
    password_hash,
    role,
    created_at,
    updated_at
FROM users
WHERE role = 'admin';

CREATE TABLE IF NOT EXISTS otp_verifications (
    challenge_id CHAR(64) NOT NULL,
    purpose VARCHAR(20) NOT NULL,
    subject_id BIGINT UNSIGNED NULL,
    code_hash VARCHAR(255) NOT NULL,
    expires_at BIGINT UNSIGNED NOT NULL,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (challenge_id),
    KEY idx_otp_expiration (expires_at),
    KEY idx_otp_subject_purpose (subject_id, purpose)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    challenge_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expires_at BIGINT UNSIGNED NOT NULL,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (challenge_id),
    KEY idx_password_reset_user_created (user_id, created_at),
    KEY idx_password_reset_expiry (expires_at),
    CONSTRAINT fk_password_reset_user
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_reference VARCHAR(20) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    guests TINYINT UNSIGNED NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    room_type VARCHAR(20) NOT NULL,
    breakfast TINYINT(1) NOT NULL DEFAULT 0,
    airport_transfer TINYINT(1) NOT NULL DEFAULT 0,
    laundry TINYINT(1) NOT NULL DEFAULT 0,
    special_requests VARCHAR(1000) NULL,
    room_total INT UNSIGNED NOT NULL,
    services_total INT UNSIGNED NOT NULL,
    grand_total INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_booking_reference (booking_reference),
    KEY idx_bookings_user (user_id),
    KEY idx_bookings_email (email),
    KEY idx_bookings_dates (check_in, check_out),
    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rooms (
    room_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_number VARCHAR(20) NOT NULL,
    room_type VARCHAR(20) NOT NULL,
    description VARCHAR(500) NULL,
    price_per_night DECIMAL(10,2) UNSIGNED NOT NULL,
    capacity TINYINT UNSIGNED NOT NULL,
    status ENUM('available', 'booked', 'maintenance') NOT NULL DEFAULT 'available',
    image_path VARCHAR(255) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (room_id),
    UNIQUE KEY uq_rooms_number (room_number),
    KEY idx_rooms_status_type (status, room_type)
) ENGINE=InnoDB;

ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS room_id BIGINT UNSIGNED NULL AFTER id,
    ADD COLUMN IF NOT EXISTS special_requests VARCHAR(1000) NULL AFTER laundry,
    ADD INDEX IF NOT EXISTS idx_bookings_room_dates (room_id, check_in, check_out);

SET @take9ine_fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bookings'
      AND CONSTRAINT_NAME = 'fk_bookings_room'
);
SET @take9ine_fk_sql = IF(
    @take9ine_fk_exists = 0,
    'ALTER TABLE bookings ADD CONSTRAINT fk_bookings_room FOREIGN KEY (room_id) REFERENCES rooms (room_id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE take9ine_fk_statement FROM @take9ine_fk_sql;
EXECUTE take9ine_fk_statement;
DEALLOCATE PREPARE take9ine_fk_statement;

UPDATE bookings AS b
JOIN (
    SELECT r.room_id, r.room_type,
           ROW_NUMBER() OVER (PARTITION BY r.room_type ORDER BY r.room_number) AS booking_rank
    FROM rooms AS r
) AS ranked ON ranked.room_type = b.room_type
SET b.room_id = ranked.room_id
WHERE b.room_id IS NULL
  AND ranked.booking_rank = 1;

UPDATE rooms AS r
SET r.status = CASE
    WHEN r.status = 'maintenance' THEN 'maintenance'
    WHEN EXISTS (
        SELECT 1
        FROM bookings AS b
        WHERE b.room_id = r.room_id
          AND b.status NOT IN ('cancelled', 'completed')
          AND b.check_out > CURDATE()
    ) THEN 'booked'
    ELSE 'available'
END;

INSERT IGNORE INTO rooms
    (room_number, room_type, description, price_per_night, capacity, status, image_path)
VALUES
    ('101', 'single', 'A comfortable and practical room for one guest.', 3500.00, 1, 'available', 'assets/images/download (5).jpg'),
    ('102', 'single', 'A comfortable and practical room for one guest.', 3500.00, 1, 'available', 'assets/images/download (7).jpg'),
    ('103', 'single', 'A comfortable and practical room for one guest.', 3500.00, 1, 'available', 'assets/images/download (8).jpg'),
    ('104', 'double', 'A spacious room for couples or shared stays.', 5000.00, 2, 'available', 'assets/images/download (9).jpg'),
    ('105', 'double', 'A spacious room for couples or shared stays.', 5000.00, 2, 'available', 'assets/images/download (10).jpg'),
    ('106', 'double', 'A spacious room for couples or shared stays.', 5000.00, 2, 'available', 'assets/images/download (11).jpg'),
    ('201', 'executive', 'A refined room with additional comfort and space.', 7500.00, 2, 'available', 'assets/images/hotel-hero.jpg'),
    ('202', 'executive', 'A refined room with additional comfort and space.', 7500.00, 2, 'available', 'assets/images/hotel_image.jpg'),
    ('203', 'executive', 'A refined room with additional comfort and space.', 7500.00, 2, 'available', 'assets/images/hotel_hero.jpg'),
    ('301', 'extra', 'An additional bed option for a guest needing extra accommodation.', 1500.00, 1, 'available', 'assets/images/uderground1.jpg');

CREATE TABLE IF NOT EXISTS staff (
    staff_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    duty_roles VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (staff_id),
    KEY idx_staff_department (department)
) ENGINE=InnoDB;

INSERT IGNORE INTO staff (staff_id, name, department, duty_roles)
VALUES
    (1, 'Sarah Njeri', 'Front Office', 'Guest check-in, check-out, and reservations'),
    (2, 'Daniel Kimani', 'Housekeeping', 'Room cleaning, linen control, and guest supplies'),
    (3, 'Grace Achieng', 'Food and Beverage', 'Restaurant service and dining area supervision'),
    (4, 'Michael Otieno', 'Maintenance', 'Facility repairs and equipment inspections');
