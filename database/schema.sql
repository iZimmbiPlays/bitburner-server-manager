-- Run this script inside a dedicated local MySQL database.
CREATE DATABASE IF NOT EXISTS bitburner_server_manager
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bitburner_server_manager;

CREATE TABLE IF NOT EXISTS game_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    bitnode VARCHAR(80) NULL,
    notes TEXT NULL,
    status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sessions_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS servers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    game_session_id BIGINT UNSIGNED NOT NULL,
    parent_server_id BIGINT UNSIGNED NULL,
    hostname VARCHAR(255) NOT NULL,
    has_root_access BOOLEAN NOT NULL DEFAULT FALSE,
    has_backdoor BOOLEAN NOT NULL DEFAULT FALSE,
    ports_required TINYINT UNSIGNED NOT NULL DEFAULT 0,
    hacking_level INT UNSIGNED NOT NULL DEFAULT 1,
    ram_gb DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_servers_session FOREIGN KEY (game_session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_servers_parent FOREIGN KEY (parent_server_id) REFERENCES servers(id) ON DELETE RESTRICT,
    CONSTRAINT uq_session_hostname UNIQUE (game_session_id, hostname),
    CONSTRAINT chk_ports_required CHECK (ports_required <= 5),
    INDEX idx_servers_parent (parent_server_id)
) ENGINE=InnoDB;

-- Application-layer rules to implement next:
-- parent must belong to the same session; no hard-coded nesting limit;
-- no circular ancestry; 'home' has no parent; archived sessions are read-only.
