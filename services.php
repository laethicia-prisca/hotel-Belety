<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'Gestion des Services';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("
                        INSERT INTO services (service_name, description, price, is_active)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        cleanInput($_POST['service_name']),
                        cleanInput($_POST['description']),
                        $_POST['price'],
                        isset($_POST['is_active']) ? 1 : 0
                    ]);
                    setSuccessMessage('Service ajouté avec succès !');
                    break;
                    
                case 'edit':
                    $stmt = $pdo->prepare("
                        UPDATE services 
                        SET service_name = ?, description = ?, price = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        cleanInput($_POST['service_name']),
                        cleanInput($_POST['description']),
                        $_POST['price'],
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['id']
                    ]);
                    setSuccessMessage('Service modifié avec succès !');
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    setSuccessMessage('Service supprimé avec succès !');
                    break;
            }
            header('Location: services.php');
            exit;
        } catch (PDOException $e) {
            setErrorMessage('Erreur : ' . $e->getMessage());
        }
    }
}

// Récupération des services
$services = $pdo->query("SELECT * FROM services ORDER BY service_name")->fetchAll();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-concierge-bell"></i> Liste des Services (<?php echo count($services); ?>)</h2>
        <button class="btn btn-primary" onclick="openModal('addServiceModal')">
            <i class="fas fa-plus"></i> Nouveau Service
        </button>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nom du service</th>
                    <th>Description</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                <tr>
                    <td colspan="5" class="text-center">Aucun service trouvé</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($service['service_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($service['description'] ?: '-'); ?></td>
                        <td><strong><?php echo formatPrice($service['price']); ?></strong></td>
                        <td>
                            <?php if ($service['is_active']): ?>
                                <span class="badge badge-success">Actif</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <button onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)" 
                                        class="action-btn action-btn-edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirmDelete('Êtes-vous sûr de vouloir supprimer ce service ?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
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

<!-- Modal Ajout Service -->
<div id="addServiceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus"></i> Nouveau Service</h2>
            <button class="close-modal" onclick="closeModal('addServiceModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="service_name" class="required">Nom du service</label>
                <input type="text" id="service_name" name="service_name" 
                       class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" 
                          class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="price" class="required">Prix (Ar)</label>
                <input type="number" id="price" name="price" 
                       class="form-control" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" checked>
                    <span>Service actif</span>
                </label>
            </div>
            
            <div class="btn-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addServiceModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Édition Service -->
<div id="editServiceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Modifier le Service</h2>
            <button class="close-modal" onclick="closeModal('editServiceModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label for="edit_service_name" class="required">Nom du service</label>
                <input type="text" id="edit_service_name" name="service_name" 
                       class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" 
                          class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="edit_price" class="required">Prix (Ar)</label>
                <input type="number" id="edit_price" name="price" 
                       class="form-control" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                    <span>Service actif</span>
                </label>
            </div>
            
            <div class="btn-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editServiceModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editService(service) {
    document.getElementById('edit_id').value = service.id;
    document.getElementById('edit_service_name').value = service.service_name;
    document.getElementById('edit_description').value = service.description || '';
    document.getElementById('edit_price').value = service.price;
    document.getElementById('edit_is_active').checked = service.is_active == 1;
    openModal('editServiceModal');
}
</script>

<?php include 'includes/footer.php'; ?>
