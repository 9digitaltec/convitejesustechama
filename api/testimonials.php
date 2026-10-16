<?php
/**
 * API de Descoberta Automática de Depoimentos — 24º Jesus te Chama
 *
 * Lista automaticamente todos os vídeos (.mp4, .webm, .mov) presentes na pasta `/depoimentos/`.
 * Permite que o cliente apenas adicione novos arquivos de vídeo na pasta sem precisar editar código.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$depoimentosDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'depoimentos';
$items = [];

if (is_dir($depoimentosDir)) {
    $files = scandir($depoimentosDir);
    if (is_array($files)) {
        $videoFiles = [];
        $allowedExtensions = ['mp4', 'webm', 'mov', 'm4v'];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $fullPath = $depoimentosDir . DIRECTORY_SEPARATOR . $file;
            if (!is_file($fullPath)) {
                continue;
            }
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExtensions, true)) {
                $videoFiles[] = $file;
            }
        }

        // Ordenação natural e insensível a maiúsculas/minúsculas (ex: 01, 02, 10...)
        natcasesort($videoFiles);
        $videoFiles = array_values($videoFiles);

        foreach ($videoFiles as $index => $filename) {
            $numStr = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $baseName = pathinfo($filename, PATHINFO_FILENAME);

            // Verifica se o nome do arquivo é genérico (ex: "WhatsApp Video...", "video1", "depoimento_01", "VID-...")
            $isGenericName = (bool) preg_match(
                '/^(whatsapp\s*video|vid[-_]|video[-_\s]*\d*|depoimento[-_\s]*\d+|img[-_]|mov[-_]|\d+)$/i',
                trim($baseName)
            );

            // Se o cliente nomear como "01 - Lucas.mp4" ou "Maria.mp4", extrai o nome limpo opcionalmente
            $cleanName = '';
            if (!$isGenericName) {
                $cleaned = preg_replace('/^\d+\s*[-_.]+\s*/', '', trim($baseName));
                $cleanName = is_string($cleaned) ? trim($cleaned) : '';
            }

            $items[] = [
                'id'      => 'depoimento_' . $numStr,
                'name'    => $cleanName,
                'role'    => '',
                'file'    => 'depoimentos/' . $filename,
                'poster'  => '',
                'caption' => $index === 0
                    ? 'Um convite preparado de coração por quem está organizando cada detalhe para receber você.'
                    : '',
                'tracks'  => [],
            ];
        }
    }
}

echo json_encode([
    'ok'           => true,
    'testimonials' => $items,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
