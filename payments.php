<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'Gestion des Paiements';

// Traitement ajout paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $pdo->beginTransaction();
        
        // Ajouter le paiement
        $stmt = $pdo->prepare("
            INSERT INTO payments (reservation_id, amount, payment_method, transaction_id, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['reservation_id'],
            $_POST['amount'],
            $_POST['payment_method'],
            cleanInput($_POST['transaction_id']),
            cleanInput($_POST['notes']),
            $_SESSION['user_id']
        ]);
        
        // Calculer le total payé
        $stmtTotal = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE reservation_id = ?");
        $stmtTotal->execute([$_POST['reservation_id']]);
        $totalPaid = $stmtTotal->fetch()['total'];
        
        // Récupérer le prix total de la réservation
        $stmtReservation = $pdo->prepare("SELECT total_price FROM reservations WHERE id = ?");
        $stmtReservation->execute([$_POST['reservation_id']]);
        $totalPrice = $stmtReservation->fetch()['total_price'];
        
        // Mettre à jour le statut de paiement
        $paymentStatus = 'unpaid';
        if ($totalPaid >= $totalPrice) {
            $paymentStatus = 'paid';
        } elseif ($totalPaid > 0) {
            $paymentStatus = 'partial';
        }
        
        $stmtUpdate = $pdo->prepare("UPDATE reservations SET payment_status = ? WHERE id = ?");
        $stmtUpdate->execute([$paymentStatus, $_POST['reservation_id']]);
        
        $pdo->commit();
        setSuccessMessage('Paiement enregistré avec succès !');
        header('Location: payments.php');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        setErrorMessage('Erreur : ' . $e->getMessage());
    }
}

// Récupération des réservations pour le formulaire
$reservations = $pdo->query("
    SELECT r.id, CONCAT(c.first_name, ' ', c.last_name) as client_name, 
           rm.room_number, r.total_price, r.payment_status
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.status NOT IN ('cancelled', 'checked_out')
    ORDER BY r.check_in_date DESC
")->fetchAll();

// Récupération des paiements
$query = "
    SELECT 
        p.*,
        r.id as reservation_id,
        CONCAT(c.first_name, ' ', c.last_name) as client_name,
        rm.room_number,
        r.total_price,
        u.full_name as created_by_name
    FROM payments p
    JOIN reservations r ON p.reservation_id = r.id
    JOIN clients c ON r.client_id = c.id
    JOIN rooms rm ON r.room_id = rm.id
    LEFT JOIN users u ON p.created_by = u.id
    ORDER BY p.payment_date DESC
";

$payments = $pdo->query($query)->fetchAll();

// Statistiques
$statsQuery = "
    SELECT 
        COUNT(*) as total_payments,
        COALESCE(SUM(amount), 0) as total_amount,
        COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END), 0) as cash_total,
        COALESCE(SUM(CASE WHEN payment_method = 'card' THEN amount ELSE 0 END), 0) as card_total,
        COALESCE(SUM(CASE WHEN payment_method = 'bank_transfer' THEN amount ELSE 0 END), 0) as transfer_total
    FROM payments
    WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())
";
$stats = $pdo->query($statsQuery)->fetch();

include 'includes/header.php';
?>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo formatPrice($stats['total_amount']); ?></h3>
            <p>Total du mois</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo $stats['total_payments']; ?></h3>
            <p>Paiements ce mois</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-money-bill"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo formatPrice($stats['cash_total']); ?></h3>
            <p>Espèces</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-credit-card"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo formatPrice($stats['card_total']); ?></h3>
            <p>Carte bancaire</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-money-bill-wave"></i> Historique des Paiements (<?php echo count($payments); ?>)</h2>
        <button class="btn btn-primary" onclick="openModal('addPaymentModal')">
            <i class="fas fa-plus"></i> Nouveau Paiement
        </button>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Réservation</th>
                    <th>Client</th>
                    <th>Chambre</th>
                    <th>Montant</th>
                    <th>Méthode</th>
                    <th>Transaction</th>
                    <th>Enregistré par</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                <tr>
                    <td colspan="9" class="text-center">Aucun paiement trouvé</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><strong>#<?php echo $payment['id']; ?></strong></td>
                        <td><?php echo formatDateTime($payment['payment_date']); ?></td>
                        <td><a href="reservations.php?view=<?php echo $payment['reservation_id']; ?>">#<?php echo $payment['reservation_id']; ?></a></td>
                        <td><?php echo htmlspecialchars($payment['client_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($payment['room_number']); ?></span></td>
                        <td><strong class="text-success"><?php echo formatPrice($payment['amount']); ?></strong></td>
                        <td>
                            <?php
                            $methodLabel = '';
                            $methodIcon = '';
                            switch ($payment['payment_method']) {
                                case 'cash':
                                    $methodLabel = 'Espèces';
                                    $methodIcon = 'fa-money-bill';
                                    break;
                                case 'card':
                                    $methodLabel = 'Carte';
                                    $methodIcon = 'fa-credit-card';
                                    break;
                                case 'bank_transfer':
                                    $methodLabel = 'Virement';
                                    $methodIcon = 'fa-university';
                                    break;
                                case 'online':
                                    $methodLabel = 'En ligne';
                                    $methodIcon = 'fa-globe';
                                    break;
                            }
                            ?>
                            <i class="fas <?php echo $methodIcon; ?>"></i> <?php echo $methodLabel; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars($payment['transaction_id'] ?: '-'); ?></small></td>
                        <td><small><?php echo htmlspecialchars($payment['created_by_name'] ?: '-'); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajout Paiement -->
<div id="addPaymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus"></i> Nouveau Paiement</h2>
            <button class="close-modal" onclick="closeModal('addPaymentModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="reservation_id" class="required">Réservation</label>
                <select id="reservation_id" name="reservation_id" class="form-control" required onchange="updateReservationInfo()">
                    <option value="">Sélectionner une réservation...</option>
                    <?php foreach ($reservations as $reservation): ?>
                        <option value="<?php echo $reservation['id']; ?>" 
                                data-total="<?php echo $reservation['total_price']; ?>"
                                data-status="<?php echo $reservation['payment_status']; ?>">
                            #<?php echo $reservation['id']; ?> - 
                            <?php echo htmlspecialchars($reservation['client_name']); ?> - 
                            Chambre <?php echo htmlspecialchars($reservation['room_number']); ?>
                            (<?php echo formatPrice($reservation['total_price']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="reservation-info" style="display: none; padding: 15px; background: var(--light-bg); border-radius: 5px; margin-bottom: 15px;">
                <p><strong>Montant total:</strong> <span id="info-total">0 Ar</span></p>
                <p><strong>Statut paiement:</strong> <span id="info-status">-</span></p>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="amount" class="required">Montant (Ar)</label>
                    <input type="number" id="amount" name="amount" 
                           class="form-control" step="0.01" min="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="payment_method" class="required">Méthode de paiement</label>
                    <select id="payment_method" name="payment_method" class="form-control" required>
                        <option value="cash">Espèces</option>
                        <option value="card">Carte bancaire</option>
                        <option value="bank_transfer">Virement bancaire</option>
                        <option value="online">Paiement en ligne</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="transaction_id">N° de transaction</label>
                <input type="text" id="transaction_id" name="transaction_id" 
                       class="form-control" placeholder="Optionnel">
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" 
                          class="form-control" rows="3" placeholder="Notes additionnelles..."></textarea>
            </div>
            
            <div class="btn-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addPaymentModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateReservationInfo() {
    const select = document.getElementById('reservation_id');
    const option = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('reservation-info');
    
    if (option.value) {
        const total = option.dataset.total;
        const status = option.dataset.status;
        
        document.getElementById('info-total').textContent = parseFloat(total).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' Ar';
        
        let statusLabel = '';
        switch(status) {
            case 'unpaid': statusLabel = '❌ Non payé'; break;
            case 'partial': statusLabel = '⚠️ Partiel'; break;
            case 'paid': statusLabel = '✅ Payé'; break;
        }
        document.getElementById('info-status').textContent = statusLabel;
        
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
