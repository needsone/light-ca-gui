#!/bin/bash
set -e

echo "🚀 Starting SSL Certificate Manager (Alpine)..."

# Créer les répertoires nécessaires
mkdir -p /var/www/localhost/htdocs/ssl-manager/data/certificates
mkdir -p /var/www/localhost/htdocs/ssl-manager/logs
mkdir -p /var/step-ca
mkdir -p /run/apache2

# Définir les permissions
chown -R www-data:www-data /var/www/localhost/htdocs/ssl-manager
chown -R www-data:www-data /var/step-ca
chmod -R 755 /var/www/localhost/htdocs/ssl-manager
chmod -R 750 /var/step-ca

# Créer le fichier .password s'il n'existe pas
if [ ! -f /var/www/localhost/htdocs/ssl-manager/data/.password ]; then
    echo "📝 Creating default .password file..."
    # Créer un utilisateur admin par défaut
    # Format: username:password_hash
    # Password par défaut: admin123
    echo 'admin:$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' > /var/www/localhost/htdocs/ssl-manager/data/.password
    chown www-data:www-data /var/www/localhost/htdocs/ssl-manager/data/.password
    chmod 600 /var/www/localhost/htdocs/ssl-manager/data/.password
    echo "✅ Default admin user created (username: admin, password: admin123)"
fi

# Initialiser step-ca si nécessaire
if [ ! -f /var/step-ca/config/ca.json ]; then
    echo "🔐 Initializing Certificate Authority..."
    
    # Variables d'environnement avec valeurs par défaut
    CA_NAME="${CA_NAME:-My Certificate Authority}"
    CA_DNS="${CA_DNS:-ca.example.com}"
    CA_ADDRESS="${CA_ADDRESS:-:9000}"
    CA_PROVISIONER="${CA_PROVISIONER:-admin}"
    CA_PROVISIONER_PASSWORD="${CA_PROVISIONER_PASSWORD:-changeme}"
    
    # Créer un fichier de mot de passe dans le répertoire step-ca
    PASS_FILE="/var/step-ca/.password_init"
    echo "$CA_PROVISIONER_PASSWORD" > "$PASS_FILE"
    chown www-data:www-data "$PASS_FILE"
    chmod 600 "$PASS_FILE"
    
    # Initialiser step-ca en tant que www-data
    sudo -u www-data STEPPATH=/var/step-ca step ca init \
        --name="$CA_NAME" \
        --dns="$CA_DNS" \
        --address="$CA_ADDRESS" \
        --provisioner="$CA_PROVISIONER" \
        --password-file="$PASS_FILE" \
        --deployment-type=standalone
    
    # Nettoyer le fichier temporaire
    rm -f "$PASS_FILE"
    
    echo "✅ Certificate Authority initialized successfully!"
    echo "📋 CA Name: $CA_NAME"
    echo "🌐 CA DNS: $CA_DNS"
    echo "🔑 Provisioner: $CA_PROVISIONER"
else
    echo "✅ Certificate Authority already initialized"
fi

# Démarrer step-ca en arrière-plan
echo "🚀 Starting step-ca server..."

# Créer un fichier de mot de passe pour step-ca runtime
PASS_FILE="/var/step-ca/.password_runtime"
echo "${CA_PROVISIONER_PASSWORD:-changeme}" > "$PASS_FILE"
chown www-data:www-data "$PASS_FILE"
chmod 600 "$PASS_FILE"

# Démarrer step-ca en arrière-plan
sudo -u www-data STEPPATH=/var/step-ca nohup step-ca /var/step-ca/config/ca.json \
    --password-file="$PASS_FILE" > /var/log/step-ca.log 2>&1 &

# Attendre que step-ca démarre
sleep 3

# Vérifier si step-ca est en cours d'exécution
if pgrep -x "step-ca" > /dev/null; then
    echo "✅ step-ca is running (PID: $(pgrep step-ca))"
else
    echo "⚠️ Warning: step-ca might not be running properly"
    echo "📋 Check logs: tail -f /var/log/step-ca.log"
fi

echo "🌐 Starting Apache..."

# Exécuter la commande passée en argument (Apache)
exec "$@"
