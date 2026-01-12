<?php
/**
 * Configuration de la base de données et paramètres de l'application
 */

// Configuration de la session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotel_management');

// Paramètres de l'application
define('SITE_NAME', 'Hotel Management System');
define('SITE_URL', 'http://localhost/hotel-management-system');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Connexion à la base de données
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Fonction pour rediriger vers la page de connexion
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Fonction pour rediriger vers le dashboard
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

// Fonction pour nettoyer les entrées
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour formater les dates
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Fonction pour formater les dates et heures
function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

// Fonction pour formater les prix en ariary (Ar)
function formatPrice($price) {
    // S'assurer que le prix est un nombre
    $price = (float)$price;
    // Formater le nombre avec des espaces comme séparateur de milliers et sans décimales
    return number_format($price, 0, ' ', ' ') . ' Ar';
}

// Fonction pour générer un message de succès
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

// Fonction pour générer un message d'erreur
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

// Fonction pour afficher les messages
function displayMessages() {
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-error">' . $_SESSION['error_message'] . '</div>';
        unset($_SESSION['error_message']);
    }
}

// Fonction pour calculer le nombre de nuits
function calculateNights($check_in, $check_out) {
    $date1 = new DateTime($check_in);
    $date2 = new DateTime($check_out);
    $interval = $date1->diff($date2);
    return $interval->days;
}

// Fonction pour générer un mot de passe hashé
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Fonction pour vérifier un mot de passe
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Fonction pour générer un code d'invitation sécurisé
function generateInvitationCode($length = 32) {
    return bin2hex(random_bytes($length));
}

// Fonction pour valider un code d'invitation
function validateInvitationCode($code) {
    // Dans une application réelle, vous pourriez vérifier ce code dans une table de base de données
    // Pour cet exemple, nous utilisons une valeur codée en dur (à ne pas faire en production)
    $validCode = 'admin_invite_2024';
    return hash_equals($validCode, $code);
}
