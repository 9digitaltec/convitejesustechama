<?php
/**
 * Conexão PDO com MySQL — 24º Jesus te Chama
 *
 * Configurações obrigatórias de segurança e compatibilidade:
 * - PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
 * - PDO::ATTR_EMULATE_PREPARES => false
 * - charset=utf8mb4
 */

declare(strict_types=1);

$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    require_once __DIR__ . '/config.example.php';
}

/**
 * Retorna uma instância ativa do PDO conectada ao banco MySQL.
 *
 * @return PDO
 * @throws PDOException
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_NAME
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);

    return $pdo;
}
