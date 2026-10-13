-- ============================================================================
-- 24º Jesus te Chama — Consultas Analíticas (MySQL)
-- Tabela: video_events
--
-- NOTA METODOLÓGICA (Seção 24):
-- - video_start    = iniciou a reprodução
-- - video_50       = assistiu parte relevante (MÉTRICA PRINCIPAL de visualização real)
-- - video_complete = assistiu até o fim
-- ============================================================================

-- 1. Total de visualizações da landing
SELECT COUNT(*) AS total_visualizacoes
FROM video_events
WHERE event_name = 'page_view';

-- 2. Visitantes únicos na landing
SELECT COUNT(DISTINCT visitor_id) AS visitantes_unicos
FROM video_events
WHERE event_name = 'page_view';

-- 3. Sessões únicas
SELECT COUNT(DISTINCT session_id) AS sessoes_unicas
FROM video_events;

-- 4. Quantos visitantes únicos iniciaram cada vídeo (video_start)
SELECT
    video_id,
    MAX(video_name) AS video_name,
    COUNT(DISTINCT visitor_id) AS iniciaram
FROM video_events
WHERE event_name = 'video_start'
  AND video_id IS NOT NULL
GROUP BY video_id
ORDER BY video_id ASC;

-- Exemplo específico para depoimento_01 (Iniciaram):
SELECT COUNT(DISTINCT visitor_id) AS iniciaram_depoimento_01
FROM video_events
WHERE event_name = 'video_start'
  AND video_id = 'depoimento_01';

-- 5. Quantos chegaram a 25% em cada vídeo
SELECT
    video_id,
    MAX(video_name) AS video_name,
    COUNT(DISTINCT visitor_id) AS chegaram_25
FROM video_events
WHERE event_name = 'video_25'
  AND video_id IS NOT NULL
GROUP BY video_id
ORDER BY video_id ASC;

-- 6. Quantos chegaram a 50% em cada vídeo (MÉTRICA PRINCIPAL de visualização real)
SELECT
    video_id,
    MAX(video_name) AS video_name,
    COUNT(DISTINCT visitor_id) AS chegaram_50
FROM video_events
WHERE event_name = 'video_50'
  AND video_id IS NOT NULL
GROUP BY video_id
ORDER BY video_id ASC;

-- Exemplo específico para depoimento_01 (Chegaram a 50%):
SELECT COUNT(DISTINCT visitor_id) AS chegaram_50_depoimento_01
FROM video_events
WHERE event_name = 'video_50'
  AND video_id = 'depoimento_01';

-- 7. Quantos chegaram a 75% em cada vídeo
SELECT
    video_id,
    MAX(video_name) AS video_name,
    COUNT(DISTINCT visitor_id) AS chegaram_75
FROM video_events
WHERE event_name = 'video_75'
  AND video_id IS NOT NULL
GROUP BY video_id
ORDER BY video_id ASC;

-- 8. Quantos chegaram a 90% em cada vídeo
SELECT
    video_id,
    MAX(video_name) AS video_name,
    COUNT(DISTINCT visitor_id) AS chegaram_90
FROM video_events
WHERE event_name = 'video_90'
  AND video_id IS NOT NULL
GROUP BY video_id
ORDER BY video_id ASC;

-- 9. Quantos concluíram cada vídeo (video_complete)
SELECT
    video_id,
    MAX(video_name) AS video_name,
    COUNT(DISTINCT visitor_id) AS concluiram
FROM video_events
WHERE event_name = 'video_complete'
  AND video_id IS NOT NULL
GROUP BY video_id
ORDER BY video_id ASC;

-- Exemplo específico para depoimento_01 (Concluíram):
SELECT COUNT(DISTINCT visitor_id) AS concluiram_depoimento_01
FROM video_events
WHERE event_name = 'video_complete'
  AND video_id = 'depoimento_01';

-- 10. Funil completo e Taxa de Conclusão por vídeo
SELECT
    video_id,
    MAX(video_name) AS video_name,
    COUNT(DISTINCT CASE WHEN event_name = 'video_start' THEN visitor_id END) AS iniciaram,
    COUNT(DISTINCT CASE WHEN event_name = 'video_25' THEN visitor_id END) AS chegaram_25,
    COUNT(DISTINCT CASE WHEN event_name = 'video_50' THEN visitor_id END) AS chegaram_50,
    COUNT(DISTINCT CASE WHEN event_name = 'video_75' THEN visitor_id END) AS chegaram_75,
    COUNT(DISTINCT CASE WHEN event_name = 'video_90' THEN visitor_id END) AS chegaram_90,
    COUNT(DISTINCT CASE WHEN event_name = 'video_complete' THEN visitor_id END) AS concluiram,
    ROUND(
        (COUNT(DISTINCT CASE WHEN event_name = 'video_complete' THEN visitor_id END) * 100.0) /
        NULLIF(COUNT(DISTINCT CASE WHEN event_name = 'video_start' THEN visitor_id END), 0),
        1
    ) AS taxa_conclusao_percentual
FROM video_events
WHERE video_id IS NOT NULL
GROUP BY video_id
ORDER BY video_id ASC;

-- 11. Vídeos mais assistidos (ordenados por visualização relevante >= 50% e conclusões)
SELECT
    video_id,
    MAX(video_name) AS video_name,
    COUNT(DISTINCT CASE WHEN event_name = 'video_50' THEN visitor_id END) AS visualizacoes_relevantes_50,
    COUNT(DISTINCT CASE WHEN event_name = 'video_complete' THEN visitor_id END) AS conclusoes,
    COUNT(DISTINCT CASE WHEN event_name = 'video_start' THEN visitor_id END) AS plays_iniciados
FROM video_events
WHERE video_id IS NOT NULL
GROUP BY video_id
ORDER BY visualizacoes_relevantes_50 DESC, conclusoes DESC;

-- 12. Quantos visitantes assistiram mais de um vídeo (considerando início ou >= 50%)
SELECT
    COUNT(*) AS visitantes_mais_de_um_video_iniciado
FROM (
    SELECT visitor_id
    FROM video_events
    WHERE event_name = 'video_start'
      AND video_id IS NOT NULL
    GROUP BY visitor_id
    HAVING COUNT(DISTINCT video_id) > 1
) AS multi_video_visitors;

-- Visitantes que assistiram parte relevante (50%+) de mais de um vídeo:
SELECT
    COUNT(*) AS visitantes_mais_de_um_video_50
FROM (
    SELECT visitor_id
    FROM video_events
    WHERE event_name = 'video_50'
      AND video_id IS NOT NULL
    GROUP BY visitor_id
    HAVING COUNT(DISTINCT video_id) > 1
) AS multi_video_50_visitors;

-- 13. Acessos por dia (visualizações e visitantes únicos diários)
SELECT
    DATE(created_at) AS data_acesso,
    COUNT(CASE WHEN event_name = 'page_view' THEN 1 END) AS visualizacoes_landing,
    COUNT(DISTINCT visitor_id) AS visitantes_unicos,
    COUNT(DISTINCT session_id) AS sessoes_unicas
FROM video_events
GROUP BY DATE(created_at)
ORDER BY data_acesso DESC;

-- 14. Visitantes que retornaram (mais de uma sessão distinta registrada)
SELECT
    COUNT(*) AS visitantes_que_retornaram
FROM (
    SELECT visitor_id
    FROM video_events
    GROUP BY visitor_id
    HAVING COUNT(DISTINCT session_id) > 1
) AS returning_visitors;

-- 15. Total de cliques em Compartilhar e WhatsApp
SELECT
    COUNT(CASE WHEN event_name = 'share_click' THEN 1 END) AS total_compartilhamentos,
    COUNT(DISTINCT CASE WHEN event_name = 'share_click' THEN visitor_id END) AS visitantes_que_compartilharam,
    COUNT(CASE WHEN event_name = 'whatsapp_click' THEN 1 END) AS total_cliques_whatsapp,
    COUNT(DISTINCT CASE WHEN event_name = 'whatsapp_click' THEN visitor_id END) AS visitantes_clicaram_whatsapp
FROM video_events;
