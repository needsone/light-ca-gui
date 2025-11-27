<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = $page_title ?? 'SSL Certificate Manager';
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
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <i class="fas fa-certificate"></i>
                <span>SSL Certificate Manager</span>
            </div>
            
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <ul class="navbar-menu">
                <li><a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Accueil
                </a></li>
                <li><a href="ca_download.php" class="<?php echo $current_page === 'ca_download.php' ? 'active' : ''; ?>">
                    <i class="fas fa-download"></i> CA Files
                </a></li>
                <li><a href="cert_create.php" class="<?php echo $current_page === 'cert_create.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i> Créer Certificat
                </a></li>
                <li><a href="users_manage.php" class="<?php echo $current_page === 'users_manage.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Utilisateurs
                </a></li>
            </ul>
            
            <div class="navbar-user">
                <span class="user-info">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username']); ?>
                    <?php if (isset($_SESSION['auth_method']) && $_SESSION['auth_method'] === 'active_directory'): ?>
                        <span class="badge badge-ad">AD</span>
                    <?php endif; ?>
                </span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    
    <main class="main-content">
        <div class="container">
