-- Supprimer la contrainte de clé étrangère existante
ALTER TABLE reservations DROP FOREIGN KEY reservations_ibfk_3;

-- Recréer la contrainte avec ON DELETE SET NULL et ON UPDATE CASCADE
ALTER TABLE reservations 
ADD CONSTRAINT fk_reservations_created_by 
FOREIGN KEY (created_by) 
REFERENCES users(id) 
ON DELETE SET NULL 
ON UPDATE CASCADE;
