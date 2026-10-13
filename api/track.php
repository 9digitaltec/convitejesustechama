<?php
/**
 * Endpoint de Rastreamento Anônimo — 24º Jesus te Chama
 *
 * Recebe eventos via POST JSON e registra na tabela `video_events`.
 * Não armazena IP nem dados pessoais.
 */

declare(strict_types=1);

// Desativa exibição de erros internos na saída JSON
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// 1. Aceitar somente método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// 2. Ler e validar JSON do corpo da requisição
$rawBody = file_get_contents('php://input');
if ($rawBody === false || trim($rawBody) === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Empty payload'
    ]);
    exit;
}

// Limita tamanho bruto do payload para evitar abuso (máx 8 KB)
if (strlen($rawBody) > 8192) {
    http_response_code(413);
    echo json_encode([
        'success' => false,
        'message' => 'Payload too large'
    ]);
    exit;
}

$data = json_decode($rawBody, true);
if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON'
    ]);
    exit;
}

// 3. Whitelist de eventos permitidos
$allowedEvents = [
    'page_view',
    'video_start',
    'video_25',
    'video_50',
    'video_75',
    'video_90',
    'video_complete',
    'share_click',
    'whatsapp_click',
];

$eventName = isset($data['event_name']) && is_string($data['event_name'])
    ? trim($data['event_name'])
    : '';

if ($eventName === '' || !in_array($eventName, $allowedEvents, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid event'
    ]);
    exit;
}

// 4. Validar visitor_id e session_id
$visitorId = isset($data['visitor_id']) && is_string($data['visitor_id'])
    ? trim($data['visitor_id'])
    : '';
$sessionId = isset($data['session_id']) && is_string($data['session_id'])
    ? trim($data['session_id'])
    : '';

if ($visitorId === '' || $sessionId === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid visitor or session'
    ]);
    exit;
}

// Permite apenas caracteres seguros nos identificadores e limita a 64 caracteres
$visitorId = substr(preg_replace('/[^a-zA-Z0-9\-_]/', '', $visitorId) ?? '', 0, 64);
$sessionId = substr(preg_replace('/[^a-zA-Z0-9\-_]/', '', $sessionId) ?? '', 0, 64);

if ($visitorId === '' || $sessionId === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid identifier format'
    ]);
    exit;
}

// 5. Sanitizar e limitar tamanhos dos campos opcionais
function sanitizeOptionalString($value, int $maxLength): ?string
{
    if (!is_string($value)) {
        return null;
    }
    $clean = trim(strip_tags($value));
    if ($clean === '') {
        return null;
    }
    if (function_exists('mb_substr')) {
        return mb_substr($clean, 0, $maxLength, 'UTF-8');
    }
    return substr($clean, 0, $maxLength);
}

$videoId   = sanitizeOptionalString($data['video_id'] ?? null, 100);
$videoName = sanitizeOptionalString($data['video_name'] ?? null, 255);
$pageUrl   = sanitizeOptionalString($data['page_url'] ?? null, 500);
$referrer  = sanitizeOptionalString($data['referrer'] ?? null, 500);
$language  = sanitizeOptionalString($data['language'] ?? null, 20);

$progress = null;
if (isset($data['progress']) && is_numeric($data['progress'])) {
    $p = (int) round((float) $data['progress']);
    $progress = max(0, min(100, $p));
}

$screenWidth = null;
if (isset($data['screen_width']) && is_numeric($data['screen_width'])) {
    $w = (int) $data['screen_width'];
    if ($w > 0 && $w <= 65535) {
        $screenWidth = $w;
    }
}

$screenHeight = null;
if (isset($data['screen_height']) && is_numeric($data['screen_height'])) {
    $h = (int) $data['screen_height'];
    if ($h > 0 && $h <= 65535) {
        $screenHeight = $h;
    }
}

// 6. Inserir no banco usando PDO e Prepared Statements
try {
    require_once __DIR__ . '/db.php';
    $pdo = getDbConnection();

    $sql = 'INSERT INTO video_events (
                visitor_id,
                session_id,
                event_name,
                video_id,
                video_name,
                progress,
                page_url,
                referrer,
                screen_width,
                screen_height,
                language
            ) VALUES (
                :visitor_id,
                :session_id,
                :event_name,
                :video_id,
                :video_name,
                :progress,
                :page_url,
                :referrer,
                :screen_width,
                :screen_height,
                :language
            )';

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':visitor_id', $visitorId, PDO::PARAM_STR);
    $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
    $stmt->bindValue(':event_name', $eventName, PDO::PARAM_STR);
    $stmt->bindValue(':video_id', $videoId, $videoId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':video_name', $videoName, $videoName === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':progress', $progress, $progress === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':page_url', $pageUrl, $pageUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':referrer', $referrer, $referrer === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':screen_width', $screenWidth, $screenWidth === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':screen_height', $screenHeight, $screenHeight === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':language', $language, $language === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

    $stmt->execute();

    echo json_encode([
        'success' => true
    ]);
} catch (Throwable $e) {
    // Nunca expor detalhes internos do banco, queries ou stack trace
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Service unavailable'
    ]);
}
