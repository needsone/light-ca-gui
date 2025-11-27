<?php
require_once 'config.php';

if (!file_exists(CA_ROOT_CERT)) {
    die('Certificat Root CA introuvable');
}

header('Content-Type: application/x-x509-ca-cert');
header('Content-Disposition: attachment; filename="root_ca.crt"');
header('Content-Length: ' . filesize(CA_ROOT_CERT));
readfile(CA_ROOT_CERT);
exit;
?>
