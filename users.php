<?php
require_once 'includes/config.php';
requireAdmin(); // Seuls les admins peuvent gérer les utilisateurs

$page_title = 'Gestion des Utilisateurs';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, password, email, full_name, role)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        cleanInput($_POST['username']),
                        hashPassword($_POST['password']),
                        cleanInput($_POST['email']),
                        cleanInput($_POST['full_name']),
                        $_POST['role']
                    ]);
                    setSuccessMessage('Utilisateur créé avec succès !');
                    break;
                    
                case 'edit':
                    if (!empty($_POST['password'])) {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET username = ?, password = ?, email = ?, full_name = ?, role = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            cleanInput($_POST['username']),
                            hashPassword($_POST['password']),
                            cleanInput($_POST['email']),
                            cleanInput($_POST['full_name']),
                            $_POST['role'],
                            $_POST['id']
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET username = ?, email = ?, full_name = ?, role = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            cleanInput($_POST['username']),
                            cleanInput($_POST['email']),
                            cleanInput($_POST['full_name']),
                            $_POST['role'],
                            $_POST['id']
                        ]);
                    }
                    setSuccessMessage('Utilisateur modifié avec succès !');
                    break;
                    
                case 'delete':
                    if ($_POST['id'] == $_SESSION['user_id']) {
                        setErrorMessage('Vous ne pouvez pas supprimer votre propre compte !');
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$_POST['id']]);
                        setSuccessMessage('Utilisateur supprimé avec succès !');
                    }
                    break;
            }
            header('Location: users.php');
            exit;
        } catch (PDOException $e) {
            setErrorMessage('Erreur : ' . $e->getMessage());
        }
    }
}

// Récupération des utilisateurs
$users = $pdo->query("SELECT * FROM users ORDER BY full_name")->fetchAll();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-shield"></i> Liste des Utilisateurs (<?php echo count($users); ?>)</h2>
        <button class="btn btn-primary" onclick="openModal('addUserModal')">
            <i class="fas fa-user-plus"></i> Nouvel Utilisateur
        </button>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nom complet</th>
                    <th>Nom d'utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Dernière connexion</th>
                    <th>Date de création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="text-center">Aucun utilisateur trouvé</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="badge badge-danger">
                                    <i class="fas fa-user-shield"></i> Administrateur
                                </span>
                            <?php else: ?>
                                <span class="badge badge-info">
                                    <i class="fas fa-user"></i> Réceptionniste
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $user['last_login'] ? formatDateTime($user['last_login']) : '<span class="text-muted">Jamais</span>'; ?>
                        </td>
                        <td><?php echo formatDate($user['created_at']); ?></td>
                        <td>
                            <div class="actions">
                                <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                        class="action-btn action-btn-edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirmDelete('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="action-btn action-btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajout Utilisateur -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> Nouvel Utilisateur</h2>
            <button class="close-modal" onclick="closeModal('addUserModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="full_name" class="required">Nom complet</label>
                <input type="text" id="full_name" name="full_name" 
                       class="form-control" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username" class="required">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" 
                           class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="required">Email</label>
                    <input type="email" id="email" name="email" 
                           class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="required">Mot de passe</label>
                    <input type="password" id="password" name="password" 
                           class="form-control" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="role" class="required">Rôle</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="receptionist">Réceptionniste</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
            </div>
            
            <div class="btn-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Édition Utilisateur -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-edit"></i> Modifier l'Utilisateur</h2>
            <button class="close-modal" onclick="closeModal('editUserModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label for="edit_full_name" class="required">Nom complet</label>
                <input type="text" id="edit_full_name" name="full_name" 
                       class="form-control" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_username" class="required">Nom d'utilisateur</label>
                    <input type="text" id="edit_username" name="username" 
                           class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email" class="required">Email</label>
                    <input type="email" id="edit_email" name="email" 
                           class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_password">Nouveau mot de passe</label>
                    <input type="password" id="edit_password" name="password" 
                           class="form-control" minlength="6"
                           placeholder="Laisser vide pour ne pas changer">
                </div>
                
                <div class="form-group">
                    <label for="edit_role" class="required">Rôle</label>
                    <select id="edit_role" name="role" class="form-control" required>
                        <option value="receptionist">Réceptionniste</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
            </div>
            
            <div class="btn-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_password').value = '';
    openModal('editUserModal');
}
</script>

<?php include 'includes/footer.php'; ?>
