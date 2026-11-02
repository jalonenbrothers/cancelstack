-- CancelStack migration 001
-- Creates: cases, attempts
-- Safe to re-run: yes (IF NOT EXISTS)

CREATE TABLE IF NOT EXISTS cases (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token      CHAR(10)     NOT NULL UNIQUE,
    merchant   VARCHAR(120) NOT NULL,
    plan       VARCHAR(120) NULL,
    amount     DECIMAL(10,2) NULL,
    cycle      ENUM('monthly','annual','other') NULL,
    started    DATE NULL,
    ref        VARCHAR(120) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attempts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id         INT UNSIGNED NOT NULL,
    attempt_date    DATE NOT NULL,
    channel         ENUM('phone','email','web portal','chat','app','other') NOT NULL,
    outcome         ENUM('success','spoke-to-human','retention-offer-received',
                         'no-confirmation','still-charged','other') NOT NULL,
    confirmation    VARCHAR(120) NULL,
    note            TEXT NULL,
    screenshot_url  VARCHAR(500) NULL,
    logged_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attempts_case
        FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    INDEX idx_attempts_case (case_id, attempt_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
