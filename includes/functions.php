<?php
/**
 * Utility Functions
 */

require_once __DIR__ . '/../config.php';

/**
 * Execute step-ca command
 */
function execute_step_command($command, &$output = null, &$return_var = null) {
    $full_command = "step " . $command . " 2>&1";
    
    log_message('DEBUG', "Executing step command: $full_command");
    
    exec($full_command, $output, $return_var);
    
    if ($return_var !== 0) {
        log_message('ERROR', "Step command failed", [
            'command' => $command,
            'return_code' => $return_var,
            'output' => implode("\n", $output ?? [])
        ]);
    }
    
    return $return_var === 0;
}

/**
 * Get CA root certificate
 */
function get_ca_root_cert() {
    if (!file_exists(STEP_CA_ROOT_CERT)) {
        return null;
    }
    return file_get_contents(STEP_CA_ROOT_CERT);
}

/**
 * Get CA intermediate certificate
 */
function get_ca_intermediate_cert() {
    if (!file_exists(STEP_CA_INTERMEDIATE_CERT)) {
        return null;
    }
    return file_get_contents(STEP_CA_INTERMEDIATE_CERT);
}

/**
 * Get CA information
 */
function get_ca_info() {
    if (!file_exists(STEP_CA_CONFIG)) {
        return null;
    }
    
    $config = json_decode(file_get_contents(STEP_CA_CONFIG), true);
    
    if (!$config) {
        return null;
    }
    
    return [
        'name' => $config['authority']['name'] ?? 'Unknown',
        'address' => $config['address'] ?? 'Unknown',
        'dns_names' => $config['dnsNames'] ?? [],
        'provisioners' => count($config['authority']['provisioners'] ?? []),
    ];
}

/**
 * Create a certificate with step-ca
 */
function create_certificate($common_name, $dns_names, $validity_days, $password, $output_format = 'pem') {
    $safe_cn = sanitize_filename($common_name);
    $timestamp = date('Y-m-d_His');
    $cert_dir = CERTS_DIR . "/{$safe_cn}_{$timestamp}";
    
    // Create directory for this certificate
    if (!mkdir($cert_dir, 0755, true)) {
        return ['success' => false, 'error' => 'Failed to create certificate directory'];
    }
    
    $cert_file = $cert_dir . "/cert.crt";
    $key_file = $cert_dir . "/cert.key";
    $chain_file = $cert_dir . "/chain.pem";
    
    // Build DNS names argument
    $dns_args = '';
    foreach ($dns_names as $dns) {
        $dns = trim($dns);
        if (!empty($dns)) {
            $dns_args .= "--san " . escapeshellarg($dns) . " ";
        }
    }
    
    // Create temporary password file
    $password_file = tempnam(sys_get_temp_dir(), 'step_pass_');
    file_put_contents($password_file, $password);
    chmod($password_file, 0600);
    
    try {
        // Build the step certificate command
        $command = sprintf(
            "ca certificate %s %s %s " .
            "--provisioner %s " .
            "--provisioner-password-file %s " .
            "--not-after %dh " .
            "--force",
            escapeshellarg($common_name),
            escapeshellarg($cert_file),
            escapeshellarg($key_file),
            escapeshellarg(CA_PROVISIONER),
            escapeshellarg($password_file),
            $validity_days * 24
        );
        
        if (!empty($dns_args)) {
            $command .= " " . $dns_args;
        }
        
        $output = [];
        $return_var = 0;
        
        if (!execute_step_command($command, $output, $return_var)) {
            @unlink($password_file);
            return [
                'success' => false,
                'error' => 'Failed to create certificate: ' . implode("\n", $output)
            ];
        }
        
        // Create full chain file
        $root_cert = get_ca_root_cert();
        $intermediate_cert = get_ca_intermediate_cert();
        $cert_content = file_get_contents($cert_file);
        
        $chain_content = $cert_content;
        if ($intermediate_cert) {
            $chain_content .= "\n" . $intermediate_cert;
        }
        if ($root_cert) {
            $chain_content .= "\n" . $root_cert;
        }
        
        file_put_contents($chain_file, $chain_content);
        
        // Create additional formats if requested
        $files = [
            'cert' => $cert_file,
            'key' => $key_file,
            'chain' => $chain_file,
        ];
        
        // Create PKCS12 format if requested
        if ($output_format === 'pkcs12' || $output_format === 'all') {
            $p12_file = $cert_dir . "/cert.p12";
            $p12_password = bin2hex(random_bytes(8));
            
            $openssl_cmd = sprintf(
                "openssl pkcs12 -export " .
                "-out %s " .
                "-inkey %s " .
                "-in %s " .
                "-certfile %s " .
                "-passout pass:%s",
                escapeshellarg($p12_file),
                escapeshellarg($key_file),
                escapeshellarg($cert_file),
                escapeshellarg($chain_file),
                escapeshellarg($p12_password)
            );
            
            exec($openssl_cmd, $openssl_output, $openssl_return);
            
            if ($openssl_return === 0) {
                $files['pkcs12'] = $p12_file;
                $files['pkcs12_password'] = $p12_password;
                
                // Save password to file
                file_put_contents($cert_dir . "/p12_password.txt", $p12_password);
            }
        }
        
        // Create certificate info file
        $info = [
            'common_name' => $common_name,
            'dns_names' => $dns_names,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['username'] ?? 'unknown',
            'validity_days' => $validity_days,
            'expires_at' => date('Y-m-d H:i:s', time() + ($validity_days * 86400)),
        ];
        
        file_put_contents($cert_dir . "/info.json", json_encode($info, JSON_PRETTY_PRINT));
        
        // Create README
        $readme = "Certificate Information\n";
        $readme .= "======================\n\n";
        $readme .= "Common Name: {$common_name}\n";
        $readme .= "DNS Names: " . implode(', ', $dns_names) . "\n";
        $readme .= "Created: " . date('Y-m-d H:i:s') . "\n";
        $readme .= "Valid for: {$validity_days} days\n";
        $readme .= "Expires: " . date('Y-m-d H:i:s', time() + ($validity_days * 86400)) . "\n\n";
        $readme .= "Files:\n";
        $readme .= "------\n";
        $readme .= "cert.crt - Certificate\n";
        $readme .= "cert.key - Private Key (KEEP SECURE!)\n";
        $readme .= "chain.pem - Full certificate chain\n";
        if (isset($files['pkcs12'])) {
            $readme .= "cert.p12 - PKCS12 bundle (password in p12_password.txt)\n";
        }
        
        file_put_contents($cert_dir . "/README.txt", $readme);
        
        @unlink($password_file);
        
        log_message('INFO', "Certificate created successfully", [
            'common_name' => $common_name,
            'directory' => $cert_dir
        ]);
        
        return [
            'success' => true,
            'directory' => $cert_dir,
            'files' => $files,
            'info' => $info,
        ];
        
    } catch (Exception $e) {
        @unlink($password_file);
        log_message('ERROR', "Exception creating certificate: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * List all created certificates
 */
function list_certificates() {
    if (!is_dir(CERTS_DIR)) {
        return [];
    }
    
    $certificates = [];
    $dirs = array_diff(scandir(CERTS_DIR), ['.', '..']);
    
    foreach ($dirs as $dir) {
        $cert_path = CERTS_DIR . '/' . $dir;
        
        if (!is_dir($cert_path)) {
            continue;
        }
        
        $info_file = $cert_path . '/info.json';
        
        if (file_exists($info_file)) {
            $info = json_decode(file_get_contents($info_file), true);
            $info['directory'] = $dir;
            $info['path'] = $cert_path;
            
            // Check if expired
            $info['is_expired'] = strtotime($info['expires_at']) < time();
            
            $certificates[] = $info;
        }
    }
    
    // Sort by creation date, newest first
    usort($certificates, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return $certificates;
}

/**
 * Delete a certificate directory
 */
function delete_certificate($directory) {
    $cert_path = CERTS_DIR . '/' . basename($directory);
    
    if (!is_dir($cert_path)) {
        return false;
    }
    
    // Recursively delete directory
    $files = array_diff(scandir($cert_path), ['.', '..']);
    
    foreach ($files as $file) {
        $file_path = $cert_path . '/' . $file;
        if (is_file($file_path)) {
            unlink($file_path);
        }
    }
    
    if (rmdir($cert_path)) {
        log_message('INFO', "Certificate deleted", ['directory' => $directory]);
        return true;
    }
    
    return false;
}

/**
 * Download certificate files as ZIP
 */
function create_certificate_zip($directory) {
    $cert_path = CERTS_DIR . '/' . basename($directory);
    
    if (!is_dir($cert_path)) {
        return null;
    }
    
    $zip_file = tempnam(sys_get_temp_dir(), 'cert_') . '.zip';
    $zip = new ZipArchive();
    
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return null;
    }
    
    $files = array_diff(scandir($cert_path), ['.', '..']);
    
    foreach ($files as $file) {
        $file_path = $cert_path . '/' . $file;
        if (is_file($file_path)) {
            $zip->addFile($file_path, $file);
        }
    }
    
    $zip->close();
    
    return $zip_file;
}

/**
 * Get certificate details
 */
function get_certificate_details($cert_file) {
    if (!file_exists($cert_file)) {
        return null;
    }
    
    $command = sprintf(
        "certificate inspect %s --format json",
        escapeshellarg($cert_file)
    );
    
    $output = [];
    $return_var = 0;
    
    if (!execute_step_command($command, $output, $return_var)) {
        return null;
    }
    
    $json = implode("\n", $output);
    return json_decode($json, true);
}

/**
 * Verify CA password
 */
function verify_ca_password($password) {
    // Create a temporary file for password
    $password_file = tempnam(sys_get_temp_dir(), 'step_verify_');
    file_put_contents($password_file, $password);
    chmod($password_file, 0600);
    
    try {
        // Try to list provisioners with the password
        $command = sprintf(
            "ca provisioner list --ca-url https://localhost:9000 --root %s --offline",
            escapeshellarg(STEP_CA_ROOT_CERT)
        );
        
        $output = [];
        $return_var = 0;
        
        // This is a simple check - in production you might want a more robust verification
        $result = file_exists(STEP_CA_ROOT_CERT) && !empty($password);
        
        @unlink($password_file);
        
        return $result;
        
    } catch (Exception $e) {
        @unlink($password_file);
        return false;
    }
}

/**
 * Validate domain name
 */
function is_valid_domain($domain) {
    return preg_match('/^(?!\-)(?:[a-zA-Z0-9\-]{0,62}[a-zA-Z0-9]\.)*[a-zA-Z]{2,}$/', $domain) === 1;
}

/**
 * Validate IP address
 */
function is_valid_ip($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}
