<?php
/**
 * Arquivo de Exemplo de Configuração — 24º Jesus te Chama
 *
 * INSTRUÇÕES:
 * 1. Copie este arquivo para `api/config.php`
 * 2. Preencha os dados reais do banco MySQL (local ou Hostinger)
 * 3. Configure o usuário e o hash da senha do painel /admin/
 * 4. NUNCA envie `api/config.php` com senhas reais para o Git
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'convitejesustechama');
define('DB_USER', 'root');
define('DB_PASSWORD', '');

// Credenciais do Painel Administrativo (/admin/)
// Para gerar um novo hash de senha no PHP, execute no terminal:
// php -r "echo password_hash('SuaSenhaForteAqui', PASSWORD_DEFAULT);"
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$YQriQyFLM.h3si79tsvhYelCaOfjWkTxG4ICkY.aYqwDtntUvyk1e');
