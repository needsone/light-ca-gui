<?php
require_once 'config.php';
require_once 'auth.php';

require_login();

$page_title = 'Gestion des utilisateurs - SSL Certificate Manager';

$success = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Le nom d\'utilisateur et le mot de passe sont obligatoires.';
        } elseif (strlen($username) < 3) {
            $error = 'Le nom d\'utilisateur doit contenir au moins 3 caractères.';
        } elseif (strlen($password) < 8) {
            $error = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($password !== $password_confirm) {
            $error = 'Les mots de passe ne correspondent pas.';
        } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
            $error = 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, points, tirets et underscores.';
        } else {
            if (save_user($username, $password)) {
                $success = "Utilisateur '$username' ajouté/mis à jour avec succès.";
            } else {
                $error = 'Erreur lors de l\'ajout de l\'utilisateur.';
            }
        }
    } elseif ($action === 'delete') {
        $username = $_POST['username'] ?? '';
        
        if (empty($username)) {
            $error = 'Nom d\'utilisateur manquant.';
        } elseif ($username === $_SESSION['username']) {
            $error = 'Vous ne pouvez pas supprimer votre propre compte.';
        } elseif ($username === 'admin') {
            $error = 'Le compte admin ne peut pas être supprimé.';
        } else {
            if (delete_user($username)) {
                $success = "Utilisateur '$username' supprimé avec succès.";
            } else {
                $error = 'Erreur lors de la suppression de l\'utilisateur.';
            }
        }
    }
}

// Get all users
$users = get_all_users();

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-users"></i> Gestion des utilisateurs</h1>
    <p>Gérez les utilisateurs locaux autorisés à accéder à l'application</p>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if (AD_ENABLED): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <strong>Note :</strong> L'authentification Active Directory est activée. 
    Les utilisateurs AD peuvent se connecter directement. Les utilisateurs locaux ci-dessous 
    servent de solution de secours.
</div>
<?php endif; ?>

<div class="users-grid">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-plus"></i> Ajouter un utilisateur</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="users_manage.php" class="user-form">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="username" class="required">
                        <i class="fas fa-user"></i> Nom d'utilisateur
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        pattern="[a-zA-Z0-9_.-]+"
                        minlength="3"
                        placeholder="johndoe"
                        autocomplete="off"
                    >
                    <small>Minimum 3 caractères, lettres, chiffres, points, tirets et underscores uniquement</small>
                </div>
                
                <div class="form-group">
                    <label for="password" class="required">
                        <i class="fas fa-lock"></i> Mot de passe
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >
                    <small>Minimum 8 caractères</small>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm" class="required">
                        <i class="fas fa-lock"></i> Confirmer le mot de passe
                    </label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Ajouter l'utilisateur
                </button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-list"></i> Utilisateurs locaux</h2>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>Aucun utilisateur local configuré.</p>
            </div>
            <?php else: ?>
            <div class="users-list">
                <?php foreach ($users as $user): ?>
                <div class="user-item">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <strong><?php echo htmlspecialchars($user); ?></strong>
                        <?php if ($user === $_SESSION['username']): ?>
                        <span class="badge badge-primary">Vous</span>
                        <?php endif; ?>
                        <?php if ($user === 'admin'): ?>
                        <span class="badge badge-warning">Admin</span>
                        <?php endif; ?>
                    </div>
                    <div class="user-actions">
                        <?php if ($user !== $_SESSION['username'] && $user !== 'admin'): ?>
                        <form method="POST" style="display: inline;" 
                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer l\'utilisateur <?php echo htmlspecialchars($user); ?> ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($user); ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="users-stats">
                <p><strong><?php echo count($users); ?></strong> utilisateur(s) local/locaux</p>
            </div>
        </div>
    </div>
</div>

<div class="card help-card">
    <div class="card-header">
        <h2><i class="fas fa-terminal"></i> Ligne de commande</h2>
    </div>
    <div class="card-body">
        <p>Vous pouvez également ajouter des utilisateurs via la ligne de commande :</p>
        <pre><code>docker exec -it ssl-certificate-manager php /scripts/add_user.php &lt;username&gt; &lt;password&gt;</code></pre>
        
        <p><strong>Exemple :</strong></p>
        <pre><code>docker exec -it ssl-certificate-manager php /scripts/add_user.php john MySecurePass123</code></pre>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
