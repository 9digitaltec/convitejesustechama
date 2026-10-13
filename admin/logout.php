<?php
/**
 * Logout do Painel Administrativo — 24º Jesus te Chama
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

startSecureAdminSession();

// Limpa todas as variáveis da sessão
$_SESSION = [];

// Expira o cookie de sessão no navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destrói a sessão no servidor
session_destroy();

header('Location: login.php');
exit;
