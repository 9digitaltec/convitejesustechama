<?php
/**
 * Painel Administrativo de Métricas — 24º Jesus te Chama
 *
 * Exibe indicadores gerais do convite e o funil completo de cada vídeo.
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireAdminAuth();

require_once dirname(__DIR__) . '/api/db.php';

$dbConnected = false;
$dbErrorHint = '';

$summary = [
    'unique_visitors'    => 0,
    'page_views'         => 0,
    'unique_sessions'    => 0,
    'share_clicks'       => 0,
    'whatsapp_clicks'    => 0,
    'multi_video_users'  => 0,
    'returning_visitors' => 0,
];

$videos = [];
$dailyStats = [];

try {
    $pdo = getDbConnection();
    $dbConnected = true;

    // 1. Indicadores Principais do Topo
    $kpiSql = "
        SELECT
            COUNT(DISTINCT visitor_id) AS unique_visitors,
            COUNT(CASE WHEN event_name = 'page_view' THEN 1 END) AS page_views,
            COUNT(DISTINCT session_id) AS unique_sessions,
            COUNT(CASE WHEN event_name = 'share_click' THEN 1 END) AS share_clicks,
            COUNT(CASE WHEN event_name = 'whatsapp_click' THEN 1 END) AS whatsapp_clicks
        FROM video_events
    ";
    $kpiRow = $pdo->query($kpiSql)->fetch();
    if ($kpiRow) {
        $summary['unique_visitors'] = (int) ($kpiRow['unique_visitors'] ?? 0);
        $summary['page_views']      = (int) ($kpiRow['page_views'] ?? 0);
        $summary['unique_sessions'] = (int) ($kpiRow['unique_sessions'] ?? 0);
        $summary['share_clicks']    = (int) ($kpiRow['share_clicks'] ?? 0);
        $summary['whatsapp_clicks'] = (int) ($kpiRow['whatsapp_clicks'] ?? 0);
    }

    // 2. Quantos visitantes assistiram mais de um vídeo
    $multiSql = "
        SELECT COUNT(*) AS total
        FROM (
            SELECT visitor_id
            FROM video_events
            WHERE event_name = 'video_start'
              AND video_id IS NOT NULL
            GROUP BY visitor_id
            HAVING COUNT(DISTINCT video_id) > 1
        ) AS t
    ";
    $multiRow = $pdo->query($multiSql)->fetch();
    $summary['multi_video_users'] = (int) ($multiRow['total'] ?? 0);

    // 3. Visitantes que retornaram (mais de 1 sessão)
    $returnSql = "
        SELECT COUNT(*) AS total
        FROM (
            SELECT visitor_id
            FROM video_events
            GROUP BY visitor_id
            HAVING COUNT(DISTINCT session_id) > 1
        ) AS r
    ";
    $returnRow = $pdo->query($returnSql)->fetch();
    $summary['returning_visitors'] = (int) ($returnRow['total'] ?? 0);

    // 4. Métricas por Vídeo (Funil Completo)
    $videoSql = "
        SELECT
            video_id,
            MAX(video_name) AS video_name,
            COUNT(DISTINCT CASE WHEN event_name = 'video_start' THEN visitor_id END) AS started,
            COUNT(DISTINCT CASE WHEN event_name = 'video_25' THEN visitor_id END) AS p25,
            COUNT(DISTINCT CASE WHEN event_name = 'video_50' THEN visitor_id END) AS p50,
            COUNT(DISTINCT CASE WHEN event_name = 'video_75' THEN visitor_id END) AS p75,
            COUNT(DISTINCT CASE WHEN event_name = 'video_90' THEN visitor_id END) AS p90,
            COUNT(DISTINCT CASE WHEN event_name = 'video_complete' THEN visitor_id END) AS completed
        FROM video_events
        WHERE video_id IS NOT NULL
          AND video_id <> ''
        GROUP BY video_id
        ORDER BY video_id ASC
    ";
    $videoRows = $pdo->query($videoSql)->fetchAll();

    foreach ($videoRows as $row) {
        $started   = (int) $row['started'];
        $completed = (int) $row['completed'];
        $p50       = (int) $row['p50'];

        $completionRate = $started > 0 ? ($completed / $started) * 100.0 : 0.0;
        $relevantRate   = $started > 0 ? ($p50 / $started) * 100.0 : 0.0;

        $videos[] = [
            'video_id'        => (string) $row['video_id'],
            'video_name'      => !empty($row['video_name']) ? (string) $row['video_name'] : (string) $row['video_id'],
            'started'         => $started,
            'p25'             => (int) $row['p25'],
            'p50'             => $p50,
            'p75'             => (int) $row['p75'],
            'p90'             => (int) $row['p90'],
            'completed'       => $completed,
            'completion_rate' => $completionRate,
            'relevant_rate'   => $relevantRate,
        ];
    }

    // Se ainda não houver eventos de vídeo gravados no banco, exibe card zerado para Depoimento 01
    if (empty($videos)) {
        $videos[] = [
            'video_id'        => 'depoimento_01',
            'video_name'      => 'Depoimento 01',
            'started'         => 0,
            'p25'             => 0,
            'p50'             => 0,
            'p75'             => 0,
            'p90'             => 0,
            'completed'       => 0,
            'completion_rate' => 0.0,
            'relevant_rate'   => 0.0,
        ];
    }

    // 5. Acessos por Dia (últimos 14 dias)
    $dailySql = "
        SELECT
            DATE(created_at) AS access_date,
            COUNT(CASE WHEN event_name = 'page_view' THEN 1 END) AS page_views,
            COUNT(DISTINCT visitor_id) AS unique_visitors,
            COUNT(DISTINCT session_id) AS unique_sessions
        FROM video_events
        GROUP BY DATE(created_at)
        ORDER BY access_date DESC
        LIMIT 14
    ";
    $dailyStats = $pdo->query($dailySql)->fetchAll();
} catch (Throwable $e) {
    $dbConnected = false;
    $dbErrorHint = 'Não foi possível conectar ao banco de dados MySQL. Verifique o arquivo api/config.php e se database/schema.sql já foi executado.';
}

/**
 * Formata percentual no padrão brasileiro (ex: 57,8%).
 */
function formatPercent(float $value): string
{
    return number_format($value, 1, ',', '.') . '%';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Painel Administrativo | 24º Jesus te Chama</title>
  <style>
    :root {
      --bg-deep: #061224;
      --bg-card: #0c1f3a;
      --bg-card-alt: #09182e;
      --gold: #f4c36d;
      --gold-soft: #f7d99c;
      --amber: #e87d2e;
      --text: #f8f5f0;
      --muted: #9fb0c7;
      --border: rgba(244, 195, 109, 0.22);
      --success: #34d399;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background-color: var(--bg-deep);
      color: var(--text);
      line-height: 1.5;
      padding-bottom: 48px;
    }
    .container {
      width: 100%;
      max-width: 1120px;
      margin: 0 auto;
      padding: 0 16px;
    }
    .topbar {
      background: rgba(9, 24, 46, 0.95);
      border-bottom: 1px solid var(--border);
      padding: 16px 0;
      margin-bottom: 24px;
    }
    .topbar-inner {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .topbar-title span {
      display: block;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--gold);
      font-weight: 700;
    }
    .topbar-title h1 {
      font-size: 1.25rem;
      color: var(--text);
    }
    .topbar-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .btn-top {
      padding: 8px 14px;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 600;
      text-decoration: none;
      border: 1px solid var(--border);
      color: var(--text);
      background: rgba(255, 255, 255, 0.05);
    }
    .btn-top:hover {
      background: rgba(244, 195, 109, 0.15);
      color: var(--gold-soft);
    }
    .btn-logout {
      border-color: rgba(248, 113, 113, 0.4);
      color: #fca5a5;
    }
    .alert-warning {
      background: rgba(232, 125, 46, 0.16);
      border: 1px solid var(--gold);
      color: var(--gold-soft);
      padding: 14px 18px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-size: 0.92rem;
    }
    /* Cards principais do Topo */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
      gap: 14px;
      margin-bottom: 16px;
    }
    .kpi-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 18px 16px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.28);
    }
    .kpi-label {
      font-size: 0.74rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      margin-bottom: 6px;
    }
    .kpi-value {
      font-size: 1.85rem;
      font-weight: 800;
      color: var(--gold);
      line-height: 1.1;
    }
    .kpi-subgrid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 14px;
      margin-bottom: 32px;
    }
    .kpi-subcard {
      background: var(--bg-card-alt);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      padding: 12px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .kpi-subcard span {
      font-size: 0.85rem;
      color: var(--muted);
    }
    .kpi-subcard strong {
      font-size: 1.15rem;
      color: var(--text);
    }
    /* Seções */
    .section-title {
      font-size: 1.2rem;
      color: var(--gold-soft);
      margin-bottom: 6px;
    }
    .section-note {
      font-size: 0.86rem;
      color: var(--muted);
      margin-bottom: 18px;
    }
    /* Cards de Vídeo */
    .videos-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 18px;
      margin-bottom: 36px;
    }
    .video-metric-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    .video-card-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 10px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      padding-bottom: 12px;
    }
    .video-card-head h3 {
      font-size: 1.15rem;
      color: var(--text);
    }
    .video-id-tag {
      font-size: 0.75rem;
      color: var(--muted);
      font-family: monospace;
    }
    .completion-pill {
      background: rgba(52, 211, 153, 0.14);
      border: 1px solid rgba(52, 211, 153, 0.4);
      color: var(--success);
      font-size: 0.8rem;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 999px;
      white-space: nowrap;
    }
    .funnel-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .funnel-row {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .funnel-row.is-highlight {
      background: rgba(244, 195, 109, 0.08);
      border: 1px solid rgba(244, 195, 109, 0.25);
      padding: 8px 10px;
      border-radius: 10px;
    }
    .funnel-meta {
      display: flex;
      justify-content: space-between;
      font-size: 0.88rem;
    }
    .funnel-meta strong {
      color: var(--text);
    }
    .funnel-row.is-highlight .funnel-meta span {
      color: var(--gold);
      font-weight: 600;
    }
    .bar-track {
      width: 100%;
      height: 7px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 999px;
      overflow: hidden;
    }
    .bar-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--amber) 0%, var(--gold) 100%);
      border-radius: 999px;
    }
    .video-card-footer {
      margin-top: 4px;
      padding-top: 12px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.9rem;
    }
    .video-card-footer strong {
      color: var(--gold);
      font-size: 1.05rem;
    }
    /* Tabelas Responsivas */
    .table-wrapper {
      width: 100%;
      overflow-x: auto;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 14px;
      margin-bottom: 32px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 520px;
    }
    th, td {
      padding: 12px 16px;
      text-align: left;
      border-bottom: 1px solid rgba(255, 255, 255, 0.07);
      font-size: 0.9rem;
    }
    th {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      color: var(--gold-soft);
      background: rgba(5, 13, 26, 0.5);
    }
    tr:last-child td {
      border-bottom: none;
    }
    .empty-cell {
      text-align: center;
      color: var(--muted);
      padding: 24px;
    }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="container topbar-inner">
      <div class="topbar-title">
        <span>24º Jesus te Chama</span>
        <h1>Painel de Métricas do Convite</h1>
      </div>
      <div class="topbar-actions">
        <a href="index.php" class="btn-top">Atualizar</a>
        <a href="../index.html" class="btn-top" target="_blank" rel="noopener">Ver Landing</a>
        <a href="logout.php" class="btn-top btn-logout">Sair</a>
      </div>
    </div>
  </header>

  <main class="container">
    <?php if (!$dbConnected): ?>
      <div class="alert-warning" role="alert">
        <strong>Atenção:</strong> <?= htmlspecialchars($dbErrorHint, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <!-- Topo: 5 Indicadores Obrigatórios -->
    <section aria-label="Indicadores principais">
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-label">VISITANTES ÚNICOS</div>
          <div class="kpi-value"><?= number_format($summary['unique_visitors'], 0, ',', '.') ?></div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">VISUALIZAÇÕES DA LANDING</div>
          <div class="kpi-value"><?= number_format($summary['page_views'], 0, ',', '.') ?></div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">SESSÕES</div>
          <div class="kpi-value"><?= number_format($summary['unique_sessions'], 0, ',', '.') ?></div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">COMPARTILHAMENTOS</div>
          <div class="kpi-value"><?= number_format($summary['share_clicks'], 0, ',', '.') ?></div>
        </div>
      </div>

      <div class="kpi-subgrid">
        <div class="kpi-subcard">
          <span>Assistiram mais de um vídeo</span>
          <strong><?= number_format($summary['multi_video_users'], 0, ',', '.') ?></strong>
        </div>
        <div class="kpi-subcard">
          <span>Visitantes que retornaram (+1 sessão)</span>
          <strong><?= number_format($summary['returning_visitors'], 0, ',', '.') ?></strong>
        </div>
      </div>
    </section>

    <!-- Cards de Retenção por Vídeo -->
    <section aria-labelledby="heading-videos">
      <h2 id="heading-videos" class="section-title">Desempenho dos Depoimentos em Vídeo</h2>
      <p class="section-note">
        Métrica principal: <strong>50%</strong> indica visualização real relevante; <strong>Concluíram</strong> indica que assistiram até o fim.
      </p>

      <div class="videos-grid">
        <?php foreach ($videos as $v): ?>
          <?php
            $base = max(1, $v['started']);
            $pct25 = min(100, round(($v['p25'] / $base) * 100));
            $pct50 = min(100, round(($v['p50'] / $base) * 100));
            $pct75 = min(100, round(($v['p75'] / $base) * 100));
            $pct90 = min(100, round(($v['p90'] / $base) * 100));
            $pct100 = min(100, round(($v['completed'] / $base) * 100));
          ?>
          <article class="video-metric-card">
            <div class="video-card-head">
              <div>
                <h3><?= htmlspecialchars($v['video_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                <span class="video-id-tag"><?= htmlspecialchars($v['video_id'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
              <span class="completion-pill">
                Conclusão: <?= formatPercent($v['completion_rate']) ?>
              </span>
            </div>

            <ul class="funnel-list">
              <li class="funnel-row">
                <div class="funnel-meta">
                  <span>Iniciaram:</span>
                  <strong><?= number_format($v['started'], 0, ',', '.') ?></strong>
                </div>
                <div class="bar-track">
                  <div class="bar-fill" style="width: <?= $v['started'] > 0 ? 100 : 0 ?>%;"></div>
                </div>
              </li>

              <li class="funnel-row">
                <div class="funnel-meta">
                  <span>25%:</span>
                  <strong><?= number_format($v['p25'], 0, ',', '.') ?></strong>
                </div>
                <div class="bar-track">
                  <div class="bar-fill" style="width: <?= $pct25 ?>%;"></div>
                </div>
              </li>

              <li class="funnel-row is-highlight">
                <div class="funnel-meta">
                  <span>50% (Visualização Relevante):</span>
                  <strong><?= number_format($v['p50'], 0, ',', '.') ?></strong>
                </div>
                <div class="bar-track">
                  <div class="bar-fill" style="width: <?= $pct50 ?>%;"></div>
                </div>
              </li>

              <li class="funnel-row">
                <div class="funnel-meta">
                  <span>75%:</span>
                  <strong><?= number_format($v['p75'], 0, ',', '.') ?></strong>
                </div>
                <div class="bar-track">
                  <div class="bar-fill" style="width: <?= $pct75 ?>%;"></div>
                </div>
              </li>

              <li class="funnel-row">
                <div class="funnel-meta">
                  <span>90%:</span>
                  <strong><?= number_format($v['p90'], 0, ',', '.') ?></strong>
                </div>
                <div class="bar-track">
                  <div class="bar-fill" style="width: <?= $pct90 ?>%;"></div>
                </div>
              </li>

              <li class="funnel-row">
                <div class="funnel-meta">
                  <span>Concluíram:</span>
                  <strong><?= number_format($v['completed'], 0, ',', '.') ?></strong>
                </div>
                <div class="bar-track">
                  <div class="bar-fill" style="width: <?= $pct100 ?>%;"></div>
                </div>
              </li>
            </ul>

            <div class="video-card-footer">
              <span>Taxa de conclusão:</span>
              <strong><?= formatPercent($v['completion_rate']) ?></strong>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Tabela de Acessos por Dia -->
    <section aria-labelledby="heading-daily">
      <h2 id="heading-daily" class="section-title">Acessos por Dia</h2>
      <p class="section-note">Histórico diário das últimas visualizações registradas.</p>

      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Data</th>
              <th>Visualizações da Landing</th>
              <th>Visitantes Únicos</th>
              <th>Sessões</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($dailyStats)): ?>
              <tr>
                <td colspan="4" class="empty-cell">Nenhum evento registrado ainda no banco de dados.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($dailyStats as $day): ?>
                <tr>
                  <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) $day['access_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= number_format((int) $day['page_views'], 0, ',', '.') ?></td>
                  <td><?= number_format((int) $day['unique_visitors'], 0, ',', '.') ?></td>
                  <td><?= number_format((int) $day['unique_sessions'], 0, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
