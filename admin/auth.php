<?php
/**
 * Autenticação e Segurança de Sessão do Painel Administrativo — 24º Jesus te Chama
 */

declare(strict_types=1);

$configPath = dirname(__DIR__) . '/api/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    require_once dirname(__DIR__) . '/api/config.example.php';
}

/**
 * Inicia a sessão PHP com parâmetros seguros (httponly e secure se HTTPS).
 */
function startSecureAdminSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );

    session_name('jtc_admin_session');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Verifica se o administrador está autenticado na sessão atual.
 */
function isAdminAuthenticated(): bool
{
    startSecureAdminSession();
    return isset($_SESSION['jtc_admin_logged_in']) && $_SESSION['jtc_admin_logged_in'] === true;
}

/**
 * Exige autenticação para acessar uma página administrativa.
 */
function requireAdminAuth(): void
{
    if (!isAdminAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Valida usuário e senha utilizando password_verify() contra o hash configurado.
 */
function verifyAdminCredentials(string $username, string $password): bool
{
    $expectedUser = defined('ADMIN_USERNAME') ? (string) ADMIN_USERNAME : 'admin';
    $expectedHash = defined('ADMIN_PASSWORD_HASH') ? (string) ADMIN_PASSWORD_HASH : '';

    if ($expectedHash === '') {
        return false;
    }

    $userMatch = hash_equals($expectedUser, trim($username));
    $passMatch = password_verify($password, $expectedHash);

    return $userMatch && $passMatch;
}

/**
 * Gera ou retorna o token CSRF da sessão.
 */
function getCsrfToken(): string
{
    startSecureAdminSession();
    if (empty($_SESSION['jtc_csrf_token'])) {
        $_SESSION['jtc_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['jtc_csrf_token'];
}
