<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'Gestion des Chambres';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("
                        INSERT INTO rooms (room_number, room_type_id, floor, status, description)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        cleanInput($_POST['room_number']),
                        $_POST['room_type_id'],
                        $_POST['floor'],
                        $_POST['status'],
                        cleanInput($_POST['description'])
                    ]);
                    setSuccessMessage('Chambre ajoutée avec succès !');
                    break;
                    
                case 'edit':
                    $stmt = $pdo->prepare("
                        UPDATE rooms 
                        SET room_number = ?, room_type_id = ?, floor = ?, 
                            status = ?, description = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        cleanInput($_POST['room_number']),
                        $_POST['room_type_id'],
                        $_POST['floor'],
                        $_POST['status'],
                        cleanInput($_POST['description']),
                        $_POST['id']
                    ]);
                    setSuccessMessage('Chambre modifiée avec succès !');
                    break;
                    
                case 'delete':
                    // Vérifier si la chambre a des réservations actives
                    $checkStmt = $pdo->prepare("
                        SELECT COUNT(*) as count FROM reservations 
                        WHERE room_id = ? AND status IN ('confirmed', 'checked_in', 'pending')
                    ");
                    $checkStmt->execute([$_POST['id']]);
                    if ($checkStmt->fetch()['count'] > 0) {
                        setErrorMessage('Impossible de supprimer : cette chambre a des réservations actives');
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
                        $stmt->execute([$_POST['id']]);
                        setSuccessMessage('Chambre supprimée avec succès !');
                    }
                    break;
            }
            header('Location: rooms.php');
            exit;
        } catch (PDOException $e) {
            setErrorMessage('Erreur : ' . $e->getMessage());
        }
    }
}

// Récupération des types de chambres
$roomTypes = $pdo->query("SELECT * FROM room_types ORDER BY type_name")->fetchAll();

// Filtres
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Construction de la requête
$query = "
    SELECT 
        r.*,
        rt.type_name,
        rt.base_price,
        rt.capacity,
        (SELECT COUNT(*) FROM reservations 
         WHERE room_id = r.id AND status IN ('confirmed', 'checked_in')) as active_reservations
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE 1=1
";

$params = [];

if ($statusFilter) {
    $query .= " AND r.status = ?";
    $params[] = $statusFilter;
}

if ($typeFilter) {
    $query .= " AND r.room_type_id = ?";
    $params[] = $typeFilter;
}

if ($searchTerm) {
    $query .= " AND (r.room_number LIKE ? OR rt.type_name LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

$query .= " ORDER BY r.room_number";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-bed"></i> Liste des Chambres (<?php echo count($rooms); ?>)</h2>
        <button class="btn btn-primary" onclick="openModal('addRoomModal')">
            <i class="fas fa-plus"></i> Nouvelle Chambre
        </button>
    </div>
    
    <!-- Filtres -->
    <form method="GET" class="filters">
        <div class="form-row">
            <div class="form-group search-box">
                <input type="text" 
                       name="search" 
                       class="form-control" 
                       placeholder="Rechercher par numéro ou type..."
                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                <i class="fas fa-search"></i>
            </div>
            
            <div class="form-group">
                <select name="type" class="form-control">
                    <option value="">Tous les types</option>
                    <?php foreach ($roomTypes as $type): ?>
                        <option value="<?php echo $type['id']; ?>" 
                                <?php echo $typeFilter == $type['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['type_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <select name="status" class="form-control">
                    <option value="">Tous les statuts</option>
                    <option value="available" <?php echo $statusFilter === 'available' ? 'selected' : ''; ?>>Disponible</option>
                    <option value="occupied" <?php echo $statusFilter === 'occupied' ? 'selected' : ''; ?>>Occupée</option>
                    <option value="maintenance" <?php echo $statusFilter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="cleaning" <?php echo $statusFilter === 'cleaning' ? 'selected' : ''; ?>>Nettoyage</option>
                </select>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
                <a href="rooms.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Réinitialiser
                </a>
            </div>
        </div>
    </form>
    
    <div class="table-container">
        <table id="roomsTable">
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Type</th>
                    <th>Capacité</th>
                    <th>Étage</th>
                    <th>Prix/Nuit</th>
                    <th>Statut</th>
                    <th>Réservations</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rooms)): ?>
                <tr>
                    <td colspan="8" class="text-center">Aucune chambre trouvée</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                        <td><?php echo $room['capacity']; ?> pers.</td>
                        <td>Étage <?php echo $room['floor']; ?></td>
                        <td><strong><?php echo formatPrice($room['base_price']); ?></strong></td>
                        <td>
                            <?php
                            $statusClass = '';
                            $statusLabel = '';
                            switch ($room['status']) {
                                case 'available':
                                    $statusClass = 'badge-success';
                                    $statusLabel = 'Disponible';
                                    break;
                                case 'occupied':
                                    $statusClass = 'badge-danger';
                                    $statusLabel = 'Occupée';
                                    break;
                                case 'maintenance':
                                    $statusClass = 'badge-warning';
                                    $statusLabel = 'Maintenance';
                                    break;
                                case 'cleaning':
                                    $statusClass = 'badge-info';
                                    $statusLabel = 'Nettoyage';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                        </td>
                        <td>
                            <?php if ($room['active_reservations'] > 0): ?>
                                <span class="badge badge-info"><?php echo $room['active_reservations']; ?> active(s)</span>
                            <?php else: ?>
                                <span class="text-muted">Aucune</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <button onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)" 
                                        class="action-btn action-btn-edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirmDelete('Êtes-vous sûr de vouloir supprimer cette chambre ?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
                                    <button type="submit" class="action-btn action-btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajout Chambre -->
<div id="addRoomModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus"></i> Nouvelle Chambre</h2>
            <button class="close-modal" onclick="closeModal('addRoomModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="room_number" class="required">Numéro de chambre</label>
                    <input type="text" id="room_number" name="room_number" 
                           class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="floor" class="required">Étage</label>
                    <input type="number" id="floor" name="floor" 
                           class="form-control" min="0" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="room_type_id" class="required">Type de chambre</label>
                    <select id="room_type_id" name="room_type_id" class="form-control" required>
                        <option value="">Sélectionner...</option>
                        <?php foreach ($roomTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>">
                                <?php echo htmlspecialchars($type['type_name']); ?> 
                                (<?php echo formatPrice($type['base_price']); ?>/nuit)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status" class="required">Statut</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="available">Disponible</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="cleaning">Nettoyage</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" 
                          class="form-control" rows="3"></textarea>
            </div>
            
            <div class="btn-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addRoomModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Édition Chambre -->
<div id="editRoomModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Modifier la Chambre</h2>
            <button class="close-modal" onclick="closeModal('editRoomModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" id="editRoomForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_room_number" class="required">Numéro de chambre</label>
                    <input type="text" id="edit_room_number" name="room_number" 
                           class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_floor" class="required">Étage</label>
                    <input type="number" id="edit_floor" name="floor" 
                           class="form-control" min="0" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_room_type_id" class="required">Type de chambre</label>
                    <select id="edit_room_type_id" name="room_type_id" class="form-control" required>
                        <?php foreach ($roomTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>">
                                <?php echo htmlspecialchars($type['type_name']); ?> 
                                (<?php echo formatPrice($type['base_price']); ?>/nuit)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_status" class="required">Statut</label>
                    <select id="edit_status" name="status" class="form-control" required>
                        <option value="available">Disponible</option>
                        <option value="occupied">Occupée</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="cleaning">Nettoyage</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" 
                          class="form-control" rows="3"></textarea>
            </div>
            
            <div class="btn-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editRoomModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editRoom(room) {
    document.getElementById('edit_id').value = room.id;
    document.getElementById('edit_room_number').value = room.room_number;
    document.getElementById('edit_floor').value = room.floor;
    document.getElementById('edit_room_type_id').value = room.room_type_id;
    document.getElementById('edit_status').value = room.status;
    document.getElementById('edit_description').value = room.description || '';
    openModal('editRoomModal');
}
</script>

<?php include 'includes/footer.php'; ?>
