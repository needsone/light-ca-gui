<?php
session_name(SESSION_NAME);
session_start();

require_once 'config.php';
require_once 'auth.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez saisir un nom d\'utilisateur et un mot de passe.';
    } else {
        $user = authenticate_user($username, $password);
        
        if ($user !== false) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['display_name'] = $user['display_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['auth_method'] = $user['auth_method'];
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time'] = time();
            
            log_message('INFO', "User logged in successfully", [
                'username' => $user['username'],
                'auth_method' => $user['auth_method']
            ]);
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
            log_message('WARNING', "Failed login attempt", ['username' => $username]);
        }
    }
}

$page_title = 'Connexion - SSL Certificate Manager';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-certificate"></i>
                <h1>SSL Certificate Manager</h1>
                <p>Powered by step-ca</p>
            </div>
            
            <?php if ($timeout): ?>
            <div class="alert alert-warning">
                <i class="fas fa-clock"></i>
                Votre session a expiré. Veuillez vous reconnecter.
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Nom d'utilisateur
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autofocus
                        autocomplete="username"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Mot de passe
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>
            
            <div class="login-info">
                <?php if (AD_ENABLED): ?>
                <p class="ad-enabled">
                    <i class="fas fa-shield-alt"></i>
                    Authentification Active Directory activée
                </p>
                <?php endif; ?>
                <p class="default-credentials">
                    <i class="fas fa-info-circle"></i>
                    <strong>Identifiants par défaut :</strong> admin / admin123
                </p>
            </div>
        </div>
        
        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> SSL Certificate Manager</p>
        </div>
    </div>
</body>
</html>
