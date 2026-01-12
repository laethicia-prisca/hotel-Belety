<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'Gestion des Clients';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("
                        INSERT INTO clients (first_name, last_name, email, phone, address, 
                                           id_number, nationality, date_of_birth)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        cleanInput($_POST['first_name']),
                        cleanInput($_POST['last_name']),
                        cleanInput($_POST['email']),
                        cleanInput($_POST['phone']),
                        cleanInput($_POST['address']),
                        cleanInput($_POST['id_number']),
                        cleanInput($_POST['nationality']),
                        $_POST['date_of_birth'] ?: null
                    ]);
                    setSuccessMessage('Client ajouté avec succès !');
                    break;
                    
                case 'edit':
                    $stmt = $pdo->prepare("
                        UPDATE clients 
                        SET first_name = ?, last_name = ?, email = ?, phone = ?, 
                            address = ?, id_number = ?, nationality = ?, date_of_birth = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        cleanInput($_POST['first_name']),
                        cleanInput($_POST['last_name']),
                        cleanInput($_POST['email']),
                        cleanInput($_POST['phone']),
                        cleanInput($_POST['address']),
                        cleanInput($_POST['id_number']),
                        cleanInput($_POST['nationality']),
                        $_POST['date_of_birth'] ?: null,
                        $_POST['id']
                    ]);
                    setSuccessMessage('Client modifié avec succès !');
                    break;
                    
                case 'delete':
                    // Vérifier si le client a des réservations
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE client_id = ?");
                    $checkStmt->execute([$_POST['id']]);
                    if ($checkStmt->fetch()['count'] > 0) {
                        setErrorMessage('Impossible de supprimer : ce client a des réservations');
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
                        $stmt->execute([$_POST['id']]);
                        setSuccessMessage('Client supprimé avec succès !');
                    }
                    break;
            }
            header('Location: clients.php');
            exit;
        } catch (PDOException $e) {
            setErrorMessage('Erreur : ' . $e->getMessage());
        }
    }
}

// Filtres et recherche
$searchTerm = $_GET['search'] ?? '';

$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM reservations WHERE client_id = c.id) as total_reservations,
          (SELECT COUNT(*) FROM reservations WHERE client_id = c.id AND status IN ('confirmed', 'checked_in')) as active_reservations
          FROM clients c WHERE 1=1";

$params = [];

if ($searchTerm) {
    $query .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $search = "%$searchTerm%";
    $params = [$search, $search, $search, $search];
}

$query .= " ORDER BY c.last_name, c.first_name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$clients = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-users"></i> Liste des Clients (<?php echo count($clients); ?>)</h2>
        <button class="btn btn-primary" onclick="openModal('addClientModal')">
            <i class="fas fa-user-plus"></i> Nouveau Client
        </button>
    </div>
    
    <!-- Recherche -->
    <form method="GET" class="filters">
        <div class="form-row">
            <div class="form-group search-box">
                <input type="text" 
                       name="search" 
                       class="form-control" 
                       placeholder="Rechercher par nom, email ou téléphone..."
                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                <i class="fas fa-search"></i>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Rechercher
                </button>
                <a href="clients.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Réinitialiser
                </a>
            </div>
        </div>
    </form>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nom Complet</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Nationalité</th>
                    <th>N° Pièce d'identité</th>
                    <th>Réservations</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                <tr>
                    <td colspan="7" class="text-center">Aucun client trouvé</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($client['email'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($client['phone']); ?></td>
                        <td><?php echo htmlspecialchars($client['nationality'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($client['id_number'] ?: '-'); ?></td>
                        <td>
                            <?php if ($client['total_reservations'] > 0): ?>
                                <span class="badge badge-info">
                                    <?php echo $client['total_reservations']; ?> total
                                </span>
                                <?php if ($client['active_reservations'] > 0): ?>
                                    <span class="badge badge-success">
                                        <?php echo $client['active_reservations']; ?> active(s)
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Aucune</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <button onclick="viewClient(<?php echo htmlspecialchars(json_encode($client)); ?>)" 
                                        class="action-btn action-btn-view">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="editClient(<?php echo htmlspecialchars(json_encode($client)); ?>)" 
                                        class="action-btn action-btn-edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirmDelete('Êtes-vous sûr de vouloir supprimer ce client ?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $client['id']; ?>">
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

<!-- Modal Ajout Client -->
<div id="addClientModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> Nouveau Client</h2>
            <button class="close-modal" onclick="closeModal('addClientModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="required">Prénom</label>
                    <input type="text" id="first_name" name="first_name" 
                           class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name" class="required">Nom</label>
                    <input type="text" id="last_name" name="last_name" 
                           class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="phone" class="required">Téléphone</label>
                    <input type="tel" id="phone" name="phone" 
                           class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="id_number">N° Pièce d'identité</label>
                    <input type="text" id="id_number" name="id_number" 
                           class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="nationality">Nationalité</label>
                    <input type="text" id="nationality" name="nationality" 
                           class="form-control">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="date_of_birth">Date de naissance</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" 
                           class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Adresse</label>
                <textarea id="address" name="address" 
                          class="form-control" rows="3"></textarea>
            </div>
            
            <div class="btn-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addClientModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Édition Client -->
<div id="editClientModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-edit"></i> Modifier le Client</h2>
            <button class="close-modal" onclick="closeModal('editClientModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_first_name" class="required">Prénom</label>
                    <input type="text" id="edit_first_name" name="first_name" 
                           class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_last_name" class="required">Nom</label>
                    <input type="text" id="edit_last_name" name="last_name" 
                           class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" 
                           class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="edit_phone" class="required">Téléphone</label>
                    <input type="tel" id="edit_phone" name="phone" 
                           class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_id_number">N° Pièce d'identité</label>
                    <input type="text" id="edit_id_number" name="id_number" 
                           class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="edit_nationality">Nationalité</label>
                    <input type="text" id="edit_nationality" name="nationality" 
                           class="form-control">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_date_of_birth">Date de naissance</label>
                    <input type="date" id="edit_date_of_birth" name="date_of_birth" 
                           class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_address">Adresse</label>
                <textarea id="edit_address" name="address" 
                          class="form-control" rows="3"></textarea>
            </div>
            
            <div class="btn-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editClientModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Voir Client -->
<div id="viewClientModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user"></i> Détails du Client</h2>
            <button class="close-modal" onclick="closeModal('viewClientModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="clientDetails"></div>
    </div>
</div>

<script>
function editClient(client) {
    document.getElementById('edit_id').value = client.id;
    document.getElementById('edit_first_name').value = client.first_name;
    document.getElementById('edit_last_name').value = client.last_name;
    document.getElementById('edit_email').value = client.email || '';
    document.getElementById('edit_phone').value = client.phone;
    document.getElementById('edit_id_number').value = client.id_number || '';
    document.getElementById('edit_nationality').value = client.nationality || '';
    document.getElementById('edit_date_of_birth').value = client.date_of_birth || '';
    document.getElementById('edit_address').value = client.address || '';
    openModal('editClientModal');
}

function viewClient(client) {
    const dob = client.date_of_birth ? new Date(client.date_of_birth).toLocaleDateString('fr-FR') : '-';
    const html = `
        <div style="padding: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <p><strong>Nom complet:</strong><br>${client.first_name} ${client.last_name}</p>
                </div>
                <div>
                    <p><strong>Email:</strong><br>${client.email || '-'}</p>
                </div>
                <div>
                    <p><strong>Téléphone:</strong><br>${client.phone}</p>
                </div>
                <div>
                    <p><strong>Nationalité:</strong><br>${client.nationality || '-'}</p>
                </div>
                <div>
                    <p><strong>N° Pièce d'identité:</strong><br>${client.id_number || '-'}</p>
                </div>
                <div>
                    <p><strong>Date de naissance:</strong><br>${dob}</p>
                </div>
                <div style="grid-column: 1 / -1;">
                    <p><strong>Adresse:</strong><br>${client.address || '-'}</p>
                </div>
                <div>
                    <p><strong>Total réservations:</strong><br>
                        <span class="badge badge-info">${client.total_reservations}</span>
                    </p>
                </div>
                <div>
                    <p><strong>Réservations actives:</strong><br>
                        <span class="badge badge-success">${client.active_reservations}</span>
                    </p>
                </div>
            </div>
            <div style="margin-top: 20px; text-align: center;">
                <a href="reservations.php?client=${client.id}" class="btn btn-primary">
                    <i class="fas fa-calendar-check"></i> Voir les réservations
                </a>
            </div>
        </div>
    `;
    document.getElementById('clientDetails').innerHTML = html;
    openModal('viewClientModal');
}
</script>

<?php include 'includes/footer.php'; ?>
