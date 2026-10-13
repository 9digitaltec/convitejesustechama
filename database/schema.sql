-- ============================================================================
-- 24º Jesus te Chama — Schema MySQL
-- Compatível com MySQL 5.7+ / 8.0+ e MariaDB (Hostinger)
-- ============================================================================

CREATE TABLE IF NOT EXISTS video_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    visitor_id VARCHAR(64) NOT NULL,
    session_id VARCHAR(64) NOT NULL,

    event_name VARCHAR(50) NOT NULL,

    video_id VARCHAR(100) DEFAULT NULL,
    video_name VARCHAR(255) DEFAULT NULL,

    progress TINYINT UNSIGNED DEFAULT NULL,

    page_url VARCHAR(500) DEFAULT NULL,
    referrer VARCHAR(500) DEFAULT NULL,

    screen_width SMALLINT UNSIGNED DEFAULT NULL,
    screen_height SMALLINT UNSIGNED DEFAULT NULL,

    language VARCHAR(20) DEFAULT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_visitor_id (visitor_id),
    INDEX idx_session_id (session_id),
    INDEX idx_event_name (event_name),
    INDEX idx_video_id (video_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
