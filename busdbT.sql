-- ============================================================
--  Bus Management System - database and tables
--
--  How to use: start MySQL in XAMPP, open phpMyAdmin -> Import ->
--  choose this file -> Go.
--  Safe to run more than once: nothing is dropped, existing tables
--  and rows are left alone, and the sample rows are only added once.
--  So after this file gets new sample rows, just import it again.
--
--  The database name matches db.php ("bus_management").
-- ============================================================

CREATE DATABASE IF NOT EXISTS bus_management
	DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bus_management;

-- ------------------------------------------------------------
-- users - every account, separated by role
-- log in with the email or the username
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
	id         INT AUTO_INCREMENT PRIMARY KEY,
	name       VARCHAR(100) NOT NULL,
	username   VARCHAR(50)  DEFAULT NULL UNIQUE, -- sign-up page has no username
	email      VARCHAR(120) NOT NULL UNIQUE,
	password   VARCHAR(255) NOT NULL,
	phone      VARCHAR(20)  DEFAULT NULL,
	role       ENUM('passenger','admin','bookingmgr','busroute') NOT NULL DEFAULT 'passenger',
	status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- buses, routes, schedules - used by the busroute pages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS buses (
	id            INT AUTO_INCREMENT PRIMARY KEY,
	bus_number    VARCHAR(30) NOT NULL UNIQUE,
	type          VARCHAR(30) NOT NULL,
	seat_capacity INT NOT NULL,
	layout        VARCHAR(20) NOT NULL,
	status        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
	created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS routes (
	id           INT AUTO_INCREMENT PRIMARY KEY,
	origin       VARCHAR(80) NOT NULL,
	destination  VARCHAR(80) NOT NULL,
	distance_km  DECIMAL(8,2) NOT NULL,
	est_duration VARCHAR(30) NOT NULL,
	status       ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
	created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS route_stops (
	id             INT AUTO_INCREMENT PRIMARY KEY,
	route_id       INT NOT NULL,
	stop_name      VARCHAR(80) NOT NULL,
	stop_order     INT NOT NULL,
	arrival_offset INT NOT NULL DEFAULT 0 COMMENT 'minutes after departure',
	FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- a bus or route cannot be deleted while a schedule still uses it
CREATE TABLE IF NOT EXISTS schedules (
	id             INT AUTO_INCREMENT PRIMARY KEY,
	bus_id         INT NOT NULL,
	route_id       INT NOT NULL,
	travel_date    DATE NOT NULL,
	departure_time TIME NOT NULL,
	arrival_time   TIME NOT NULL,
	fare           DECIMAL(10,2) NOT NULL,
	status         ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
	created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (bus_id)   REFERENCES buses(id),
	FOREIGN KEY (route_id) REFERENCES routes(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- passenger side
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS travellers (
	id        INT AUTO_INCREMENT PRIMARY KEY,
	user_id   INT NOT NULL,
	name      VARCHAR(100) NOT NULL,
	age       INT DEFAULT NULL,
	gender    ENUM('male','female','other') DEFAULT NULL,
	nid_no    VARCHAR(40) DEFAULT NULL,
	phone     VARCHAR(20) DEFAULT NULL,
	email     VARCHAR(120) DEFAULT NULL,
	relation  VARCHAR(40) DEFAULT NULL,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- an online ticket's code is TKT-<id>
CREATE TABLE IF NOT EXISTS bookings (
	id           INT AUTO_INCREMENT PRIMARY KEY,
	user_id      INT NOT NULL,
	schedule_id  INT NOT NULL,
	traveller_id INT DEFAULT NULL,
	seat_no      VARCHAR(10) NOT NULL,
	fare         DECIMAL(10,2) NOT NULL,
	status       ENUM('Confirmed','Cancelled','Completed') NOT NULL DEFAULT 'Confirmed',
	booked_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (user_id)      REFERENCES users(id) ON DELETE CASCADE,
	FOREIGN KEY (schedule_id)  REFERENCES schedules(id),
	FOREIGN KEY (traveller_id) REFERENCES travellers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- a saved route + optional date
CREATE TABLE IF NOT EXISTS watchlist (
	id             INT AUTO_INCREMENT PRIMARY KEY,
	user_id        INT NOT NULL,
	route_id       INT NOT NULL,
	preferred_date DATE DEFAULT NULL,
	FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
	FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- booking manager side - counter sales, seats, refunds
-- ------------------------------------------------------------

-- a counter ticket's code is CTR-<id>
CREATE TABLE IF NOT EXISTS counter_bookings (
	id            INT AUTO_INCREMENT PRIMARY KEY,
	schedule_id   INT NOT NULL,
	customer_name VARCHAR(100) NOT NULL,
	phone         VARCHAR(20) NOT NULL,
	nid_no        VARCHAR(40) DEFAULT NULL,
	seat_no       VARCHAR(10) NOT NULL,
	amount        DECIMAL(10,2) NOT NULL,
	payment_mode  ENUM('Cash','bKash','Nagad','Card') NOT NULL DEFAULT 'Cash',
	issued_by     INT NOT NULL,
	status        ENUM('Paid','Pending','Void') NOT NULL DEFAULT 'Paid',
	created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (schedule_id) REFERENCES schedules(id),
	FOREIGN KEY (issued_by)   REFERENCES users(id)
) ENGINE=InnoDB;

-- one row per taken seat; the UNIQUE key stops a seat being sold twice
CREATE TABLE IF NOT EXISTS seat_allocations (
	id          INT AUTO_INCREMENT PRIMARY KEY,
	schedule_id INT NOT NULL,
	seat_no     VARCHAR(10) NOT NULL,
	booking_ref VARCHAR(40) DEFAULT NULL,
	alloc_type  ENUM('Online','Counter','Blocked','Crew') NOT NULL DEFAULT 'Online',
	is_blocked  TINYINT(1) NOT NULL DEFAULT 0,
	remarks     VARCHAR(255) DEFAULT NULL,
	UNIQUE KEY uq_seat (schedule_id, seat_no),
	FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- booking_ref is TKT-<bookings.id> (online) or CTR-<counter_bookings.id> (counter)
-- deduction and refund_amount are money amounts, not percentages
CREATE TABLE IF NOT EXISTS refunds (
	id            INT AUTO_INCREMENT PRIMARY KEY,
	booking_ref   VARCHAR(40) NOT NULL,
	cancel_reason VARCHAR(255) NOT NULL,
	ticket_amount DECIMAL(10,2) NOT NULL,
	deduction     DECIMAL(10,2) NOT NULL DEFAULT 0,
	refund_amount DECIMAL(10,2) NOT NULL,
	method        ENUM('Cash','bKash','Nagad','Card','Bank Transfer') NOT NULL DEFAULT 'bKash',
	status        ENUM('Pending','Approved','Rejected','Settled') NOT NULL DEFAULT 'Pending',
	requested_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	processed_by  INT DEFAULT NULL,
	processed_at  DATETIME DEFAULT NULL,
	KEY idx_booking_ref (booking_ref),
	FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- admin side - complaints and notices
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaints (
	id          INT AUTO_INCREMENT PRIMARY KEY,
	user_id     INT NOT NULL,
	booking_ref VARCHAR(40) DEFAULT NULL,
	category    ENUM('Delay','Seat','Refund','Staff','Other') NOT NULL DEFAULT 'Other',
	subject     VARCHAR(150) NOT NULL,
	details     TEXT NOT NULL,
	priority    ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
	status      ENUM('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
	created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS complaint_replies (
	id           INT AUTO_INCREMENT PRIMARY KEY,
	complaint_id INT NOT NULL,
	admin_id     INT NOT NULL,
	message      TEXT NOT NULL,
	replied_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
	FOREIGN KEY (admin_id)     REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcements (
	id           INT AUTO_INCREMENT PRIMARY KEY,
	created_by   INT NOT NULL,
	title        VARCHAR(150) NOT NULL,
	body         TEXT NOT NULL,
	audience     ENUM('All','User','Admin','Manager') NOT NULL DEFAULT 'All',
	publish_from DATE NOT NULL,
	publish_to   DATE NOT NULL,
	is_active    TINYINT(1) NOT NULL DEFAULT 1,
	created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
--  SAMPLE DATA  (INSERT IGNORE + fixed ids = only added once)
--
--  Admin:          username admin      password admin
--  Other accounts: password 12345678 (log in with username or email)
-- ============================================================
INSERT IGNORE INTO users (id, name, username, email, password, phone, role) VALUES
(1, 'Admin User',      'admin',      'admin@bus.com',      '$2y$10$z3SUYouX.AKwxlyCYRULdeXpgbfS5f0T5edHhEQUTqoap19PEBzpm', '01711000001', 'admin'),
(2, 'Manager User',    'manager',    'manager@bus.com',    '$2y$10$etJBpqx9vCGZvkyP37wAa.KLwt6.zLPNGntlXAa8Pvxz7WCO.3KWK', '01711000002', 'busroute'),
(3, 'Normal User',     'user',       'user@bus.com',       '$2y$10$etJBpqx9vCGZvkyP37wAa.KLwt6.zLPNGntlXAa8Pvxz7WCO.3KWK', '01711000003', 'passenger'),
(4, 'Booking Manager', 'bookingmgr', 'bookingmgr@bus.com', '$2y$10$etJBpqx9vCGZvkyP37wAa.KLwt6.zLPNGntlXAa8Pvxz7WCO.3KWK', '01711000004', 'bookingmgr');

INSERT IGNORE INTO buses (id, bus_number, type, seat_capacity, layout, status) VALUES
(1, 'DHK-1101', 'AC',      40, '2x2', 'Active'),
(2, 'DHK-1102', 'Non-AC',  36, '2x2', 'Active'),
(3, 'DHK-1103', 'Sleeper', 28, '2x1', 'Inactive');

INSERT IGNORE INTO routes (id, origin, destination, distance_km, est_duration, status) VALUES
(1, 'Dhaka', 'Sylhet',     241.00, '6 hours',   'Active'),
(2, 'Dhaka', 'Chattogram', 264.00, '6.5 hours', 'Active'),
(3, 'Dhaka', 'Rajshahi',   256.00, '6 hours',   'Active');

INSERT IGNORE INTO schedules (id, bus_id, route_id, travel_date, departure_time, arrival_time, fare, status) VALUES
(1, 1, 1, '2026-09-20', '22:30:00', '04:30:00', 850.00, 'Active'),
(2, 2, 2, '2026-09-21', '08:00:00', '14:30:00', 650.00, 'Active'),
(3, 1, 3, '2026-09-22', '09:30:00', '15:30:00', 780.00, 'Active');

INSERT IGNORE INTO travellers (id, user_id, name, age, gender, phone, email, relation) VALUES
(1, 3, 'Rahim Uddin', 45, 'male', '01711000010', NULL, 'father');

-- booking 1 is live, booking 2 was cancelled (see refund 1)
INSERT IGNORE INTO bookings (id, user_id, schedule_id, traveller_id, seat_no, fare, status) VALUES
(1, 3, 1, NULL, 'A1', 850.00, 'Confirmed'),
(2, 3, 2, NULL, 'B2', 650.00, 'Cancelled');

-- counter sale 1 is live, counter sale 2 was released (see refund 2)
INSERT IGNORE INTO counter_bookings (id, schedule_id, customer_name, phone, seat_no, amount, payment_mode, issued_by, status) VALUES
(1, 1, 'Karim Hossain', '01811000020', 'B4', 850.00, 'Cash',  4, 'Paid'),
(2, 3, 'Salma Begum',   '01911000030', 'C1', 780.00, 'bKash', 4, 'Void');

INSERT IGNORE INTO seat_allocations (id, schedule_id, seat_no, booking_ref, alloc_type) VALUES
(1, 1, 'A1', 'TKT-1', 'Online'),
(2, 1, 'B4', 'CTR-1', 'Counter');

INSERT IGNORE INTO refunds (id, booking_ref, cancel_reason, ticket_amount, deduction, refund_amount, method, status, processed_by, processed_at) VALUES
(1, 'TKT-2', 'Travel plan changed', 650.00, 65.00, 585.00, 'bKash', 'Pending', NULL, NULL),
(2, 'CTR-2', 'Released at counter', 780.00,  0.00, 780.00, 'Cash',  'Settled', 4, '2026-09-12 15:00:00');

INSERT IGNORE INTO watchlist (id, user_id, route_id, preferred_date) VALUES
(1, 3, 2, NULL);

-- audience: All = everyone, User = passengers, Manager = booking managers, Admin = admins
-- (post, edit and switch them off on the Announcements page: admin or booking manager)
INSERT IGNORE INTO announcements (id, created_by, title, body, audience, publish_from, publish_to) VALUES
(1, 1, 'Welcome to online booking', 'You can now book seats, save co-travellers and watch routes online.', 'All', '2026-01-01', '2027-12-31'),
(2, 1, 'Counter cash check', 'Please count the counter cash and settle open refunds before closing each evening.', 'Manager', '2026-01-01', '2027-12-31'),
(3, 4, 'Arrive 15 minutes early', 'Boarding closes 10 minutes before departure. Please reach the counter at least 15 minutes early with your ticket code.', 'User', '2026-01-01', '2027-12-31');
