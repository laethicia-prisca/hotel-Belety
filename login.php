<?php
require_once 'includes/config.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        setErrorMessage('Veuillez remplir tous les champs');
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // Mise à jour de la dernière connexion
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                setSuccessMessage('Connexion réussie ! Bienvenue ' . $user['full_name']);
                header('Location: index.php');
                exit;
            } else {
                setErrorMessage('Identifiant ou mot de passe incorrect');
            }
        } catch (PDOException $e) {
            setErrorMessage('Erreur de connexion : ' . $e->getMessage());
        }
    }
}

$page_title = 'Connexion';
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
        /* Styles spécifiques à la page de connexion */
        body.auth-page {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            padding: 2rem;
        }
        
        .auth-container {
            max-width: 420px;
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
            margin-bottom: 1.25rem;
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
            width: 100%;
            padding: 0.625rem 0.875rem 0.625rem 2.75rem;
            font-size: 0.9375rem;
            line-height: 1.5;
            color: var(--dark-text);
            background-color: var(--white);
            background-clip: padding-box;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }
        
        .btn-block {
            display: block;
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: var(--radius);
            margin-top: 1.5rem;
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
        
        /* Animation pour le bouton */
        .btn-primary {
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
            cursor: pointer;
            z-index: 10;
        }
        
        .test-accounts {
            background-color: #f8fafc;
            border-radius: var(--radius);
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            border: 1px dashed var(--border-color);
        }
        
        .test-accounts p {
            margin: 0 0 0.5rem 0;
            font-weight: 500;
            color: var(--dark-text);
        }
        
        .test-accounts ul {
            margin: 0;
            padding-left: 1.25rem;
        }
        
        .test-accounts li {
            margin-bottom: 0.25rem;
        }
        
        .test-accounts strong {
            color: var(--primary-color);
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-hotel"></i>
                    <h1>Système de Gestion d'Hôtel</h1>
                </div>
                <p>Connectez-vous pour accéder à votre espace</p>
            </div>
            
            <?php displayMessages(); ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur ou Email</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control" 
                               placeholder="Entrez votre identifiant"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               required
                               autofocus>
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
                               placeholder="Entrez votre mot de passe"
                               required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
                
                <div class="auth-footer">
                    <p>Vous n'avez pas de compte ? <a href="register.php?code=admin_invite_2024">Créer un compte administrateur</a></p>
                </div>
                
                <div class="test-accounts">
                    <p>Comptes de test :</p>
                    <ul>
                        <li><strong>Admin:</strong> admin / admin123</li>
                        <li><strong>Réceptionniste:</strong> receptionist / admin123</li>
                    </ul>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Script pour afficher/masquer le mot de passe
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            
            if (passwordInput) {
                const toggle = document.createElement('span');
                toggle.className = 'password-toggle';
                toggle.innerHTML = '<i class="far fa-eye"></i>';
                
                passwordInput.parentNode.style.position = 'relative';
                passwordInput.parentNode.appendChild(toggle);
                
                toggle.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="far fa-eye"></i>' : '<i class="far fa-eye-slash"></i>';
                });
            }
        });
    </script>
</body>
</html>
            </div>
        </div>
    </div>
</body>
</html>
