-- Milestone 06: run ONCE against your existing database after making a HeidiSQL backup.
-- Choose database bitburner_server_manager before executing. Do not rerun schema.sql.
CREATE TABLE script_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    builtin_key VARCHAR(180) NULL,
    filename VARCHAR(180) NOT NULL,
    category VARCHAR(60) NOT NULL,
    difficulty VARCHAR(30) NOT NULL DEFAULT 'Beginner',
    description TEXT NOT NULL,
    usage_command VARCHAR(500) NOT NULL DEFAULT '',
    argument_help VARCHAR(500) NOT NULL DEFAULT '',
    dependencies VARCHAR(500) NOT NULL DEFAULT '',
    caution TEXT NOT NULL,
    code LONGTEXT NOT NULL,
    version_label VARCHAR(40) NOT NULL DEFAULT '1.0.0',
    is_builtin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_script_builtin (builtin_key),
    INDEX idx_script_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE script_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    script_id BIGINT UNSIGNED NOT NULL,
    version_label VARCHAR(40) NOT NULL,
    code LONGTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_script_revision_entry FOREIGN KEY (script_id) REFERENCES script_entries(id) ON DELETE CASCADE,
    INDEX idx_script_revisions (script_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
