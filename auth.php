<?php
/**
 * Sistema de Autenticação
 * Gerencia login, logout e verificação de permissões
 */

require_once __DIR__ . '/database.php';

// Inicia sessão se ainda não foi iniciada - CORRIGIDO
if (session_status() === PHP_SESSION_NONE) {
    // Verifica se headers já foram enviados
    if (!headers_sent($file, $line)) {
        session_start();
    } else {
        // Debug apenas em desenvolvimento
        // error_log("Headers já enviados em $file linha $line");
    }
}

// ========================================
// FUNÇÕES DE LOGIN/LOGOUT
// ========================================

function login($email, $password) {
    $user = authenticateUser($email, $password);
    
    if ($user) {
        // Regenera ID da sessão para segurança (só se sessão estiver ativa)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        
        // Armazena dados do usuário na sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        $_SESSION['logged_in_at'] = time();
        
        return true;
    }
    
    return false;
}

function logout() {
    // Limpa todas as variáveis de sessão
    $_SESSION = [];
    
    // Destrói o cookie da sessão
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destrói a sessão
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

// ========================================
// FUNÇÕES DE VERIFICAÇÃO
// ========================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return getUserById($_SESSION['user_id']);
}

function requireLogin($redirectTo = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectTo");
        exit;
    }
}

function requireAdmin($redirectTo = 'index.php') {
    if (!isAdmin()) {
        header("Location: $redirectTo");
        exit;
    }
}

// ========================================
// FUNÇÕES DE VERIFICAÇÃO DE CRÉDITOS
// ========================================

function hasCredits($userId, $amount = 1) {
    return getUserCredits($userId) >= $amount;
}

function requireCredits($amount = 1, $redirectTo = 'index.php?error=no_credits') {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
    
    if (!hasCredits(getCurrentUserId(), $amount)) {
        header("Location: $redirectTo");
        exit;
    }
}

// ========================================
// MIDDLEWARE DE PROTEÇÃO
// ========================================

function checkMaintenanceMode() {
    $maintenance = getSetting('maintenance_mode', 0);
    
    if ($maintenance == 1 && !isAdmin()) {
        http_response_code(503);
        include 'maintenance.php';
        exit;
    }
}

function checkRegistrationAllowed() {
    return getSetting('allow_registration', 1) == 1;
}

// ========================================
// FUNÇÕES AUXILIARES
// ========================================

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'text' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $message;
    }
    return null;
}

// ========================================
// PROTEÇÃO CSRF
// ========================================

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

?>