<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Gestion Hôtel</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="sidebar">
        <div class="sidebar-header">
            <h2 style="color: white; font-weight: bold;"><i class="fas fa-hotel"></i> Hotel Belety</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-dashboard"></i> Dashboard
            </a></li>
            <li><a href="rooms.php" <?php echo basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-bed"></i> Chambres
            </a></li>
            <li><a href="reservations.php" <?php echo basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-calendar-check"></i> Réservations
            </a></li>
            <li><a href="clients.php" <?php echo basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-users"></i> Clients
            </a></li>
            <li><a href="payments.php" <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-money-bill-wave"></i> Paiements
            </a></li>
            <li><a href="services.php" <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-concierge-bell"></i> Services
            </a></li>
            <?php if (isAdmin()): ?>
            <li><a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-user-shield"></i> Utilisateurs
            </a></li>
            <li><a href="reports.php" <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-chart-line"></i> Rapports
            </a></li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
            <p>© <?php echo date('Y'); ?> Hotel Belety</p>
        </div>
    </nav>
    <?php endif; ?>
    
    <div class="main-content <?php echo isLoggedIn() ? 'with-sidebar' : ''; ?>">
        <?php if (isLoggedIn()): ?>
        <header class="top-header">
            <div class="header-left">
                <h1><?php echo $page_title ?? 'Dashboard'; ?></h1>
            </div>
            <div class="header-right">
                <div class="header-user-info">
                    <span class="date-time">
                        <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i'); ?>
                    </span>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <div class="user-details">
                            <span><?php echo $_SESSION['full_name'] ?? 'Utilisateur'; ?></span>
                            <small><?php echo $_SESSION['role'] ?? ''; ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <?php endif; ?>
        
        <div class="content-wrapper">
            <?php displayMessages(); ?>
