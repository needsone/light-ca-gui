<?php
require_once 'config.php';

if (empty($_GET['file'])) {
    die('Fichier non spécifié');
}

$filename = basename($_GET['file']);
$filepath = CERTS_DIR . '/' . $filename;

if (!file_exists($filepath)) {
    die('Fichier introuvable');
}

// Déterminer le type MIME
$extension = pathinfo($filename, PATHINFO_EXTENSION);
switch ($extension) {
    case 'crt':
        $mimeType = 'application/x-x509-ca-cert';
        break;
    case 'key':
        $mimeType = 'application/x-pem-file';
        break;
    default:
        $mimeType = 'application/octet-stream';
}

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
?>
