-- =====================================================
-- Base de Données : Système de Gestion d'Hôtel
-- =====================================================

CREATE DATABASE IF NOT EXISTS hotel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_management;

-- Table des utilisateurs (administrateurs)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'receptionist') DEFAULT 'receptionist',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des types de chambres
CREATE TABLE IF NOT EXISTS room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    description TEXT,
    base_price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des chambres
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    room_type_id INT NOT NULL,
    floor INT NOT NULL,
    status ENUM('available', 'occupied', 'maintenance', 'cleaning') DEFAULT 'available',
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des clients
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    id_number VARCHAR(50),
    nationality VARCHAR(50),
    date_of_birth DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des réservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    number_of_guests INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'partial', 'paid', 'refunded') DEFAULT 'unpaid',
    special_requests TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_dates (check_in_date, check_out_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des paiements
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'online') NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(100),
    notes TEXT,
    created_by INT,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des services supplémentaires
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des services de réservation
CREATE TABLE IF NOT EXISTS reservation_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    service_id INT NOT NULL,
    quantity INT DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertion des données par défaut

-- Utilisateur admin par défaut (mot de passe: admin123)
INSERT INTO users (username, password, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@hotel.com', 'Administrateur Principal', 'admin'),
('receptionist', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reception@hotel.com', 'Réceptionniste', 'receptionist');

-- Types de chambres
INSERT INTO room_types (type_name, description, base_price, capacity) VALUES
('Simple', 'Chambre simple avec un lit simple, idéale pour une personne', 50.00, 1),
('Double', 'Chambre double avec un grand lit, parfaite pour les couples', 80.00, 2),
('Twin', 'Chambre avec deux lits simples', 85.00, 2),
('Suite Junior', 'Suite spacieuse avec salon séparé', 150.00, 3),
('Suite Présidentielle', 'Suite de luxe avec toutes les commodités', 300.00, 4);

-- Chambres
INSERT INTO rooms (room_number, room_type_id, floor, status, description) VALUES
('101', 1, 1, 'available', 'Chambre simple au rez-de-chaussée'),
('102', 2, 1, 'available', 'Chambre double avec vue sur jardin'),
('103', 2, 1, 'available', 'Chambre double standard'),
('201', 3, 2, 'available', 'Chambre twin au 1er étage'),
('202', 2, 2, 'available', 'Chambre double avec balcon'),
('203', 4, 2, 'available', 'Suite junior avec salon'),
('301', 4, 3, 'available', 'Suite junior vue panoramique'),
('302', 5, 3, 'available', 'Suite présidentielle de luxe'),
('303', 2, 3, 'occupied', 'Chambre double occupée'),
('401', 1, 4, 'maintenance', 'Chambre en maintenance');

-- Services supplémentaires
INSERT INTO services (service_name, description, price) VALUES
('Petit déjeuner', 'Buffet petit déjeuner continental', 15.00),
('Spa', 'Accès au spa et sauna (1h)', 40.00),
('Parking', 'Place de parking sécurisée (par jour)', 10.00),
('Navette aéroport', 'Service de navette aller-retour', 50.00),
('Room service', 'Service en chambre 24h/24', 20.00),
('Blanchisserie', 'Service de blanchisserie express', 25.00),
('Wifi Premium', 'Connexion internet haute vitesse', 5.00);

-- Clients exemples
INSERT INTO clients (first_name, last_name, email, phone, address, id_number, nationality, date_of_birth) VALUES
('Jean', 'Dupont', 'jean.dupont@email.com', '0612345678', '123 Rue de Paris, 75001 Paris', 'AB123456', 'Française', '1985-03-15'),
('Marie', 'Martin', 'marie.martin@email.com', '0623456789', '456 Avenue Victor Hugo, 69000 Lyon', 'CD789012', 'Française', '1990-07-22'),
('Pierre', 'Bernard', 'pierre.bernard@email.com', '0634567890', '789 Boulevard Haussmann, 75008 Paris', 'EF345678', 'Française', '1978-11-30'),
('Sophie', 'Dubois', 'sophie.dubois@email.com', '0645678901', '321 Rue de la République, 13000 Marseille', 'GH901234', 'Française', '1992-05-18'),
('Luc', 'Moreau', 'luc.moreau@email.com', '0656789012', '654 Avenue des Champs, 33000 Bordeaux', 'IJ567890', 'Française', '1988-09-25');

-- Réservations exemples
INSERT INTO reservations (client_id, room_id, check_in_date, check_out_date, number_of_guests, total_price, status, payment_status, created_by) VALUES
(1, 2, '2026-01-15', '2026-01-18', 2, 240.00, 'confirmed', 'paid', 1),
(2, 5, '2026-01-20', '2026-01-25', 2, 400.00, 'pending', 'unpaid', 1),
(3, 6, '2026-01-12', '2026-01-16', 3, 600.00, 'checked_in', 'partial', 1),
(4, 9, '2026-01-10', '2026-01-14', 2, 320.00, 'checked_in', 'paid', 2),
(5, 7, '2026-02-01', '2026-02-05', 2, 600.00, 'confirmed', 'unpaid', 1);

-- Paiements exemples
INSERT INTO payments (reservation_id, amount, payment_method, transaction_id, created_by) VALUES
(1, 240.00, 'card', 'TXN001234567', 1),
(3, 300.00, 'cash', NULL, 1),
(4, 320.00, 'bank_transfer', 'TXN001234568', 2);

-- Services de réservation exemples
INSERT INTO reservation_services (reservation_id, service_id, quantity, total_price) VALUES
(1, 1, 6, 90.00),
(3, 1, 12, 180.00),
(3, 3, 4, 40.00),
(4, 1, 8, 120.00),
(4, 4, 1, 50.00);

-- Vues utiles pour les statistiques

CREATE VIEW v_available_rooms AS
SELECT 
    r.id,
    r.room_number,
    rt.type_name,
    rt.base_price,
    rt.capacity,
    r.floor,
    r.status
FROM rooms r
JOIN room_types rt ON r.room_type_id = rt.id
WHERE r.status = 'available';

CREATE VIEW v_reservation_details AS
SELECT 
    res.id AS reservation_id,
    res.check_in_date,
    res.check_out_date,
    res.number_of_guests,
    res.total_price,
    res.status AS reservation_status,
    res.payment_status,
    CONCAT(c.first_name, ' ', c.last_name) AS client_name,
    c.phone AS client_phone,
    c.email AS client_email,
    r.room_number,
    rt.type_name AS room_type,
    DATEDIFF(res.check_out_date, res.check_in_date) AS nights,
    res.created_at
FROM reservations res
JOIN clients c ON res.client_id = c.id
JOIN rooms r ON res.room_id = r.id
JOIN room_types rt ON r.room_type_id = rt.id;

CREATE VIEW v_revenue_summary AS
SELECT 
    DATE_FORMAT(p.payment_date, '%Y-%m') AS month,
    COUNT(DISTINCT p.reservation_id) AS total_reservations,
    SUM(p.amount) AS total_revenue,
    AVG(p.amount) AS average_payment
FROM payments p
GROUP BY DATE_FORMAT(p.payment_date, '%Y-%m')
ORDER BY month DESC;

-- Index pour optimiser les performances
CREATE INDEX idx_room_status ON rooms(status);
CREATE INDEX idx_reservation_dates ON reservations(check_in_date, check_out_date);
CREATE INDEX idx_client_name ON clients(last_name, first_name);
CREATE INDEX idx_payment_date ON payments(payment_date);

-- Procédure stockée pour vérifier la disponibilité d'une chambre
DELIMITER //

CREATE PROCEDURE check_room_availability(
    IN p_room_id INT,
    IN p_check_in DATE,
    IN p_check_out DATE,
    OUT p_is_available BOOLEAN
)
BEGIN
    DECLARE conflict_count INT;
    
    SELECT COUNT(*) INTO conflict_count
    FROM reservations
    WHERE room_id = p_room_id
    AND status NOT IN ('cancelled', 'checked_out')
    AND (
        (check_in_date <= p_check_in AND check_out_date > p_check_in)
        OR (check_in_date < p_check_out AND check_out_date >= p_check_out)
        OR (check_in_date >= p_check_in AND check_out_date <= p_check_out)
    );
    
    SET p_is_available = (conflict_count = 0);
END //

DELIMITER ;

-- Trigger pour mettre à jour le statut de la chambre
DELIMITER //

CREATE TRIGGER update_room_status_after_reservation
AFTER UPDATE ON reservations
FOR EACH ROW
BEGIN
    IF NEW.status = 'checked_in' THEN
        UPDATE rooms SET status = 'occupied' WHERE id = NEW.room_id;
    ELSEIF NEW.status = 'checked_out' OR NEW.status = 'cancelled' THEN
        UPDATE rooms SET status = 'cleaning' WHERE id = NEW.room_id;
    END IF;
END //

DELIMITER ;

-- =====================================================
-- Fin de la création de la base de données
-- =====================================================
