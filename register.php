<?php
require_once 'includes/config.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Vérification du code d'invitation
$invitationCode = $_GET['code'] ?? '';
if (empty($invitationCode) || !validateInvitationCode($invitationCode)) {
    setErrorMessage('Code d\'invitation invalide ou manquant. Veuillez contacter l\'administrateur.');
    header('Location: login.php');
    exit;
}

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $fullName = cleanInput($_POST['full_name']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validation des champs
    $errors = [];
    if (empty($username) || empty($email) || empty($fullName) || empty($password) || empty($confirmPassword)) {
        $errors[] = 'Tous les champs sont obligatoires.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }

    // Vérification de l'unicité du nom d'utilisateur et de l'email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = 'Ce nom d\'utilisateur ou cette adresse email est déjà utilisé.';
    }

    // Si pas d'erreurs, on procède à l'inscription
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Hachage du mot de passe
            $hashedPassword = hashPassword($password);

            // Insertion du nouvel administrateur
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, email, full_name, role)
                VALUES (?, ?, ?, ?, 'admin')
            ");
            $stmt->execute([$username, $hashedPassword, $email, $fullName]);

            $pdo->commit();

            setSuccessMessage('Compte administrateur créé avec succès ! Vous pouvez maintenant vous connecter.');
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            setErrorMessage('Une erreur est survenue lors de la création du compte : ' . $e->getMessage());
        }
    } else {
        foreach ($errors as $error) {
            setErrorMessage($error);
        }
    }
}

$page_title = 'Inscription Administrateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Gestion Hôtel</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Styles spécifiques à la page d'authentification */
        body.auth-page {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            padding: 2rem;
        }
        
        .auth-container {
            max-width: 480px;
            width: 100%;
            margin: auto;
        }
        
        .auth-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .auth-header {
            padding: 2.5rem 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .auth-logo {
            margin-bottom: 1rem;
        }
        
        .auth-logo i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .auth-logo h1 {
            font-size: 1.5rem;
            margin: 0.5rem 0 0.25rem;
            color: var(--dark-text);
        }
        
        .auth-header p {
            color: var(--light-text);
            margin: 0;
        }
        
        .auth-form {
            padding: 2rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
            z-index: 10;
        }
        
        .form-control {
            padding-left: 2.75rem !important;
        }
        
        .form-text {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: var(--light-text);
        }
        
        .btn-block {
            display: block;
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: var(--radius);
        }
        
        .auth-footer {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            text-align: center;
            border-top: 1px solid var(--border-color);
            font-size: 0.9375rem;
            color: var(--light-text);
        }
        
        .auth-footer a {
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        /* Animation pour les champs de formulaire */
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        /* Animation pour le bouton */
        .btn-primary {
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-hotel"></i>
                    <h1>Création du compte Administrateur</h1>
                </div>
                <p>Veuillez remplir le formulaire ci-dessous pour créer votre compte administrateur.</p>
            </div>

            <?php displayMessages(); ?>

            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="full_name">Nom complet</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               class="form-control" 
                               placeholder="Votre nom complet"
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user-tag"></i></span>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control" 
                               placeholder="Choisissez un nom d'utilisateur"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="Votre adresse email"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Créez un mot de passe sécurisé"
                               required>
                    </div>
                    <small class="form-text">Minimum 8 caractères</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-control" 
                               placeholder="Confirmez votre mot de passe"
                               required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Créer le compte
                </button>

                <div class="auth-footer">
                    <p>Déjà un compte ? <a href="login.php">Connectez-vous ici</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Script pour afficher/masquer le mot de passe
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInputs = document.querySelectorAll('input[type="password"]');
            
            passwordInputs.forEach(input => {
                const toggle = document.createElement('span');
                toggle.className = 'password-toggle';
                toggle.innerHTML = '<i class="far fa-eye"></i>';
                toggle.style.cursor = 'pointer';
                toggle.style.position = 'absolute';
                toggle.style.right = '10px';
                toggle.style.top = '50%';
                toggle.style.transform = 'translateY(-50%)';
                toggle.style.color = '#64748b';
                
                input.parentNode.style.position = 'relative';
                input.parentNode.appendChild(toggle);
                
                toggle.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="far fa-eye"></i>' : '<i class="far fa-eye-slash"></i>';
                });
            });
            
            // Validation en temps réel du formulaire
            const form = document.querySelector('form');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity("Les mots de passe ne correspondent pas");
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            password.onchange = validatePassword;
            confirmPassword.onkeyup = validatePassword;
            
            // Validation de la longueur du mot de passe
            password.addEventListener('input', function() {
                if (this.value.length < 8) {
                    this.setCustomValidity("Le mot de passe doit contenir au moins 8 caractères");
                } else {
                    this.setCustomValidity('');
                }
            });
        });
    </script>
</body>
</html>
