<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'Dashboard';

// Récupération des statistiques
try {
    // Nombre total de chambres
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms");
    $totalRooms = $stmt->fetch()['total'];
    
    // Chambres disponibles
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'available'");
    $availableRooms = $stmt->fetch()['total'];
    
    // Chambres occupées
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'occupied'");
    $occupiedRooms = $stmt->fetch()['total'];
    
    // Réservations actives
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE status IN ('confirmed', 'checked_in')");
    $activeReservations = $stmt->fetch()['total'];
    
    // Réservations d'aujourd'hui
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE DATE(check_in_date) = CURDATE()");
    $todayCheckIns = $stmt->fetch()['total'];
    
    // Revenus du mois
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
    $monthlyRevenue = $stmt->fetch()['total'];
    
    // Dernières réservations
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.check_in_date,
            r.check_out_date,
            r.status,
            r.total_price,
            CONCAT(c.first_name, ' ', c.last_name) as client_name,
            rm.room_number,
            rt.type_name
        FROM reservations r
        JOIN clients c ON r.client_id = c.id
        JOIN rooms rm ON r.room_id = rm.id
        JOIN room_types rt ON rm.room_type_id = rt.id
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $recentReservations = $stmt->fetchAll();
    
    // Chambres nécessitant une attention
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.room_number,
            r.status,
            rt.type_name,
            r.floor
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE r.status IN ('maintenance', 'cleaning')
        ORDER BY r.room_number
    ");
    $roomsNeedingAttention = $stmt->fetchAll();
    
    // Check-ins prévus aujourd'hui
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.check_in_date,
            CONCAT(c.first_name, ' ', c.last_name) as client_name,
            c.phone,
            rm.room_number,
            rt.type_name
        FROM reservations r
        JOIN clients c ON r.client_id = c.id
        JOIN rooms rm ON r.room_id = rm.id
        JOIN room_types rt ON rm.room_type_id = rt.id
        WHERE DATE(r.check_in_date) = CURDATE()
        AND r.status = 'confirmed'
        ORDER BY r.check_in_date
    ");
    $todayCheckInsList = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setErrorMessage('Erreur lors de la récupération des données : ' . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-bed"></i>
        </div>
        <div class="stat-details">
            <h3 id="stat-total-rooms"><?php echo $totalRooms; ?></h3>
            <p>Total Chambres</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-door-open"></i>
        </div>
        <div class="stat-details">
            <h3 id="stat-available-rooms"><?php echo $availableRooms; ?></h3>
            <p>Chambres Disponibles</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-door-closed"></i>
        </div>
        <div class="stat-details">
            <h3 id="stat-occupied-rooms"><?php echo $occupiedRooms; ?></h3>
            <p>Chambres Occupées</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-details">
            <h3 id="stat-active-reservations"><?php echo $activeReservations; ?></h3>
            <p>Réservations Actives</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-money-bill-wave"></i> Revenus du mois</h2>
        </div>
        <div style="text-align: center; padding: 30px 0;">
            <h1 style="color: var(--success-color); font-size: 3rem; margin: 0;">
                <?php echo formatPrice($monthlyRevenue); ?>
            </h1>
            <p style="color: var(--light-text); margin-top: 10px;">
                <i class="fas fa-calendar"></i> <?php echo date('F Y'); ?>
            </p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-sign-in-alt"></i> Check-ins Aujourd'hui</h2>
        </div>
        <div style="text-align: center; padding: 30px 0;">
            <h1 style="color: var(--info-color); font-size: 3rem; margin: 0;">
                <?php echo $todayCheckIns; ?>
            </h1>
            <p style="color: var(--light-text); margin-top: 10px;">
                <i class="fas fa-calendar-day"></i> <?php echo formatDate(date('Y-m-d')); ?>
            </p>
        </div>
    </div>
</div>

<?php if (!empty($todayCheckInsList)): ?>
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-calendar-day"></i> Arrivées Prévues Aujourd'hui</h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Chambre</th>
                    <th>Type</th>
                    <th>Heure d'arrivée</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($todayCheckInsList as $checkin): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($checkin['client_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($checkin['phone']); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($checkin['room_number']); ?></span></td>
                    <td><?php echo htmlspecialchars($checkin['type_name']); ?></td>
                    <td><?php echo date('H:i', strtotime($checkin['check_in_date'])); ?></td>
                    <td>
                        <a href="reservations.php?view=<?php echo $checkin['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> Voir
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-clock"></i> Réservations Récentes</h2>
        <a href="reservations.php" class="btn btn-primary btn-sm">
            <i class="fas fa-list"></i> Voir tout
        </a>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Chambre</th>
                    <th>Type</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentReservations)): ?>
                <tr>
                    <td colspan="9" class="text-center">Aucune réservation trouvée</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recentReservations as $reservation): ?>
                    <tr>
                        <td><strong>#<?php echo $reservation['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($reservation['client_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($reservation['room_number']); ?></span></td>
                        <td><?php echo htmlspecialchars($reservation['type_name']); ?></td>
                        <td><?php echo formatDate($reservation['check_in_date']); ?></td>
                        <td><?php echo formatDate($reservation['check_out_date']); ?></td>
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
                            <div class="actions">
                                <a href="reservations.php?view=<?php echo $reservation['id']; ?>" 
                                   class="action-btn action-btn-view">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($roomsNeedingAttention)): ?>
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-exclamation-triangle"></i> Chambres Nécessitant Attention</h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Type</th>
                    <th>Étage</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roomsNeedingAttention as $room): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                    <td>Étage <?php echo $room['floor']; ?></td>
                    <td>
                        <?php if ($room['status'] === 'maintenance'): ?>
                            <span class="badge badge-danger">Maintenance</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Nettoyage</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="rooms.php?edit=<?php echo $room['id']; ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
