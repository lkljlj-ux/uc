CREATE TABLE IF NOT EXISTS uc_operators (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    operator_name VARCHAR(150) NOT NULL,
    aadhaar_encrypted TEXT NOT NULL,
    aadhaar_last4 CHAR(4) NOT NULL,
    auth_token_encrypted TEXT NOT NULL,
    bio_token_encrypted TEXT NOT NULL,
    pid_data_encrypted LONGTEXT NOT NULL,
    otp_encrypted TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_uc_operator_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS uc_machine_map (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    macid VARCHAR(100) NOT NULL,
    uc_operator_id INT UNSIGNED NOT NULL,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_uc_machine_macid (macid),
    KEY idx_uc_machine_operator (uc_operator_id),
    CONSTRAINT fk_uc_machine_operator
        FOREIGN KEY (uc_operator_id) REFERENCES uc_operators(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;