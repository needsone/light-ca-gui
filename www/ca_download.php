<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'includes/functions.php';

require_login();

$page_title = 'Télécharger CA - SSL Certificate Manager';

// Get CA info
$ca_info = get_ca_info();
$root_cert = get_ca_root_cert();
$intermediate_cert = get_ca_intermediate_cert();

// Handle file downloads
if (isset($_GET['file'])) {
    $file_type = $_GET['file'];
    $content = null;
    $filename = null;
    
    switch ($file_type) {
        case 'root':
            if (file_exists(STEP_CA_ROOT_CERT)) {
                $content = file_get_contents(STEP_CA_ROOT_CERT);
                $filename = 'root_ca.crt';
            }
            break;
            
        case 'intermediate':
            if (file_exists(STEP_CA_INTERMEDIATE_CERT)) {
                $content = file_get_contents(STEP_CA_INTERMEDIATE_CERT);
                $filename = 'intermediate_ca.crt';
            }
            break;
            
        case 'bundle':
            if (file_exists(STEP_CA_ROOT_CERT) && file_exists(STEP_CA_INTERMEDIATE_CERT)) {
                $content = file_get_contents(STEP_CA_INTERMEDIATE_CERT) . "\n" . file_get_contents(STEP_CA_ROOT_CERT);
                $filename = 'ca_bundle.pem';
            }
            break;
    }
    
    if ($content && $filename) {
        header('Content-Type: application/x-pem-file');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        
        log_message('INFO', "CA file downloaded", [
            'file_type' => $file_type,
            'filename' => $filename
        ]);
        
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-download"></i> Fichiers Certificate Authority</h1>
    <p>Téléchargez les certificats de votre CA pour les installer sur vos systèmes</p>
</div>

<?php if ($ca_info): ?>
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-info-circle"></i> Informations CA</h2>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-item">
                <strong>Nom de la CA :</strong>
                <span><?php echo htmlspecialchars($ca_info['name']); ?></span>
            </div>
            <div class="info-item">
                <strong>Adresse :</strong>
                <span><?php echo htmlspecialchars($ca_info['address']); ?></span>
            </div>
            <div class="info-item">
                <strong>DNS Names :</strong>
                <span><?php echo htmlspecialchars(implode(', ', $ca_info['dns_names'])); ?></span>
            </div>
            <div class="info-item">
                <strong>Provisioners :</strong>
                <span><?php echo $ca_info['provisioners']; ?></span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="download-grid">
    <div class="card download-card">
        <div class="card-header">
            <h2><i class="fas fa-certificate"></i> Certificat Root CA</h2>
        </div>
        <div class="card-body">
            <p class="card-description">
                Le certificat racine de votre Certificate Authority. 
                Installez-le dans le magasin de certificats de confiance de votre système.
            </p>
            
            <?php if ($root_cert): ?>
            <div class="cert-preview">
                <pre><?php echo htmlspecialchars(substr($root_cert, 0, 300)) . '...'; ?></pre>
            </div>
            
            <div class="download-actions">
                <a href="?file=root" class="btn btn-primary">
                    <i class="fas fa-download"></i> Télécharger Root CA
                </a>
                <button class="btn btn-secondary" onclick="copyToClipboard('root')">
                    <i class="fas fa-copy"></i> Copier
                </button>
            </div>
            
            <textarea id="root-cert" style="display: none;"><?php echo htmlspecialchars($root_cert); ?></textarea>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Certificat root non disponible
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card download-card">
        <div class="card-header">
            <h2><i class="fas fa-certificate"></i> Certificat Intermediate CA</h2>
        </div>
        <div class="card-body">
            <p class="card-description">
                Le certificat intermédiaire utilisé pour signer les certificats finaux. 
                Incluez-le dans la chaîne de certificats de vos serveurs.
            </p>
            
            <?php if ($intermediate_cert): ?>
            <div class="cert-preview">
                <pre><?php echo htmlspecialchars(substr($intermediate_cert, 0, 300)) . '...'; ?></pre>
            </div>
            
            <div class="download-actions">
                <a href="?file=intermediate" class="btn btn-primary">
                    <i class="fas fa-download"></i> Télécharger Intermediate CA
                </a>
                <button class="btn btn-secondary" onclick="copyToClipboard('intermediate')">
                    <i class="fas fa-copy"></i> Copier
                </button>
            </div>
            
            <textarea id="intermediate-cert" style="display: none;"><?php echo htmlspecialchars($intermediate_cert); ?></textarea>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Certificat intermediate non disponible
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card download-card">
        <div class="card-header">
            <h2><i class="fas fa-layer-group"></i> Bundle complet</h2>
        </div>
        <div class="card-body">
            <p class="card-description">
                Bundle contenant le certificat intermediate et root. 
                Utile pour configurer la chaîne de certificats complète.
            </p>
            
            <?php if ($root_cert && $intermediate_cert): ?>
            <div class="cert-preview">
                <pre><?php echo htmlspecialchars(substr($intermediate_cert . "\n" . $root_cert, 0, 300)) . '...'; ?></pre>
            </div>
            
            <div class="download-actions">
                <a href="?file=bundle" class="btn btn-primary">
                    <i class="fas fa-download"></i> Télécharger Bundle
                </a>
                <button class="btn btn-secondary" onclick="copyToClipboard('bundle')">
                    <i class="fas fa-copy"></i> Copier
                </button>
            </div>
            
            <textarea id="bundle-cert" style="display: none;"><?php echo htmlspecialchars($intermediate_cert . "\n" . $root_cert); ?></textarea>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Bundle non disponible
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card help-card">
    <div class="card-header">
        <h2><i class="fas fa-book"></i> Instructions d'installation</h2>
    </div>
    <div class="card-body">
        <div class="instructions">
            <div class="instruction-section">
                <h3><i class="fab fa-windows"></i> Windows</h3>
                <ol>
                    <li>Téléchargez le certificat Root CA</li>
                    <li>Double-cliquez sur le fichier .crt</li>
                    <li>Cliquez sur "Installer le certificat"</li>
                    <li>Sélectionnez "Ordinateur local"</li>
                    <li>Placez le certificat dans "Autorités de certification racines de confiance"</li>
                    <li>Validez l'installation</li>
                </ol>
            </div>
            
            <div class="instruction-section">
                <h3><i class="fab fa-linux"></i> Linux (Ubuntu/Debian)</h3>
                <pre><code>sudo cp root_ca.crt /usr/local/share/ca-certificates/
sudo update-ca-certificates</code></pre>
            </div>
            
            <div class="instruction-section">
                <h3><i class="fab fa-linux"></i> Linux (RedHat/CentOS)</h3>
                <pre><code>sudo cp root_ca.crt /etc/pki/ca-trust/source/anchors/
sudo update-ca-trust</code></pre>
            </div>
            
            <div class="instruction-section">
                <h3><i class="fab fa-apple"></i> macOS</h3>
                <ol>
                    <li>Téléchargez le certificat Root CA</li>
                    <li>Double-cliquez sur le fichier .crt</li>
                    <li>Ouvrez "Trousseaux d'accès"</li>
                    <li>Trouvez le certificat dans "Système"</li>
                    <li>Double-cliquez dessus et définissez "Toujours faire confiance"</li>
                </ol>
            </div>
            
            <div class="instruction-section">
                <h3><i class="fab fa-firefox"></i> Firefox</h3>
                <ol>
                    <li>Ouvrez Préférences → Vie privée et sécurité</li>
                    <li>Descendez à "Certificats" → "Afficher les certificats"</li>
                    <li>Onglet "Autorités" → "Importer"</li>
                    <li>Sélectionnez le certificat Root CA</li>
                    <li>Cochez "Faire confiance à cette autorité pour identifier des sites web"</li>
                </ol>
            </div>
            
            <div class="instruction-section">
                <h3><i class="fas fa-server"></i> Configuration serveur web</h3>
                <p><strong>Apache :</strong></p>
                <pre><code>SSLCertificateFile /path/to/cert.crt
SSLCertificateKeyFile /path/to/cert.key
SSLCertificateChainFile /path/to/ca_bundle.pem</code></pre>
                
                <p><strong>Nginx :</strong></p>
                <pre><code>ssl_certificate /path/to/chain.pem;
ssl_certificate_key /path/to/cert.key;</code></pre>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(type) {
    const textArea = document.getElementById(type + '-cert');
    if (!textArea) return;
    
    textArea.style.display = 'block';
    textArea.select();
    document.execCommand('copy');
    textArea.style.display = 'none';
    
    // Show feedback
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Copié !';
    btn.classList.add('btn-success');
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.remove('btn-success');
    }, 2000);
}
</script>

<?php require_once 'includes/footer.php'; ?>
