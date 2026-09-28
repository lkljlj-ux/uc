CREATE TABLE IF NOT EXISTS uc_device_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    macid VARCHAR(100) NOT NULL,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_uc_device_token_hash (token_hash),
    KEY idx_uc_device_mac (macid, revoked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS uc_upload_events (
    macid VARCHAR(100) NOT NULL,
    event_id VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    sid VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (macid, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;