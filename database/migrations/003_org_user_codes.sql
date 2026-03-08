-- Migration 003: 자치단체 4자리 고유번호 + 개인 고유번호 시스템
-- 실행: mysql -u root chunking_english < database/migrations/003_org_user_codes.sql

-- 자치단체 고유번호 (4자리 숫자, 예: 1234)
ALTER TABLE organizations
    ADD COLUMN org_code VARCHAR(4) NULL UNIQUE AFTER name;

-- 개인 고유번호 (형식: [org_code]-[100001~], 예: 1234-100001)
ALTER TABLE users
    ADD COLUMN user_code VARCHAR(12) NULL UNIQUE AFTER org_id;
