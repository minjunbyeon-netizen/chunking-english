-- =============================================
-- Migration 002: B2B 지자체 허가코드 시스템
-- 적용일: 2026-03-04
-- 설명: 지자체 관리, 조회수 추적, 회원-지자체 연결
-- =============================================

-- ① 지자체 테이블
CREATE TABLE IF NOT EXISTS organizations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    region       VARCHAR(50)  DEFAULT NULL,
    license_code VARCHAR(30)  NOT NULL UNIQUE,
    max_users    INT          NOT NULL DEFAULT 100,
    expires_at   DATE         DEFAULT NULL,          -- NULL = 무기한
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    note         TEXT         DEFAULT NULL,
    created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ② Day 조회수 테이블
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

-- ③ users 테이블에 org_id 컬럼 추가
--    (이미 적용된 경우 오류 무시: ALTER TABLE은 IF NOT EXISTS 미지원)
ALTER TABLE users
    ADD COLUMN org_id INT DEFAULT NULL AFTER role;

-- =============================================
-- 롤백 (필요 시 수동 실행)
-- =============================================
-- ALTER TABLE users DROP COLUMN org_id;
-- DROP TABLE IF EXISTS page_views;
-- DROP TABLE IF EXISTS organizations;
