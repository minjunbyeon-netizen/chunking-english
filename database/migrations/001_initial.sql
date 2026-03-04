-- =============================================
-- Migration 001: 초기 스키마
-- 설명: 콘텐츠(days/verbs/expressions), 회원(users), 진도(progress)
-- =============================================

CREATE TABLE IF NOT EXISTS days (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    day_number   INT  NOT NULL UNIQUE,
    release_date DATE DEFAULT NULL,
    is_active    TINYINT(1) DEFAULT 1,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS verbs (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    day_id               INT          NOT NULL,
    order_num            INT          NOT NULL,
    global_num           INT          NOT NULL,
    verb_en              VARCHAR(100) NOT NULL,
    verb_kr              VARCHAR(100) NOT NULL,
    sentence_en          TEXT         DEFAULT NULL,
    sentence_kr          TEXT         DEFAULT NULL,
    sentence_audio_path  VARCHAR(500) DEFAULT NULL,
    FOREIGN KEY (day_id) REFERENCES days(id) ON DELETE CASCADE,
    UNIQUE KEY uq_day_order (day_id, order_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expressions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    verb_id        INT          NOT NULL,
    order_num      INT          NOT NULL,
    expression_en  VARCHAR(200) NOT NULL,
    expression_kr  VARCHAR(200) DEFAULT NULL,
    image_path     VARCHAR(500) DEFAULT NULL,
    audio_path     VARCHAR(500) DEFAULT NULL,
    FOREIGN KEY (verb_id) REFERENCES verbs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    email               VARCHAR(100) NOT NULL UNIQUE,
    password            VARCHAR(255) NOT NULL,
    nickname            VARCHAR(50)  DEFAULT NULL,
    email_verified      TINYINT(1)   NOT NULL DEFAULT 0,
    verification_token  VARCHAR(64)  DEFAULT NULL,
    token_expires_at    DATETIME     DEFAULT NULL,
    role                ENUM('student','parent','admin') DEFAULT 'student',
    created_at          DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expression_progress (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    expression_id  INT NOT NULL,
    correct_count  INT NOT NULL DEFAULT 0,
    last_seen_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_expr (user_id, expression_id),
    FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (expression_id) REFERENCES expressions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS progress (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    day_id     INT NOT NULL,
    completed  TINYINT(1) DEFAULT 0,
    studied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_day (user_id, day_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (day_id)  REFERENCES days(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
