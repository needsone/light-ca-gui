<?php
/**
 * Authentication Module
 */

require_once 'config.php';

/**
 * Authenticate user against .password file
 */
function authenticate_local($username, $password) {
    if (!file_exists(PASSWORD_FILE)) {
        log_message('ERROR', 'Password file not found');
        return false;
    }
    
    $lines = file(PASSWORD_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos($line, ':') === false) continue;
        
        list($stored_username, $stored_hash) = explode(':', $line, 2);
        
        if (trim($stored_username) === $username) {
            if (password_verify($password, trim($stored_hash))) {
                log_message('INFO', "Local authentication successful for user: $username");
                return [
                    'username' => $username,
                    'display_name' => ucfirst($username),
                    'email' => '',
                    'auth_method' => 'local',
                ];
            }
        }
    }
    
    log_message('WARNING', "Local authentication failed for user: $username");
    return false;
}

/**
 * Authenticate user against Active Directory
 */
function authenticate_ad($username, $password) {
    if (!AD_ENABLED) {
        return false;
    }
    
    if (!extension_loaded('ldap')) {
        log_message('ERROR', 'LDAP extension not loaded');
        return false;
    }
    
    if (empty($username) || empty($password)) {
        return false;
    }
    
    try {
        $ldap_conn = ldap_connect(AD_SERVER, AD_PORT);
        
        if (!$ldap_conn) {
            log_message('ERROR', 'Failed to connect to AD server: ' . AD_SERVER);
            return false;
        }
        
        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap_conn, LDAP_OPT_NETWORK_TIMEOUT, AD_TIMEOUT);
        
        if (AD_USE_TLS && !str_starts_with(AD_SERVER, 'ldaps://')) {
            if (!@ldap_start_tls($ldap_conn)) {
                log_message('ERROR', 'Failed to start TLS: ' . ldap_error($ldap_conn));
                ldap_close($ldap_conn);
                return false;
            }
        }
        
        // Format username
        $bind_username = $username;
        if (!str_contains($username, '\\') && !str_contains($username, '@')) {
            $bind_username = AD_DOMAIN . '\\' . $username;
        }
        
        // Bind
        $bind = @ldap_bind($ldap_conn, $bind_username, $password);
        
        if (!$bind) {
            log_message('WARNING', "AD authentication failed for user: $username");
            ldap_close($ldap_conn);
            return false;
        }
        
        // Extract clean username
        $clean_username = $username;
        if (str_contains($username, '\\')) {
            $parts = explode('\\', $username);
            $clean_username = end($parts);
        } elseif (str_contains($username, '@')) {
            $parts = explode('@', $username);
            $clean_username = $parts[0];
        }
        
        // Search for user
        $search_filter = "(&(objectClass=user)(sAMAccountName=" . ldap_escape($clean_username, '', LDAP_ESCAPE_FILTER) . "))";
        $search_result = @ldap_search($ldap_conn, AD_BASE_DN, $search_filter, ['cn', 'mail', 'displayname', 'memberof']);
        
        if (!$search_result) {
            ldap_close($ldap_conn);
            return false;
        }
        
        $entries = ldap_get_entries($ldap_conn, $search_result);
        
        if ($entries['count'] === 0) {
            ldap_close($ldap_conn);
            return false;
        }
        
        $user_entry = $entries[0];
        
        // Check group membership
        if (!empty(AD_REQUIRED_GROUPS)) {
            $user_groups = [];
            if (isset($user_entry['memberof'])) {
                $user_groups = is_array($user_entry['memberof']) ? $user_entry['memberof'] : [$user_entry['memberof']];
                unset($user_groups['count']);
            }
            
            $has_access = false;
            foreach (AD_REQUIRED_GROUPS as $required_group) {
                foreach ($user_groups as $user_group) {
                    if (stripos($user_group, trim($required_group)) !== false) {
                        $has_access = true;
                        break 2;
                    }
                }
            }
            
            if (!$has_access) {
                log_message('WARNING', "User $clean_username not in required AD groups");
                ldap_close($ldap_conn);
                return false;
            }
        }
        
        $user_info = [
            'username' => $clean_username,
            'display_name' => $user_entry['displayname'][0] ?? $user_entry['cn'][0] ?? $clean_username,
            'email' => $user_entry['mail'][0] ?? '',
            'auth_method' => 'active_directory',
        ];
        
        ldap_close($ldap_conn);
        
        log_message('INFO', "AD authentication successful for user: $clean_username");
        
        return $user_info;
        
    } catch (Exception $e) {
        log_message('ERROR', "AD authentication exception: " . $e->getMessage());
        if (isset($ldap_conn)) {
            @ldap_close($ldap_conn);
        }
        return false;
    }
}

/**
 * Main authentication function
 */
function authenticate_user($username, $password) {
    // Try AD first if enabled
    if (AD_ENABLED) {
        $ad_result = authenticate_ad($username, $password);
        if ($ad_result !== false) {
            return $ad_result;
        }
    }
    
    // Fallback to local authentication
    return authenticate_local($username, $password);
}

/**
 * Check if user is logged in
 */
function require_login() {
    session_start();
    
    // Check if logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Get all users from .password file
 */
function get_all_users() {
    if (!file_exists(PASSWORD_FILE)) {
        return [];
    }
    
    $users = [];
    $lines = file(PASSWORD_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($username) = explode(':', $line, 2);
            $users[] = trim($username);
        }
    }
    
    return $users;
}

/**
 * Add or update user in .password file
 */
function save_user($username, $password) {
    $existing_users = [];
    
    if (file_exists(PASSWORD_FILE)) {
        $lines = file(PASSWORD_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($existing_username) = explode(':', $line, 2);
                $existing_users[trim($existing_username)] = $line;
            }
        }
    }
    
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $existing_users[$username] = "$username:$hash";
    
    $content = implode("\n", array_values($existing_users)) . "\n";
    
    if (file_put_contents(PASSWORD_FILE, $content) !== false) {
        chmod(PASSWORD_FILE, 0600);
        log_message('INFO', "User $username added/updated");
        return true;
    }
    
    log_message('ERROR', "Failed to save user $username");
    return false;
}

/**
 * Delete user from .password file
 */
function delete_user($username) {
    if (!file_exists(PASSWORD_FILE)) {
        return false;
    }
    
    $lines = file(PASSWORD_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $new_lines = [];
    
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($stored_username) = explode(':', $line, 2);
            if (trim($stored_username) !== $username) {
                $new_lines[] = $line;
            }
        }
    }
    
    $content = implode("\n", $new_lines) . "\n";
    
    if (file_put_contents(PASSWORD_FILE, $content) !== false) {
        log_message('INFO', "User $username deleted");
        return true;
    }
    
    log_message('ERROR', "Failed to delete user $username");
    return false;
}
