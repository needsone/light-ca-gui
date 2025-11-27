#!/usr/bin/env php
<?php
/**
 * Script pour ajouter un utilisateur au fichier .password
 * Usage: php add_user.php username password
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line\n");
}

if ($argc < 3) {
    echo "Usage: php add_user.php <username> <password>\n";
    echo "Example: php add_user.php john MySecurePass123\n";
    exit(1);
}

$username = $argv[1];
$password = $argv[2];

// Validation
if (strlen($username) < 3) {
    die("Error: Username must be at least 3 characters long\n");
}

if (strlen($password) < 8) {
    die("Error: Password must be at least 8 characters long\n");
}

if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
    die("Error: Username can only contain letters, numbers, dots, hyphens and underscores\n");
}

// Définir le chemin du fichier .password
$password_file = __DIR__ . '/../data/.password';

// Créer le répertoire data s'il n'existe pas
$data_dir = dirname($password_file);
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0755, true);
}

// Vérifier si l'utilisateur existe déjà
$existing_users = [];
if (file_exists($password_file)) {
    $lines = file($password_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($existing_username) = explode(':', $line, 2);
            $existing_users[trim($existing_username)] = $line;
        }
    }
}

// Hasher le mot de passe
$hash = password_hash($password, PASSWORD_BCRYPT);

// Ajouter ou mettre à jour l'utilisateur
$existing_users[$username] = "$username:$hash";

// Écrire dans le fichier
$content = implode("\n", array_values($existing_users)) . "\n";
if (file_put_contents($password_file, $content) !== false) {
    chmod($password_file, 0600);
    echo "✅ User '$username' has been successfully added/updated\n";
    echo "📁 Password file: $password_file\n";
    exit(0);
} else {
    die("❌ Error: Failed to write to password file\n");
}
