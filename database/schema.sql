-- =============================================
-- 청킹잉글리시 DB 스키마 (전체)
-- DB명: chunking_english
-- 최종 수정: 2026-03-04
-- =============================================
-- 적용 방법:
--   mysql -u root < database/schema.sql
-- 개별 마이그레이션:
--   mysql -u root chunking_english < database/migrations/002_b2b_organizations.sql
-- =============================================

CREATE DATABASE IF NOT EXISTS chunking_english
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE chunking_english;

-- ─────────────────────────────────────────────
-- 콘텐츠 테이블
-- ─────────────────────────────────────────────

-- ① 학습일
CREATE TABLE IF NOT EXISTS days (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    day_number   INT  NOT NULL UNIQUE,
    release_date DATE DEFAULT NULL,
    is_active    TINYINT(1) DEFAULT 1,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ② 동사 (1 Day = 3개)
CREATE TABLE IF NOT EXISTS verbs (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    day_id               INT          NOT NULL,
    order_num            INT          NOT NULL,   -- Day 내 순서 (1·2·3)
    global_num           INT          NOT NULL,   -- 전체 누적 번호
    verb_en              VARCHAR(100) NOT NULL,
    verb_kr              VARCHAR(100) NOT NULL,
    sentence_en          TEXT         DEFAULT NULL,
    sentence_kr          TEXT         DEFAULT NULL,
    sentence_audio_path  VARCHAR(500) DEFAULT NULL,
    FOREIGN KEY (day_id) REFERENCES days(id) ON DELETE CASCADE,
    UNIQUE KEY uq_day_order (day_id, order_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ③ 표현 (1 동사 = 7개)
CREATE TABLE IF NOT EXISTS expressions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    verb_id        INT          NOT NULL,
    order_num      INT          NOT NULL,         -- 1~7
    expression_en  VARCHAR(200) NOT NULL,
    expression_kr  VARCHAR(200) DEFAULT NULL,
    image_path     VARCHAR(500) DEFAULT NULL,
    audio_path     VARCHAR(500) DEFAULT NULL,
    FOREIGN KEY (verb_id) REFERENCES verbs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- B2B 지자체 테이블  [Migration 002]
-- ─────────────────────────────────────────────

-- ④ 지자체 (B2B 판매 단위)
CREATE TABLE IF NOT EXISTS organizations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    region       VARCHAR(50)  DEFAULT NULL,        -- 시도명 (서울·경기 등)
    license_code VARCHAR(30)  NOT NULL UNIQUE,     -- 관리자용 허가코드
    max_users    INT          NOT NULL DEFAULT 100, -- 계약 인원
    expires_at   DATE         DEFAULT NULL,         -- NULL = 무기한
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    note         TEXT         DEFAULT NULL,
    created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- 회원 테이블
-- ─────────────────────────────────────────────

-- ⑤ 회원
CREATE TABLE IF NOT EXISTS users (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    email               VARCHAR(100) NOT NULL UNIQUE,
    password            VARCHAR(255) NOT NULL,
    nickname            VARCHAR(50)  DEFAULT NULL,
    email_verified      TINYINT(1)   NOT NULL DEFAULT 0,
    verification_token  VARCHAR(64)  DEFAULT NULL,
    token_expires_at    DATETIME     DEFAULT NULL,
    role                ENUM('student','parent','admin') DEFAULT 'student',
    org_id              INT          DEFAULT NULL,  -- FK → organizations  [Migration 002]
    created_at          DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- 통계 테이블  [Migration 002]
-- ─────────────────────────────────────────────

-- ⑥ Day 조회수
CREATE TABLE IF NOT EXISTS page_views (
    id         BIGINT   AUTO_INCREMENT PRIMARY KEY,
    day_number SMALLINT NOT NULL,
    user_id    INT      DEFAULT NULL,
    org_id     INT      DEFAULT NULL,
    viewed_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_day  (day_number),
    INDEX idx_org  (org_id),
    INDEX idx_date (viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────
-- 학습 진도 테이블
-- ─────────────────────────────────────────────

-- ⑦ 표현별 진도
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

-- ⑧ Day별 진도
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
