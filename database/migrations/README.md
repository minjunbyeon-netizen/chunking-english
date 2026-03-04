# DB 마이그레이션 이력

| 파일 | 적용일 | 내용 |
|------|--------|------|
| `001_initial.sql` | 2024 | 초기 스키마 (days, verbs, expressions, users, progress) |
| `002_b2b_organizations.sql` | 2026-03-04 | 지자체 허가코드 시스템 (organizations, page_views, users.org_id) |

## 적용 방법

```bash
# 신규 설치 (전체)
mysql -u root < database/schema.sql

# 기존 DB에 마이그레이션만 적용
mysql -u root chunking_english < database/migrations/002_b2b_organizations.sql
```

## 주의

- `ALTER TABLE users ADD COLUMN org_id` 는 이미 컬럼이 있으면 오류 발생
  → 이미 적용된 서버에서는 해당 줄을 건너뛰거나 `IF NOT EXISTS` 확인 후 실행
