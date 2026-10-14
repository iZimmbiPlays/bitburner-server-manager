-- Run ONCE on an existing installation, after backing up the database.
USE bitburner_server_manager;
ALTER TABLE servers
    ADD COLUMN ram_gb DECIMAL(14,3) NOT NULL DEFAULT 0.000 AFTER hacking_level;
-- Zero means RAM has not yet been recorded for older servers.
