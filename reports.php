<?php
require_once 'includes/config.php';
requireAdmin(); // Seuls les admins peuvent voir les rapports

$page_title = 'Rapports et Statistiques';

// Période sélectionnée
$period = $_GET['period'] ?? 'month';
$customStart = $_GET['start_date'] ?? '';
$customEnd = $_GET['end_date'] ?? '';

// Définir les dates selon la période
switch ($period) {
    case 'today':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        break;
    case 'week':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
        break;
    case 'month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        break;
    case 'year':
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        break;
    case 'custom':
        $startDate = $customStart ?: date('Y-m-01');
        $endDate = $customEnd ?: date('Y-m-d');
        break;
    default:
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
}

// Revenus totaux
$revenueStmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM payments 
    WHERE DATE(payment_date) BETWEEN ? AND ?
");
$revenueStmt->execute([$startDate, $endDate]);
$totalRevenue = $revenueStmt->fetch()['total'];

// Nombre de réservations
$reservationsStmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM reservations 
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$reservationsStmt->execute([$startDate, $endDate]);
$totalReservations = $reservationsStmt->fetch()['total'];

// Taux d'occupation
$occupancyStmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT res.room_id) as occupied_rooms,
        (SELECT COUNT(*) FROM rooms WHERE status != 'maintenance') as available_rooms
    FROM reservations res
    JOIN rooms r ON res.room_id = r.id
    WHERE res.status IN ('confirmed', 'checked_in')
    AND DATE(?) BETWEEN res.check_in_date AND res.check_out_date
");
$occupancyStmt->execute([date('Y-m-d')]);
$occupancyData = $occupancyStmt->fetch();
$occupancyRate = $occupancyData['available_rooms'] > 0 
    ? round(($occupancyData['occupied_rooms'] / $occupancyData['available_rooms']) * 100, 2) 
    : 0;

// Revenus par méthode de paiement
$paymentMethodStmt = $pdo->prepare("
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(amount) as total
    FROM payments 
    WHERE DATE(payment_date) BETWEEN ? AND ?
    GROUP BY payment_method
");
$paymentMethodStmt->execute([$startDate, $endDate]);
$paymentMethods = $paymentMethodStmt->fetchAll();

// Top 5 clients
$topClientsStmt = $pdo->prepare("
    SELECT 
        c.id,
        CONCAT(c.first_name, ' ', c.last_name) as client_name,
        COUNT(r.id) as reservation_count,
        SUM(r.total_price) as total_spent
    FROM clients c
    JOIN reservations r ON c.id = r.client_id
    WHERE DATE(r.created_at) BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY total_spent DESC
    LIMIT 5
");
$topClientsStmt->execute([$startDate, $endDate]);
$topClients = $topClientsStmt->fetchAll();

// Réservations par statut
$statusStmt = $pdo->prepare("
    SELECT 
        status,
        COUNT(*) as count
    FROM reservations 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY status
");
$statusStmt->execute([$startDate, $endDate]);
$reservationsByStatus = $statusStmt->fetchAll();

// Chambres les plus réservées
$topRoomsStmt = $pdo->prepare("
    SELECT 
        rm.room_number,
        rt.type_name,
        COUNT(r.id) as reservation_count,
        SUM(r.total_price) as total_revenue
    FROM reservations r
    JOIN rooms rm ON r.room_id = rm.id
    JOIN room_types rt ON rm.room_type_id = rt.id
    WHERE DATE(r.created_at) BETWEEN ? AND ?
    GROUP BY rm.id
    ORDER BY reservation_count DESC
    LIMIT 5
");
$topRoomsStmt->execute([$startDate, $endDate]);
$topRooms = $topRoomsStmt->fetchAll();

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-chart-line"></i> Filtres de Période</h2>
    </div>
    <form method="GET" class="filters">
        <div class="form-row">
            <div class="form-group">
                <select name="period" id="periodSelect" class="form-control" onchange="toggleCustomDates()">
                    <option value="today" <?php echo $period === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                    <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>7 derniers jours</option>
                    <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                    <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>Cette année</option>
                    <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>Période personnalisée</option>
                </select>
            </div>
            
            <div id="customDates" style="display: <?php echo $period === 'custom' ? 'contents' : 'none'; ?>;">
                <div class="form-group">
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo htmlspecialchars($customStart); ?>" placeholder="Date début">
                </div>
                <div class="form-group">
                    <input type="date" name="end_date" class="form-control" 
                           value="<?php echo htmlspecialchars($customEnd); ?>" placeholder="Date fin">
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Appliquer
                </button>
                <a href="reports.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Réinitialiser
                </a>
            </div>
        </div>
    </form>
    <div style="padding: 0 20px 10px; color: var(--light-text);">
        <small>
            <i class="fas fa-calendar"></i> 
            Période : <?php echo formatDate($startDate); ?> au <?php echo formatDate($endDate); ?>
        </small>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-euro-sign"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo formatPrice($totalRevenue); ?></h3>
            <p>Revenus Totaux</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo $totalReservations; ?></h3>
            <p>Réservations</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-percentage"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo $occupancyRate; ?>%</h3>
            <p>Taux d'Occupation</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-calculator"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo $totalReservations > 0 ? formatPrice($totalRevenue / $totalReservations) : '0 €'; ?></h3>
            <p>Panier Moyen</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
    <!-- Répartition des paiements -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-credit-card"></i> Méthodes de Paiement</h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Méthode</th>
                        <th>Transactions</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paymentMethods)): ?>
                    <tr>
                        <td colspan="3" class="text-center">Aucun paiement pour cette période</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($paymentMethods as $method): ?>
                        <tr>
                            <td>
                                <?php
                                $methodLabels = [
                                    'cash' => '💵 Espèces',
                                    'card' => '💳 Carte',
                                    'bank_transfer' => '🏦 Virement',
                                    'online' => '🌐 En ligne'
                                ];
                                echo $methodLabels[$method['payment_method']] ?? $method['payment_method'];
                                ?>
                            </td>
                            <td><?php echo $method['count']; ?></td>
                            <td><strong><?php echo formatPrice($method['total']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Répartition par statut -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-chart-pie"></i> Statuts des Réservations</h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Statut</th>
                        <th>Nombre</th>
                        <th>Pourcentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservationsByStatus)): ?>
                    <tr>
                        <td colspan="3" class="text-center">Aucune réservation pour cette période</td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $totalCount = array_sum(array_column($reservationsByStatus, 'count'));
                        foreach ($reservationsByStatus as $status): 
                            $percentage = $totalCount > 0 ? round(($status['count'] / $totalCount) * 100, 1) : 0;
                            $statusLabels = [
                                'pending' => '⏳ En attente',
                                'confirmed' => '✅ Confirmée',
                                'checked_in' => '🏨 Arrivé',
                                'checked_out' => '👋 Parti',
                                'cancelled' => '❌ Annulée'
                            ];
                        ?>
                        <tr>
                            <td><?php echo $statusLabels[$status['status']] ?? $status['status']; ?></td>
                            <td><?php echo $status['count']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: var(--light-bg); height: 20px; border-radius: 10px; overflow: hidden;">
                                        <div style="background: var(--secondary-color); height: 100%; width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                    <span><strong><?php echo $percentage; ?>%</strong></span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top clients -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-star"></i> Top 5 Clients</h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Client</th>
                    <th>Réservations</th>
                    <th>Montant Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topClients)): ?>
                <tr>
                    <td colspan="4" class="text-center">Aucun client pour cette période</td>
                </tr>
                <?php else: ?>
                    <?php 
                    $position = 1;
                    foreach ($topClients as $client): 
                    ?>
                    <tr>
                        <td>
                            <?php
                            $medals = ['🥇', '🥈', '🥉'];
                            echo $position <= 3 ? $medals[$position - 1] : $position;
                            ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($client['client_name']); ?></strong></td>
                        <td><span class="badge badge-info"><?php echo $client['reservation_count']; ?></span></td>
                        <td><strong class="text-success"><?php echo formatPrice($client['total_spent']); ?></strong></td>
                    </tr>
                    <?php 
                    $position++;
                    endforeach; 
                    ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chambres les plus réservées -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-trophy"></i> Top 5 Chambres les Plus Réservées</h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Chambre</th>
                    <th>Type</th>
                    <th>Réservations</th>
                    <th>Revenus</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topRooms)): ?>
                <tr>
                    <td colspan="5" class="text-center">Aucune réservation pour cette période</td>
                </tr>
                <?php else: ?>
                    <?php 
                    $position = 1;
                    foreach ($topRooms as $room): 
                    ?>
                    <tr>
                        <td>
                            <?php
                            $medals = ['🥇', '🥈', '🥉'];
                            echo $position <= 3 ? $medals[$position - 1] : $position;
                            ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo $room['reservation_count']; ?></span></td>
                        <td><strong class="text-success"><?php echo formatPrice($room['total_revenue']); ?></strong></td>
                    </tr>
                    <?php 
                    $position++;
                    endforeach; 
                    ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleCustomDates() {
    const select = document.getElementById('periodSelect');
    const customDates = document.getElementById('customDates');
    customDates.style.display = select.value === 'custom' ? 'contents' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
