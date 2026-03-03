-- =============================================
-- 청킹잉글리시 DB 스키마
-- DB명: chunking_english
-- =============================================

CREATE DATABASE IF NOT EXISTS chunking_english
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE chunking_english;

-- ① 학습일 테이블
CREATE TABLE IF NOT EXISTS days (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    day_number   INT NOT NULL UNIQUE,
    release_date DATE,
    is_active    TINYINT(1) DEFAULT 1,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ② 동사 테이블 (1 Day = 3개)
CREATE TABLE IF NOT EXISTS verbs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    day_id       INT NOT NULL,
    order_num    INT NOT NULL,       -- 1, 2, 3 (Day 내 순서)
    global_num   INT NOT NULL,       -- 전체 누적 번호 (01, 02, 03 ...)
    verb_en      VARCHAR(100) NOT NULL,
    verb_kr      VARCHAR(100) NOT NULL,
    sentence_en  TEXT,
    sentence_kr  TEXT,
    FOREIGN KEY (day_id) REFERENCES days(id) ON DELETE CASCADE,
    UNIQUE KEY uq_day_order (day_id, order_num)
);

-- ③ 표현 테이블 (1 동사 = 7개)
CREATE TABLE IF NOT EXISTS expressions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    verb_id        INT NOT NULL,
    order_num      INT NOT NULL,     -- 1~7
    expression_en  VARCHAR(200) NOT NULL,
    expression_kr  VARCHAR(200),
    image_path     VARCHAR(500),     -- NULL = 이미지 없음
    FOREIGN KEY (verb_id) REFERENCES verbs(id) ON DELETE CASCADE
);

-- ④ 회원 테이블
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(100) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    nickname    VARCHAR(50),
    role        ENUM('student','parent','admin') DEFAULT 'student',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ⑤ 학습 진도 테이블
CREATE TABLE IF NOT EXISTS progress (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    day_id      INT NOT NULL,
    completed   TINYINT(1) DEFAULT 0,
    studied_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_day (user_id, day_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (day_id)  REFERENCES days(id)  ON DELETE CASCADE
);
