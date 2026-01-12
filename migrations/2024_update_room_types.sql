-- Migration to update room types

-- First, remove existing rooms that reference old room types
DELETE FROM rooms;

-- Then remove old room types
DELETE FROM room_types;

-- Insert new room types
INSERT INTO room_types (type_name, description, base_price, capacity) VALUES
('Chambre Simple', 'Chambre simple avec lit simple, salle de bain partagée', 25000.00, 1),
('Chambre Double', 'Chambre avec un grand lit, salle de bain partagée', 40000.00, 2),
('Chambre avec eau chaude', 'Chambre simple avec eau chaude, salle de bain privée', 35000.00, 1),
('Chambre eau chaude wifi', 'Chambre avec eau chaude et accès wifi, salle de bain privée', 45000.00, 2),
('Chambre avec cuisine eau chaude wifi', 'Chambre avec kitchenette, eau chaude et wifi, salle de bain privée', 60000.00, 2);

-- Insert some sample rooms for each type
INSERT INTO rooms (room_number, room_type_id, floor, status, description) VALUES
-- Chambres Simples
('101', 1, 1, 'available', 'Chambre simple - Rez-de-chaussée'),
('102', 1, 1, 'available', 'Chambre simple - Rez-de-chaussée'),

-- Chambres Doubles
('201', 2, 2, 'available', 'Chambre double - 1er étage'),
('202', 2, 2, 'available', 'Chambre double - 1er étage'),

-- Chambres avec eau chaude
('301', 3, 3, 'available', 'Chambre avec eau chaude - 2ème étage'),
('302', 3, 3, 'available', 'Chambre avec eau chaude - 2ème étage'),

-- Chambres eau chaude wifi
('401', 4, 4, 'available', 'Chambre eau chaude wifi - 3ème étage'),
('402', 4, 4, 'available', 'Chambre eau chaude wifi - 3ème étage'),

-- Chambres avec cuisine eau chaude wifi
('501', 5, 5, 'available', 'Chambre avec cuisine eau chaude wifi - Dernier étage'),
('502', 5, 5, 'available', 'Chambre avec cuisine eau chaude wifi - Dernier étage');
