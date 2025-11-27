<?php
/**
 * SSL Certificate Manager - Configuration
 */

// Timezone
date_default_timezone_set('Europe/Paris');

// ===========================
// Paths
// ===========================
define('BASE_DIR', __DIR__);
define('DATA_DIR', BASE_DIR . '/data');
define('CERTS_DIR', DATA_DIR . '/certificates');
define('LOGS_DIR', BASE_DIR . '/logs');
define('PASSWORD_FILE', DATA_DIR . '/.password');

// step-ca paths
define('STEP_CA_PATH', '/var/step-ca');
define('STEP_CA_CONFIG', STEP_CA_PATH . '/config/ca.json');
define('STEP_CA_ROOT_CERT', STEP_CA_PATH . '/certs/root_ca.crt');
define('STEP_CA_INTERMEDIATE_CERT', STEP_CA_PATH . '/certs/intermediate_ca.crt');

// ===========================
// Application Settings
// ===========================
define('APP_NAME', 'SSL Certificate Manager');
define('APP_VERSION', '1.0.0');
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// ===========================
// Certificate Defaults
// ===========================
define('DEFAULT_CERT_VALIDITY', 365); // days
define('DEFAULT_KEY_SIZE', 2048);
define('SUPPORTED_KEY_SIZES', [2048, 3072, 4096]);

// ===========================
// Active Directory (optionnel)
// ===========================
define('AD_ENABLED', getenv('AD_ENABLED') === 'true');
define('AD_SERVER', getenv('AD_SERVER') ?: 'ldap://dc.example.com');
define('AD_PORT', getenv('AD_PORT') ?: 389);
define('AD_DOMAIN', getenv('AD_DOMAIN') ?: 'EXAMPLE');
define('AD_BASE_DN', getenv('AD_BASE_DN') ?: 'DC=example,DC=com');
define('AD_USE_TLS', getenv('AD_USE_TLS') === 'true');

// ===========================
// Security
// ===========================
define('ENABLE_CSRF', true);
define('PASSWORD_MIN_LENGTH', 8);

// ===========================
// Logging
// ===========================
define('ENABLE_LOGGING', true);
define('LOG_FILE', LOGS_DIR . '/app.log');

// Create necessary directories
$directories = [DATA_DIR, CERTS_DIR, LOGS_DIR];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
}

// Create default password file if it doesn't exist
if (!file_exists(PASSWORD_FILE)) {
    // Default: admin / admin123
    $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
    file_put_contents(PASSWORD_FILE, "admin:$defaultPassword\n");
    chmod(PASSWORD_FILE, 0600);
}
