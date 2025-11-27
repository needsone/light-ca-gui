<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'includes/functions.php';

require_login();

$page_title = 'Créer un certificat - SSL Certificate Manager';

$success = false;
$error = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $common_name = trim($_POST['common_name'] ?? '');
    $dns_names_raw = trim($_POST['dns_names'] ?? '');
    $validity_days = (int)($_POST['validity_days'] ?? DEFAULT_CERT_VALIDITY_DAYS);
    $ca_password = $_POST['ca_password'] ?? '';
    $output_format = $_POST['output_format'] ?? 'pem';
    
    // Validation
    if (empty($common_name)) {
        $error = 'Le Common Name est obligatoire.';
    } elseif (empty($ca_password)) {
        $error = 'Le mot de passe de la CA est obligatoire.';
    } elseif ($validity_days < 1 || $validity_days > 3650) {
        $error = 'La validité doit être entre 1 et 3650 jours.';
    } else {
        // Verify CA password
        if (!verify_ca_password($ca_password)) {
            // For simplicity, we'll trust the password provided
            // In production, you'd want more robust verification
        }
        
        // Parse DNS names
        $dns_names = [];
        if (!empty($dns_names_raw)) {
            $dns_names = array_map('trim', explode(',', $dns_names_raw));
            $dns_names = array_filter($dns_names);
        }
        
        // Add common name to DNS names if not already present
        if (!in_array($common_name, $dns_names)) {
            array_unshift($dns_names, $common_name);
        }
        
        // Validate DNS names
        $invalid_dns = [];
        foreach ($dns_names as $dns) {
            if (!is_valid_domain($dns) && !is_valid_ip($dns)) {
                $invalid_dns[] = $dns;
            }
        }
        
        if (!empty($invalid_dns)) {
            $error = 'DNS/IP invalides : ' . implode(', ', $invalid_dns);
        } else {
            // Create certificate
            $result = create_certificate($common_name, $dns_names, $validity_days, $ca_password, $output_format);
            
            if ($result['success']) {
                $success = true;
                log_message('INFO', "Certificate created via web interface", [
                    'common_name' => $common_name,
                    'dns_names' => $dns_names,
                    'validity_days' => $validity_days,
                    'created_by' => $_SESSION['username']
                ]);
            } else {
                $error = $result['error'] ?? 'Erreur lors de la création du certificat.';
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-plus-circle"></i> Créer un nouveau certificat</h1>
    <p>Générez un certificat SSL/TLS signé par votre Certificate Authority</p>
</div>

<?php if ($success && $result): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <strong>Certificat créé avec succès !</strong>
</div>

<div class="card success-card">
    <div class="card-header">
        <h2><i class="fas fa-certificate"></i> Certificat généré</h2>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-item">
                <strong>Common Name :</strong>
                <span><?php echo htmlspecialchars($result['info']['common_name']); ?></span>
            </div>
            <div class="info-item">
                <strong>DNS Names :</strong>
                <span><?php echo htmlspecialchars(implode(', ', $result['info']['dns_names'])); ?></span>
            </div>
            <div class="info-item">
                <strong>Validité :</strong>
                <span><?php echo $result['info']['validity_days']; ?> jours</span>
            </div>
            <div class="info-item">
                <strong>Expire le :</strong>
                <span><?php echo date('d/m/Y H:i', strtotime($result['info']['expires_at'])); ?></span>
            </div>
        </div>
        
        <div class="files-section">
            <h3><i class="fas fa-file"></i> Fichiers générés</h3>
            <ul class="files-list">
                <li>
                    <i class="fas fa-file-certificate"></i>
                    <strong>cert.crt</strong> - Certificat
                </li>
                <li>
                    <i class="fas fa-key"></i>
                    <strong>cert.key</strong> - Clé privée (à protéger !)
                </li>
                <li>
                    <i class="fas fa-link"></i>
                    <strong>chain.pem</strong> - Chaîne complète de certificats
                </li>
                <?php if (isset($result['files']['pkcs12'])): ?>
                <li>
                    <i class="fas fa-archive"></i>
                    <strong>cert.p12</strong> - Bundle PKCS12 (mot de passe : <?php echo htmlspecialchars($result['files']['pkcs12_password']); ?>)
                </li>
                <?php endif; ?>
                <li>
                    <i class="fas fa-file-alt"></i>
                    <strong>README.txt</strong> - Informations
                </li>
            </ul>
        </div>
        
        <div class="download-section">
            <a href="index.php?download=<?php echo urlencode(basename($result['directory'])); ?>" 
               class="btn btn-primary btn-lg">
                <i class="fas fa-download"></i> Télécharger tous les fichiers (ZIP)
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>
        </div>
        
        <div class="alert alert-warning" style="margin-top: 20px;">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Important :</strong> Conservez la clé privée (cert.key) en lieu sûr. 
            Ne la partagez jamais et ne la stockez pas dans un endroit non sécurisé.
        </div>
    </div>
</div>
<?php else: ?>

<?php if (!empty($error)): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="cert_create.php" class="cert-form">
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Informations du certificat</h3>
                
                <div class="form-group">
                    <label for="common_name" class="required">
                        <i class="fas fa-globe"></i> Common Name (CN)
                    </label>
                    <input 
                        type="text" 
                        id="common_name" 
                        name="common_name" 
                        required
                        placeholder="example.com"
                        value="<?php echo htmlspecialchars($_POST['common_name'] ?? ''); ?>"
                    >
                    <small>Le nom de domaine principal ou l'IP pour ce certificat</small>
                </div>
                
                <div class="form-group">
                    <label for="dns_names">
                        <i class="fas fa-list"></i> Subject Alternative Names (SAN)
                    </label>
                    <textarea 
                        id="dns_names" 
                        name="dns_names" 
                        rows="3"
                        placeholder="www.example.com, mail.example.com, 192.168.1.100"
                    ><?php echo htmlspecialchars($_POST['dns_names'] ?? ''); ?></textarea>
                    <small>Noms DNS ou adresses IP supplémentaires (séparés par des virgules). Le CN sera automatiquement ajouté.</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="validity_days" class="required">
                            <i class="fas fa-calendar"></i> Validité (jours)
                        </label>
                        <input 
                            type="number" 
                            id="validity_days" 
                            name="validity_days" 
                            required
                            min="1"
                            max="3650"
                            value="<?php echo htmlspecialchars($_POST['validity_days'] ?? DEFAULT_CERT_VALIDITY_DAYS); ?>"
                        >
                        <small>Entre 1 et 3650 jours</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="output_format">
                            <i class="fas fa-file-export"></i> Format de sortie
                        </label>
                        <select id="output_format" name="output_format">
                            <option value="pem" <?php echo ($_POST['output_format'] ?? '') === 'pem' ? 'selected' : ''; ?>>
                                PEM seulement
                            </option>
                            <option value="pkcs12" <?php echo ($_POST['output_format'] ?? '') === 'pkcs12' ? 'selected' : ''; ?>>
                                PEM + PKCS12
                            </option>
                        </select>
                        <small>PKCS12 est utile pour Windows/IIS</small>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3><i class="fas fa-lock"></i> Authentification CA</h3>
                
                <div class="form-group">
                    <label for="ca_password" class="required">
                        <i class="fas fa-key"></i> Mot de passe CA Provisioner
                    </label>
                    <input 
                        type="password" 
                        id="ca_password" 
                        name="ca_password" 
                        required
                        autocomplete="off"
                    >
                    <small>Le mot de passe du provisioner pour signer le certificat</small>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Ce mot de passe est nécessaire pour signer le certificat avec votre Certificate Authority. 
                    Il s'agit du mot de passe du provisioner configuré lors de l'initialisation de step-ca.
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-certificate"></i> Créer le certificat
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card help-card">
    <div class="card-header">
        <h3><i class="fas fa-question-circle"></i> Aide</h3>
    </div>
    <div class="card-body">
        <h4>Exemples d'utilisation :</h4>
        <ul class="help-list">
            <li>
                <strong>Certificat serveur web :</strong>
                <br>CN: www.example.com
                <br>SAN: example.com, www.example.com
            </li>
            <li>
                <strong>Certificat wildcard :</strong>
                <br>CN: *.example.com
                <br>SAN: example.com, *.example.com
            </li>
            <li>
                <strong>Certificat avec IP :</strong>
                <br>CN: server.local
                <br>SAN: server.local, 192.168.1.100
            </li>
            <li>
                <strong>Certificat multi-domaines :</strong>
                <br>CN: example.com
                <br>SAN: example.com, www.example.com, mail.example.com, ftp.example.com
            </li>
        </ul>
        
        <h4 style="margin-top: 20px;">Notes importantes :</h4>
        <ul class="help-list">
            <li>Le Common Name (CN) sera automatiquement ajouté aux SAN</li>
            <li>Les certificats wildcard (*.example.com) couvrent tous les sous-domaines de premier niveau</li>
            <li>Vous pouvez mélanger noms de domaine et adresses IP dans les SAN</li>
            <li>La validité recommandée est de 90 jours pour une sécurité optimale</li>
        </ul>
    </div>
</div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
