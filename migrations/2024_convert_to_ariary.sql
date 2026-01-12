-- Migration pour convertir tous les montants en Ariary (Ar)
-- Taux de change approximatif: 1 EUR = 4500 Ar (à ajuster selon le taux actuel)
-- Cette migration doit être exécutée avec précaution, de préférence après une sauvegarde complète de la base de données

-- Désactiver temporairement les contraintes de clé étrangère
SET FOREIGN_KEY_CHECKS = 0;

-- Mise à jour de la table room_types
UPDATE room_types SET base_price = ROUND(base_price * 4500);

-- Mise à jour de la table services
UPDATE services SET price = ROUND(price * 4500);

-- Mise à jour de la table reservations
UPDATE reservations SET total_price = ROUND(total_price * 4500);

-- Mise à jour de la table payments
UPDATE payments SET amount = ROUND(amount * 4500);

-- Mise à jour de la table reservation_services
UPDATE reservation_services SET total_price = ROUND(total_price * 4500);

-- Mise à jour de la structure des tables pour refléter la nouvelle échelle (suppression des décimales)
ALTER TABLE room_types MODIFY COLUMN base_price INT NOT NULL;
ALTER TABLE services MODIFY COLUMN price INT NOT NULL;
ALTER TABLE reservations MODIFY COLUMN total_price INT NOT NULL;
ALTER TABLE payments MODIFY COLUMN amount INT NOT NULL;
ALTER TABLE reservation_services MODIFY COLUMN total_price INT NOT NULL;

-- Réactiver les contraintes de clé étrangère
SET FOREIGN_KEY_CHECKS = 1;

-- Mise à jour de la fonction formatPrice dans config.php
-- Cette partie doit être effectuée manuellement dans le fichier config.php
-- Remplacer la fonction formatPrice par :
/*
function formatPrice($price) {
    return number_format($price, 0, ' ', ' ') . ' Ar';
}
*/

-- Mise à jour des exemples de données pour qu'ils reflètent des prix réalistes en Ariary
-- Cette partie est optionnelle et peut être ajustée selon les besoins
UPDATE room_types SET 
    base_price = CASE 
        WHEN id = 1 THEN 225000  -- Simple: 50€ * 4500
        WHEN id = 2 THEN 360000  -- Double: 80€ * 4500
        WHEN id = 3 THEN 382500  -- Twin: 85€ * 4500
        WHEN id = 4 THEN 675000  -- Suite Junior: 150€ * 4500
        WHEN id = 5 THEN 1350000 -- Suite Présidentielle: 300€ * 4500
    END;

UPDATE services SET 
    price = CASE 
        WHEN id = 1 THEN 67500   -- Petit déjeuner: 15€ * 4500
        WHEN id = 2 THEN 180000  -- Spa: 40€ * 4500
        WHEN id = 3 THEN 45000   -- Parking: 10€ * 4500
        WHEN id = 4 THEN 225000  -- Navette aéroport: 50€ * 4500
        WHEN id = 5 THEN 90000   -- Room service: 20€ * 4500
        WHEN id = 6 THEN 112500  -- Blanchisserie: 25€ * 4500
        WHEN id = 7 THEN 22500   -- Wifi Premium: 5€ * 4500
    END;

-- Mise à jour des réservations et paiements existants
-- Note: Ces mises à jour sont des exemples et doivent être adaptées aux données réelles
UPDATE reservations SET total_price = (
    SELECT 
        DATEDIFF(check_out_date, check_in_date) * 
        (SELECT base_price FROM room_types rt JOIN rooms r ON rt.id = r.room_type_id WHERE r.id = reservations.room_id)
);

-- Mise à jour des services de réservation
UPDATE reservation_services rs
JOIN services s ON rs.service_id = s.id
SET rs.total_price = s.price * rs.quantity;

-- Mise à jour des paiements pour qu'ils correspondent au total des réservations
UPDATE payments p
JOIN (
    SELECT reservation_id, SUM(amount) as total_paid
    FROM payments
    GROUP BY reservation_id
) as paid ON p.reservation_id = paid.reservation_id
JOIN reservations r ON p.reservation_id = r.id
SET p.amount = (
    SELECT LEAST(r.total_price, paid.total_paid)
    WHERE p.id = (
        SELECT id FROM payments 
        WHERE reservation_id = r.id 
        ORDER BY payment_date DESC 
        LIMIT 1
    )
);
