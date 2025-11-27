<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .root-ca-section {
            background: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .root-ca-section h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .root-ca-section p {
            color: #666;
            font-size: 13px;
            margin-bottom: 15px;
        }
        
        .btn-root-ca {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-root-ca:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .download-links {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .download-links a {
            display: inline-block;
            margin: 5px 10px 5px 0;
            padding: 8px 16px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .download-links a:hover {
            background: #5568d3;
        }
        
        .icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 30px;
        }
        
        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 30px 0;
        }
        
        .config-status {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔒</div>
        <h1><?= APP_NAME ?></h1>
        <p class="subtitle"><?= APP_SUBTITLE ?></p>
        
        <?php
        // Vérifier la configuration
        $configErrors = checkConfiguration();
        if (!empty($configErrors)) {
            echo '<div class="config-status">';
            echo '<strong>⚠️ Configuration incomplète :</strong><br>';
            foreach ($configErrors as $error) {
                echo '• ' . htmlspecialchars($error) . '<br>';
            }
            echo '</div>';
        }
        ?>
        
        <!-- Section Root CA -->
        <div class="root-ca-section">
            <h3>📜 Certificat Root CA</h3>
            <p>Téléchargez et installez ce certificat sur vos machines</p>
            <a href="root-ca.php" class="btn-root-ca">⬇️ Télécharger Root CA</a>
        </div>
        
        <div class="divider"></div>
        
        <?php
        if (isset($_GET['success']) && $_GET['success'] == '1') {
            $hostname = htmlspecialchars($_GET['hostname']);
            echo '<div class="alert alert-success">';
            echo '✓ Certificat généré avec succès pour <strong>' . $hostname . '</strong>';
            echo '<div class="download-links">';
            echo '<a href="download.php?file=' . urlencode($hostname) . '.crt">📥 Certificat</a>';
            echo '<a href="download.php?file=' . urlencode($hostname) . '.key">📥 Clé privée</a>';
            echo '<a href="download.php?file=' . urlencode($hostname) . '.bundle.crt">📥 Bundle complet</a>';
            echo '</div>';
            echo '</div>';
        }
        
        if (isset($_GET['error'])) {
            echo '<div class="alert alert-error">';
            echo '✗ Erreur : ' . htmlspecialchars($_GET['error']);
            echo '</div>';
        }
        ?>
        
        <form action="generate.php" method="POST">
            <div class="form-group">
                <label for="hostname">Nom d'hôte (FQDN)</label>
                <input 
                    type="text" 
                    id="hostname" 
                    name="hostname" 
                    placeholder="serveur.exemple.local" 
                    required
                    pattern="[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*"
                    title="Entrez un nom de domaine valide"
                >
                <div class="help-text">
                    Exemple : serveur.exemple.local ou api.mondomaine.com
                </div>
            </div>
            
            <button type="submit">🔐 Générer le certificat</button>
        </form>
    </div>
</body>
</html>
