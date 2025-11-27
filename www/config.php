<?php
/**
 * SSL Certificate Manager - Configuration
 */

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
// Session Configuration
// ===========================
define('SESSION_TIMEOUT', getenv('SESSION_TIMEOUT') ?: 1800); // 30 minutes
define('SESSION_NAME', 'SSL_CERT_MANAGER_SESSION');

// ===========================
// Active Directory Configuration
// ===========================
define('AD_ENABLED', filter_var(getenv('AD_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('AD_SERVER', getenv('AD_SERVER') ?: 'ldap://dc.example.com');
define('AD_PORT', (int)(getenv('AD_PORT') ?: 389));
define('AD_DOMAIN', getenv('AD_DOMAIN') ?: 'EXAMPLE');
define('AD_BASE_DN', getenv('AD_BASE_DN') ?: 'DC=example,DC=com');
define('AD_USE_TLS', filter_var(getenv('AD_USE_TLS') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('AD_TIMEOUT', 10);

// AD Group restrictions (comma-separated)
$ad_groups_env = getenv('AD_REQUIRED_GROUPS') ?: '';
define('AD_REQUIRED_GROUPS', !empty($ad_groups_env) ? explode(',', $ad_groups_env) : []);

// ===========================
// Certificate Configuration
// ===========================
define('DEFAULT_CERT_VALIDITY_DAYS', (int)(getenv('DEFAULT_CERT_VALIDITY_DAYS') ?: 365));
define('CA_PROVISIONER', getenv('CA_PROVISIONER') ?: 'admin');
define('CA_PROVISIONER_PASSWORD', getenv('CA_PROVISIONER_PASSWORD') ?: 'changeme');

// ===========================
// Logging Configuration
// ===========================
define('LOG_FILE', LOGS_DIR . '/app.log');
define('MAX_LOG_SIZE', 10 * 1024 * 1024); // 10 MB

// ===========================
// Security Headers
// ===========================
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ===========================
// Initialize Directories
// ===========================
function init_directories() {
    $dirs = [DATA_DIR, CERTS_DIR, LOGS_DIR];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Créer le fichier .password s'il n'existe pas
    if (!file_exists(PASSWORD_FILE)) {
        // Utilisateur admin par défaut (password: admin123)
        $default_user = 'admin:$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        file_put_contents(PASSWORD_FILE, $default_user . PHP_EOL);
        chmod(PASSWORD_FILE, 0600);
    }
}

// ===========================
// Logging Function
// ===========================
function log_message($level, $message, $context = []) {
    init_directories();
    
    // Rotate log if too large
    if (file_exists(LOG_FILE) && filesize(LOG_FILE) > MAX_LOG_SIZE) {
        $backup = LOG_FILE . '.' . date('Y-m-d_His') . '.old';
        rename(LOG_FILE, $backup);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $user = $_SESSION['username'] ?? 'anonymous';
    
    $context_str = !empty($context) ? ' | ' . json_encode($context) : '';
    $log_entry = "[$timestamp] [$level] [$ip] [$user] $message$context_str" . PHP_EOL;
    
    file_put_contents(LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
}

// ===========================
// Utility Functions
// ===========================
function sanitize_filename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    return trim($filename, '_');
}

function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Initialize on load
init_directories();
