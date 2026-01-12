<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'Gestion des Réservations';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    // Vérification que l'utilisateur est connecté
                    if (!isset($_SESSION['user_id'])) {
                        setErrorMessage('Vous devez être connecté pour effectuer cette action');
                        header('Location: login.php');
                        exit;
                    }
                    
                    // Vérifier que l'utilisateur existe dans la base de données
                    $userCheck = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                    $userCheck->execute([$_SESSION['user_id']]);
                    if (!$userCheck->fetch()) {
                        // L'utilisateur n'existe pas dans la base de données
                        setErrorMessage('Erreur : Utilisateur non valide');
                        header('Location: logout.php'); // Déconnexion car l'utilisateur n'est pas valide
                        exit;
                    }

                    // Vérification de la disponibilité
                    $checkStmt = $pdo->prepare("
                        SELECT COUNT(*) as count FROM reservations 
                        WHERE room_id = ? 
                        AND status NOT IN ('cancelled', 'checked_out')
                        AND ((check_in_date <= ? AND check_out_date > ?) 
                             OR (check_in_date < ? AND check_out_date >= ?)
                             OR (check_in_date >= ? AND check_out_date <= ?))
                    ");
                    $checkStmt->execute([
                        $_POST['room_id'],
                        $_POST['check_in_date'], $_POST['check_in_date'],
                        $_POST['check_out_date'], $_POST['check_out_date'],
                        $_POST['check_in_date'], $_POST['check_out_date']
                    ]);
                    
                    if ($checkStmt->fetch()['count'] > 0) {
                        setErrorMessage('Cette chambre n\'est pas disponible pour ces dates');
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO reservations (client_id, room_id, check_in_date, check_out_date,
                                                     number_of_guests, total_price, status, payment_status,
                                                     special_requests, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $_POST['client_id'],
                            $_POST['room_id'],
                            $_POST['check_in_date'],
                            $_POST['check_out_date'],
                            $_POST['number_of_guests'],
                            $_POST['total_price'],
                            $_POST['status'],
                            $_POST['payment_status'],
                            cleanInput($_POST['special_requests']),
                            (int)$_SESSION['user_id'] // S'assurer que c'est un entier
                        ]);
                        setSuccessMessage('Réservation créée avec succès !');
                    }
                    break;
                    
                case 'edit':
                    $stmt = $pdo->prepare("
                        UPDATE reservations 
                        SET client_id = ?, room_id = ?, check_in_date = ?, check_out_date = ?,
                            number_of_guests = ?, total_price = ?, status = ?, payment_status = ?,
                            special_requests = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['client_id'],
                        $_POST['room_id'],
                        $_POST['check_in_date'],
                        $_POST['check_out_date'],
                        $_POST['number_of_guests'],
                        $_POST['total_price'],
                        $_POST['status'],
                        $_POST['payment_status'],
                        cleanInput($_POST['special_requests']),
                        $_POST['id']
                    ]);
                    setSuccessMessage('Réservation modifiée avec succès !');
                    break;
                    
                case 'cancel':
                    $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    setSuccessMessage('Réservation annulée avec succès !');
                    break;
            }
            header('Location: reservations.php');
            exit;
        } catch (PDOException $e) {
            setErrorMessage('Erreur : ' . $e->getMessage());
        }
    }
}

// Récupération des clients
$clients = $pdo->query("SELECT id, first_name, last_name, phone FROM clients ORDER BY last_name, first_name")->fetchAll();

// Récupération des chambres disponibles
$availableRooms = $pdo->query("
    SELECT r.id, r.room_number, rt.type_name, rt.base_price, rt.capacity
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE r.status = 'available'
    ORDER BY r.room_number
")->fetchAll();

// Filtres
$statusFilter = $_GET['status'] ?? '';
$clientFilter = $_GET['client'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Requête principale
$query = "
    SELECT 
        r.*,
        CONCAT(c.first_name, ' ', c.last_name) as client_name,
        c.phone as client_phone,
        c.email as client_email,
        rm.room_number,
        rt.type_name as room_type,
        DATEDIFF(r.check_out_date, r.check_in_date) as nights
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN rooms rm ON r.room_id = rm.id
    JOIN room_types rt ON rm.room_type_id = rt.id
    WHERE 1=1
";

$params = [];

if ($statusFilter) {
    $query .= " AND r.status = ?";
    $params[] = $statusFilter;
}

if ($clientFilter) {
    $query .= " AND r.client_id = ?";
    $params[] = $clientFilter;
}

if ($searchTerm) {
    $query .= " AND (CONCAT(c.first_name, ' ', c.last_name) LIKE ? OR rm.room_number LIKE ? OR c.phone LIKE ?)";
    $search = "%$searchTerm%";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

$query .= " ORDER BY r.check_in_date DESC, r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-calendar-check"></i> Liste des Réservations (<?php echo count($reservations); ?>)</h2>
        <button class="btn btn-primary" onclick="openModal('addReservationModal')">
            <i class="fas fa-plus"></i> Nouvelle Réservation
        </button>
    </div>
    
    <!-- Filtres -->
    <form method="GET" class="filters">
        <div class="form-row">
            <div class="form-group search-box">
                <input type="text" 
                       name="search" 
                       class="form-control" 
                       placeholder="Rechercher par client, chambre..."
                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                <i class="fas fa-search"></i>
            </div>
            
            <div class="form-group">
                <select name="status" class="form-control">
                    <option value="">Tous les statuts</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>En attente</option>
                    <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmée</option>
                    <option value="checked_in" <?php echo $statusFilter === 'checked_in' ? 'selected' : ''; ?>>Arrivé</option>
                    <option value="checked_out" <?php echo $statusFilter === 'checked_out' ? 'selected' : ''; ?>>Parti</option>
                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                </select>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
                <a href="reservations.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Réinitialiser
                </a>
            </div>
        </div>
    </form>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Chambre</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Nuits</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Paiement</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                <tr>
                    <td colspan="11" class="text-center">Aucune réservation trouvée</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($reservations as $reservation): ?>
                    <tr>
                        <td><strong>#<?php echo $reservation['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($reservation['client_name']); ?></td>
                        <td>
                            <small><?php echo htmlspecialchars($reservation['client_phone']); ?></small>
                        </td>
                        <td>
                            <span class="badge badge-info"><?php echo htmlspecialchars($reservation['room_number']); ?></span><br>
                            <small><?php echo htmlspecialchars($reservation['room_type']); ?></small>
                        </td>
                        <td><?php echo formatDate($reservation['check_in_date']); ?></td>
                        <td><?php echo formatDate($reservation['check_out_date']); ?></td>
                        <td><?php echo $reservation['nights']; ?> nuits</td>
                        <td><strong><?php echo formatPrice($reservation['total_price']); ?></strong></td>
                        <td>
                            <?php
                            $statusClass = '';
                            $statusLabel = '';
                            switch ($reservation['status']) {
                                case 'pending':
                                    $statusClass = 'badge-warning';
                                    $statusLabel = 'En attente';
                                    break;
                                case 'confirmed':
                                    $statusClass = 'badge-info';
                                    $statusLabel = 'Confirmée';
                                    break;
                                case 'checked_in':
                                    $statusClass = 'badge-success';
                                    $statusLabel = 'Arrivé';
                                    break;
                                case 'checked_out':
                                    $statusClass = 'badge-secondary';
                                    $statusLabel = 'Parti';
                                    break;
                                case 'cancelled':
                                    $statusClass = 'badge-danger';
                                    $statusLabel = 'Annulée';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                        </td>
                        <td>
                            <?php
                            $paymentClass = '';
                            $paymentLabel = '';
                            switch ($reservation['payment_status']) {
                                case 'unpaid':
                                    $paymentClass = 'badge-danger';
                                    $paymentLabel = 'Non payé';
                                    break;
                                case 'partial':
                                    $paymentClass = 'badge-warning';
                                    $paymentLabel = 'Partiel';
                                    break;
                                case 'paid':
                                    $paymentClass = 'badge-success';
                                    $paymentLabel = 'Payé';
                                    break;
                                case 'refunded':
                                    $paymentClass = 'badge-info';
                                    $paymentLabel = 'Remboursé';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $paymentClass; ?>"><?php echo $paymentLabel; ?></span>
                        </td>
                        <td>
                            <div class="actions">
                                <button onclick="viewReservation(<?php echo htmlspecialchars(json_encode($reservation)); ?>)" 
                                        class="action-btn action-btn-view">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($reservation['status'] !== 'cancelled' && $reservation['status'] !== 'checked_out'): ?>
                                <button onclick="editReservation(<?php echo htmlspecialchars(json_encode($reservation)); ?>)" 
                                        class="action-btn action-btn-edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirmDelete('Êtes-vous sûr de vouloir annuler cette réservation ?')">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="id" value="<?php echo $reservation['id']; ?>">
                                    <button type="submit" class="action-btn action-btn-delete">
                                        <i class="fas fa-ban"></i>
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

<!-- Modal Ajout Réservation -->
<div id="addReservationModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2><i class="fas fa-plus"></i> Nouvelle Réservation</h2>
            <button class="close-modal" onclick="closeModal('addReservationModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="client_id" class="required">Client</label>
                    <select id="client_id" name="client_id" class="form-control" required>
                        <option value="">Sélectionner un client...</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>">
                                <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                (<?php echo htmlspecialchars($client['phone']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="room_id" class="required">Chambre</label>
                    <select id="room_id" name="room_id" class="form-control" required>
                        <option value="">Sélectionner une chambre...</option>
                        <?php foreach ($availableRooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" data-price="<?php echo $room['base_price']; ?>">
                                <?php echo htmlspecialchars($room['room_number']); ?> - 
                                <?php echo htmlspecialchars($room['type_name']); ?>
                                (<?php echo formatPrice($room['base_price']); ?>/nuit)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="check_in_date" class="required">Date d'arrivée</label>
                    <input type="date" id="check_in_date" name="check_in_date" 
                           class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="check_out_date" class="required">Date de départ</label>
                    <input type="date" id="check_out_date" name="check_out_date" 
                           class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="number_of_guests" class="required">Nombre de personnes</label>
                    <input type="number" id="number_of_guests" name="number_of_guests" 
                           class="form-control" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="total_price" class="required">Prix total (AR)</label>
                    <input type="number" id="total_price" name="total_price" 
                           class="form-control" step="0.01" required readonly>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="status" class="required">Statut</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="pending">En attente</option>
                        <option value="confirmed" selected>Confirmée</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="payment_status" class="required">État du paiement</label>
                    <select id="payment_status" name="payment_status" class="form-control" required>
                        <option value="unpaid" selected>Non payé</option>
                        <option value="partial">Partiel</option>
                        <option value="paid">Payé</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="special_requests">Demandes spéciales</label>
                <textarea id="special_requests" name="special_requests" 
                          class="form-control" rows="3"></textarea>
            </div>
            
            <div class="btn-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addReservationModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Édition (similaire à l'ajout) -->
<div id="editReservationModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Modifier la Réservation</h2>
            <button class="close-modal" onclick="closeModal('editReservationModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_client_id" class="required">Client</label>
                    <select id="edit_client_id" name="client_id" class="form-control" required>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>">
                                <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_room_id" class="required">Chambre</label>
                    <select id="edit_room_id" name="room_id" class="form-control" required>
                        <?php foreach ($availableRooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" data-price="<?php echo $room['base_price']; ?>">
                                <?php echo htmlspecialchars($room['room_number'] . ' - ' . $room['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_check_in_date" class="required">Date d'arrivée</label>
                    <input type="date" id="edit_check_in_date" name="check_in_date" 
                           class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_check_out_date" class="required">Date de départ</label>
                    <input type="date" id="edit_check_out_date" name="check_out_date" 
                           class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_number_of_guests" class="required">Nombre de personnes</label>
                    <input type="number" id="edit_number_of_guests" name="number_of_guests" 
                           class="form-control" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_total_price" class="required">Prix total (AR)</label>
                    <input type="number" id="edit_total_price" name="total_price" 
                           class="form-control" step="0.01" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_status" class="required">Statut</label>
                    <select id="edit_status" name="status" class="form-control" required>
                        <option value="pending">En attente</option>
                        <option value="confirmed">Confirmée</option>
                        <option value="checked_in">Arrivé</option>
                        <option value="checked_out">Parti</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_payment_status" class="required">État du paiement</label>
                    <select id="edit_payment_status" name="payment_status" class="form-control" required>
                        <option value="unpaid">Non payé</option>
                        <option value="partial">Partiel</option>
                        <option value="paid">Payé</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_special_requests">Demandes spéciales</label>
                <textarea id="edit_special_requests" name="special_requests" 
                          class="form-control" rows="3"></textarea>
            </div>
            
            <div class="btn-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editReservationModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Voir Détails -->
<div id="viewReservationModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2><i class="fas fa-info-circle"></i> Détails de la Réservation</h2>
            <button class="close-modal" onclick="closeModal('viewReservationModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="reservationDetails"></div>
    </div>
</div>

<script>
function editReservation(reservation) {
    document.getElementById('edit_id').value = reservation.id;
    document.getElementById('edit_client_id').value = reservation.client_id;
    document.getElementById('edit_room_id').value = reservation.room_id;
    document.getElementById('edit_check_in_date').value = reservation.check_in_date;
    document.getElementById('edit_check_out_date').value = reservation.check_out_date;
    document.getElementById('edit_number_of_guests').value = reservation.number_of_guests;
    document.getElementById('edit_total_price').value = reservation.total_price;
    document.getElementById('edit_status').value = reservation.status;
    document.getElementById('edit_payment_status').value = reservation.payment_status;
    document.getElementById('edit_special_requests').value = reservation.special_requests || '';
    openModal('editReservationModal');
}

function viewReservation(reservation) {
    const statusLabels = {
        'pending': '<span class="badge badge-warning">En attente</span>',
        'confirmed': '<span class="badge badge-info">Confirmée</span>',
        'checked_in': '<span class="badge badge-success">Arrivé</span>',
        'checked_out': '<span class="badge badge-secondary">Parti</span>',
        'cancelled': '<span class="badge badge-danger">Annulée</span>'
    };
    
    const paymentLabels = {
        'unpaid': '<span class="badge badge-danger">Non payé</span>',
        'partial': '<span class="badge badge-warning">Partiel</span>',
        'paid': '<span class="badge badge-success">Payé</span>',
        'refunded': '<span class="badge badge-info">Remboursé</span>'
    };
    
    const html = `
        <div style="padding: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <p><strong>N° Réservation:</strong><br>#${reservation.id}</p>
                </div>
                <div>
                    <p><strong>Client:</strong><br>${reservation.client_name}</p>
                </div>
                <div>
                    <p><strong>Téléphone:</strong><br>${reservation.client_phone}</p>
                </div>
                <div>
                    <p><strong>Email:</strong><br>${reservation.client_email || '-'}</p>
                </div>
                <div>
                    <p><strong>Chambre:</strong><br>${reservation.room_number} - ${reservation.room_type}</p>
                </div>
                <div>
                    <p><strong>Nombre de personnes:</strong><br>${reservation.number_of_guests}</p>
                </div>
                <div>
                    <p><strong>Check-in:</strong><br>${formatDate(reservation.check_in_date)}</p>
                </div>
                <div>
                    <p><strong>Check-out:</strong><br>${formatDate(reservation.check_out_date)}</p>
                </div>
                <div>
                    <p><strong>Nombre de nuits:</strong><br>${reservation.nights} nuits</p>
                </div>
                <div>
                    <p><strong>Prix total:</strong><br><strong style="font-size: 1.2em; color: var(--success-color);">${formatPrice(reservation.total_price)}</strong></p>
                </div>
                <div>
                    <p><strong>Statut:</strong><br>${statusLabels[reservation.status]}</p>
                </div>
                <div>
                    <p><strong>Paiement:</strong><br>${paymentLabels[reservation.payment_status]}</p>
                </div>
                ${reservation.special_requests ? `
                <div style="grid-column: 1 / -1;">
                    <p><strong>Demandes spéciales:</strong><br>${reservation.special_requests}</p>
                </div>
                ` : ''}
            </div>
            <div style="margin-top: 20px; text-align: center; padding-top: 20px; border-top: 1px solid #ddd;">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimer
                </button>
            </div>
        </div>
    `;
    document.getElementById('reservationDetails').innerHTML = html;
    openModal('viewReservationModal');
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR');
}

document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[action*="reservations.php"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Vérifier si l'utilisateur est connecté via la variable de session PHP
            const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
            
            if (!isLoggedIn) {
                e.preventDefault();
                alert('Veuillez vous connecter pour effectuer cette action');
                window.location.href = 'login.php';
                return false;
            }
            return true;
        });
    });
});

function formatPrice(price) {
    // Convertir en nombre, arrondir à l'entier le plus proche et formater avec des espaces comme séparateurs de milliers
    return parseFloat(price).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' Ar';
}
</script>

<?php include 'includes/footer.php'; ?>
