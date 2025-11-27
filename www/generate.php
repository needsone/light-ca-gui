<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['hostname'])) {
    header('Location: index.php?error=' . urlencode('Hostname manquant'));
    exit;
}

$hostname = trim($_POST['hostname']);

$result = generateCertificate($hostname);

if ($result['success']) {
    header('Location: index.php?success=1&hostname=' . urlencode($result['hostname']));
} else {
    header('Location: index.php?error=' . urlencode($result['error']));
}
exit;
?>
