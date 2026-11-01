-- VFDSMS Complete Database Schema + Seed Data
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS vfdsms CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE vfdsms;

-- ── garage_settings ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS garage_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  garage_name VARCHAR(100) NOT NULL,
  garage_code VARCHAR(20),
  address VARCHAR(255),
  city VARCHAR(100),
  district VARCHAR(100),
  phone VARCHAR(30),
  email VARCHAR(100),
  tax_id VARCHAR(50),
  logo_url VARCHAR(255),
  established_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO garage_settings (id, garage_name, garage_code, address, city, district, phone, email, tax_id, logo_url, established_date) VALUES
(1, 'ATECAR GARAGE', 'ATG001', 'KG 17 Ave, Gishushu', 'Kigali', 'Gasabo', '+250788123000', 'info@atecar.rw', 'TIN-102345678', '/assets/logo.png', '2010-05-15');

-- ── users ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) DEFAULT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','staff','receptionist','client') NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  assigned_staff_id INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── vehicles ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  make VARCHAR(50) NOT NULL,
  model VARCHAR(50) NOT NULL,
  year INT NOT NULL,
  plate VARCHAR(20) NOT NULL UNIQUE,
  color VARCHAR(30) DEFAULT NULL,
  fuel_type ENUM('petrol','diesel','electric','hybrid') DEFAULT 'petrol',
  mileage INT DEFAULT 0,
  status ENUM('operational','faulty','under_maintenance','critical') DEFAULT 'operational',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── diagnoses ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS diagnoses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NOT NULL,
  receptionist_id INT NOT NULL,
  diagnosis_date DATE NOT NULL,
  fault_code VARCHAR(20) DEFAULT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT DEFAULT NULL,
  category ENUM('engine','transmission','brakes','electrical','suspension','exhaust','fuel','cooling','other') DEFAULT 'other',
  severity ENUM('low','medium','high','critical') DEFAULT 'medium',
  status ENUM('pending','in_progress','resolved') DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
  FOREIGN KEY (receptionist_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── invoices ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  diagnosis_id INT NOT NULL,
  client_id INT NOT NULL,
  receptionist_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  tax DECIMAL(10,2) DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL,
  status ENUM('unpaid','paid','cancelled') DEFAULT 'unpaid',
  payment_method ENUM('cash','mobile_money','bank_transfer','card') DEFAULT NULL,
  paid_at DATETIME DEFAULT NULL,
  issued_date DATE NOT NULL,
  due_date DATE DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  seen_by_client TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (diagnosis_id) REFERENCES diagnoses(id) ON DELETE CASCADE,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receptionist_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── vehicle_technician_assignments ───────────────────────────
CREATE TABLE IF NOT EXISTS vehicle_technician_assignments (
  vehicle_id INT PRIMARY KEY,
  technician_id INT DEFAULT NULL,
  assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
  FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── client_staff_assignments ──────────────────────────────────
CREATE TABLE IF NOT EXISTS client_staff_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  staff_id INT NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_client (client_id),
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── comments ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  diagnosis_id INT NOT NULL,
  client_id INT DEFAULT NULL,
  user_id INT DEFAULT NULL,
  rating INT DEFAULT NULL,
  comment TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (diagnosis_id) REFERENCES diagnoses(id) ON DELETE CASCADE,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── messages ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  sender_role VARCHAR(20) DEFAULT NULL,
  receiver_id INT NOT NULL,
  subject VARCHAR(255) DEFAULT NULL,
  body TEXT DEFAULT NULL,
  parent_id INT DEFAULT NULL,
  message TEXT DEFAULT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── client_appointments ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS client_appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  vehicle_id INT NOT NULL,
  staff_id INT DEFAULT NULL,
  appointment_date DATE NOT NULL,
  note TEXT DEFAULT NULL,
  status ENUM('pending','approved','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  seen_by_client TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
  FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── attendance ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  receptionist_id INT DEFAULT NULL,
  recorded_by INT DEFAULT NULL,
  clock_in DATETIME DEFAULT NULL,
  clock_out DATETIME DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (receptionist_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── activity_logs ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  action VARCHAR(100) NOT NULL,
  description TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── service_centers ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS service_centers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  address VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  email VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ── SEED DATA ─────────────────────────────────────────────────
SET @p = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

INSERT IGNORE INTO users (username, name, email, password, role, phone, address) VALUES
('admin',        'ATECAR Administrator',    'admin@vfdsms.rw',        @p, 'admin',        '+250788123456', 'KG 17 Ave, Gishushu, Kigali'),
('receptionist', 'Aline Uwase',             'receptionist@vfdsms.rw', @p, 'receptionist', '+250788234567', 'KN 5 Rd, Kigali'),
('staff1',  'Jean Paul Habimana',     'staff1@vfdsms.rw',  @p, 'staff', '+250788345678', 'Kimironko, Kigali'),
('staff2',  'Marie Claire Uwamahoro', 'staff2@vfdsms.rw',  @p, 'staff', '+250788456789', 'Remera, Kigali'),
('staff3',  'Emmanuel Nsengiyumva',   'staff3@vfdsms.rw',  @p, 'staff', '+250788567890', 'Kacyiru, Kigali'),
('staff4',  'Diane Mukandayisenga',   'staff4@vfdsms.rw',  @p, 'staff', '+250788678901', 'Gisozi, Kigali'),
('staff5',  'Patrick Kwizera',        'staff5@vfdsms.rw',  @p, 'staff', '+250788789012', 'Nyamirambo, Kigali'),
('staff6',  'Josiane Mukamana',       'staff6@vfdsms.rw',  @p, 'staff', '+250788890123', 'Kanombe, Kigali'),
('staff7',  'Eric Ndagijimana',       'staff7@vfdsms.rw',  @p, 'staff', '+250788901234', 'Kimihurura, Kigali'),
('staff8',  'Claudine Mukarugwiza',   'staff8@vfdsms.rw',  @p, 'staff', '+250788012345', 'Gatsata, Kigali'),
('staff9',  'Sylvestre Rukundo',      'staff9@vfdsms.rw',  @p, 'staff', '+250789123456', 'Jabana, Kigali'),
('staff10', 'Jeannette Iradukunda',   'staff10@vfdsms.rw', @p, 'staff', '+250789234567', 'Rusororo, Kigali'),
('staff11', 'Augustin Niyonzima',     'staff11@vfdsms.rw', @p, 'staff', '+250789345678', 'Kinyinya, Kigali'),
('staff12', 'Chantal Nyiransabimana', 'staff12@vfdsms.rw', @p, 'staff', '+250789456789', 'Muhima, Kigali'),
('staff13', 'Celestin Hakizimana',    'staff13@vfdsms.rw', @p, 'staff', '+250789567890', 'Nyarugenge, Kigali'),
('staff14', 'Beatha Mukashema',       'staff14@vfdsms.rw', @p, 'staff', '+250789678901', 'Biryogo, Kigali'),
('client1',   'Gatete Jean Bosco',          'client1@vfdsms.rw',   @p, 'client', '+250788111001', 'KG 123 St, Kimironko, Kigali'),
('client2',   'Mukamana Vestine',           'client2@vfdsms.rw',   @p, 'client', '+250788111002', 'KK 456 Ave, Niboye, Kigali'),
('client3',   'Habarurema Pierre',          'client3@vfdsms.rw',   @p, 'client', '+250788111003', 'NY 789 Rd, Nyamirambo, Kigali'),
('client4',   'Uwimbabazi Solange',         'client4@vfdsms.rw',   @p, 'client', '+250788111004', 'GS 321 St, Remera, Kigali'),
('client5',   'Ntawukuriryayo Damascene',   'client5@vfdsms.rw',   @p, 'client', '+250788111005', 'KC 654 Ave, Gahanga, Kigali'),
('client6',   'Mukarubuga Donatile',        'client6@vfdsms.rw',   @p, 'client', '+250788111006', 'NY 987 Rd, Muhima, Kigali'),
('client7',   'Ndayisaba Faustin',          'client7@vfdsms.rw',   @p, 'client', '+250788111007', 'GS 147 St, Kinyinya, Kigali'),
('client8',   'Mukankuranga Liberatha',     'client8@vfdsms.rw',   @p, 'client', '+250788111008', 'KC 258 Ave, Gatenga, Kigali'),
('client9',   'Nkurunziza Innocent',        'client9@vfdsms.rw',   @p, 'client', '+250788111009', 'NY 369 Rd, Kimisagara, Kigali'),
('client10',  'Uwanyirigira Pascasie',      'client10@vfdsms.rw',  @p, 'client', '+250788111010', 'GS 741 St, Jabana, Kigali'),
('client11',  'Nsengimana Jerome',          'client11@vfdsms.rw',  @p, 'client', '+250788111011', 'KC 852 Ave, Kagarama, Kigali'),
('client12',  'Mukasine Gaudence',          'client12@vfdsms.rw',  @p, 'client', '+250788111012', 'NY 963 Rd, Gitega, Kigali'),
('client13',  'Hakizimana Tharcisse',       'client13@vfdsms.rw',  @p, 'client', '+250788111013', 'GS 159 St, Rusororo, Kigali'),
('client14',  'Murekatete Claudette',       'client14@vfdsms.rw',  @p, 'client', '+250788111014', 'KC 357 Ave, Masaka, Kigali'),
('client15',  'Twagirayezu Elie',           'client15@vfdsms.rw',  @p, 'client', '+250788111015', 'NY 753 Rd, Kanyinya, Kigali'),
('client16',  'Nyirandimubanzi Jeanne',     'client16@vfdsms.rw',  @p, 'client', '+250788111016', 'GS 951 St, Gisozi, Kigali'),
('client17',  'Munyaneza Froduard',         'client17@vfdsms.rw',  @p, 'client', '+250788111017', 'KC 159 Ave, Kanombe, Kigali'),
('client18',  'Mukabahizi Speciose',        'client18@vfdsms.rw',  @p, 'client', '+250788111018', 'NY 357 Rd, Mageragere, Kigali'),
('client19',  'Ndahayo Emmanuel',           'client19@vfdsms.rw',  @p, 'client', '+250788111019', 'GS 258 St, Kimihurura, Kigali'),
('client20',  'Uwamariya Concessa',         'client20@vfdsms.rw',  @p, 'client', '+250788111020', 'KC 369 Ave, Nyanza, Kigali');


-- ── api_tokens ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS api_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  token VARCHAR(64) NOT NULL UNIQUE,
  label VARCHAR(100) NOT NULL,
  created_by INT NOT NULL,
  last_used DATETIME DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── obd_scans ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS obd_scans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT DEFAULT NULL,
  plate VARCHAR(20) DEFAULT NULL,
  scanner_token VARCHAR(64) DEFAULT NULL,
  raw_data JSON DEFAULT NULL,
  fault_codes TEXT DEFAULT NULL,
  vin VARCHAR(17) DEFAULT NULL,
  mileage INT DEFAULT NULL,
  protocol VARCHAR(30) DEFAULT NULL,
  scanner_id VARCHAR(100) DEFAULT NULL,
  status ENUM('pending','imported','ignored') DEFAULT 'pending',
  imported_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
  FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
