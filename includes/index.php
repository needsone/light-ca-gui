<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'includes/functions.php';

require_login();

$page_title = 'Tableau de bord - SSL Certificate Manager';

// Get CA info
$ca_info = get_ca_info();

// Get certificates list
$certificates = list_certificates();
$total_certs = count($certificates);
$expired_certs = count(array_filter($certificates, fn($cert) => $cert['is_expired']));
$active_certs = $total_certs - $expired_certs;

// Handle certificate deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $directory = $_POST['directory'] ?? '';
    
    if (!empty($directory) && delete_certificate($directory)) {
        header('Location: index.php?deleted=1');
        exit;
    } else {
        $error = 'Erreur lors de la suppression du certificat.';
    }
}

// Handle certificate download
if (isset($_GET['download'])) {
    $directory = $_GET['download'];
    $zip_file = create_certificate_zip($directory);
    
    if ($zip_file && file_exists($zip_file)) {
        $info_file = CERTS_DIR . '/' . basename($directory) . '/info.json';
        $filename = 'certificate_' . basename($directory) . '.zip';
        
        if (file_exists($info_file)) {
            $info = json_decode(file_get_contents($info_file), true);
            $safe_cn = sanitize_filename($info['common_name']);
            $filename = $safe_cn . '_' . date('Y-m-d') . '.zip';
        }
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($zip_file));
        
        readfile($zip_file);
        unlink($zip_file);
        exit;
    }
}

require_once 'includes/header.php';
?>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    Certificat supprimé avec succès.
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="dashboard">
    <h1><i class="fas fa-tachometer-alt"></i> Tableau de bord</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #4CAF50;">
                <i class="fas fa-certificate"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $active_certs; ?></h3>
                <p>Certificats actifs</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #FF9800;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $expired_certs; ?></h3>
                <p>Certificats expirés</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #2196F3;">
                <i class="fas fa-list"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $total_certs; ?></h3>
                <p>Total certificats</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #9C27B0;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $ca_info ? 'OK' : 'N/A'; ?></h3>
                <p>Certificate Authority</p>
            </div>
        </div>
    </div>
    
    <?php if ($ca_info): ?>
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-info-circle"></i> Informations CA</h2>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <strong>Nom :</strong>
                    <span><?php echo htmlspecialchars($ca_info['name']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Adresse :</strong>
                    <span><?php echo htmlspecialchars($ca_info['address']); ?></span>
                </div>
                <div class="info-item">
                    <strong>DNS Names :</strong>
                    <span><?php echo implode(', ', $ca_info['dns_names']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Provisioners :</strong>
                    <span><?php echo $ca_info['provisioners']; ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-list"></i> Certificats récents</h2>
            <a href="cert_create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau certificat
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($certificates)): ?>
            <div class="empty-state">
                <i class="fas fa-certificate"></i>
                <p>Aucun certificat créé pour le moment.</p>
                <a href="cert_create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Créer le premier certificat
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Common Name</th>
                            <th>DNS Names</th>
                            <th>Créé le</th>
                            <th>Expire le</th>
                            <th>Créé par</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($certificates, 0, 10) as $cert): ?>
                        <tr class="<?php echo $cert['is_expired'] ? 'expired-row' : ''; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($cert['common_name']); ?></strong>
                            </td>
                            <td>
                                <?php 
                                $dns_names = array_slice($cert['dns_names'], 0, 2);
                                echo htmlspecialchars(implode(', ', $dns_names));
                                if (count($cert['dns_names']) > 2) {
                                    echo ' <span class="badge">+' . (count($cert['dns_names']) - 2) . '</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($cert['created_at'])); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($cert['expires_at'])); ?></td>
                            <td><?php echo htmlspecialchars($cert['created_by']); ?></td>
                            <td>
                                <?php if ($cert['is_expired']): ?>
                                <span class="badge badge-error">
                                    <i class="fas fa-times-circle"></i> Expiré
                                </span>
                                <?php else: ?>
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle"></i> Actif
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?download=<?php echo urlencode($cert['directory']); ?>" 
                                       class="btn btn-sm btn-primary"
                                       title="Télécharger">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce certificat ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="directory" value="<?php echo htmlspecialchars($cert['directory']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (count($certificates) > 10): ?>
            <div class="text-center" style="margin-top: 20px;">
                <p>Affichage de 10 sur <?php echo count($certificates); ?> certificats</p>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
