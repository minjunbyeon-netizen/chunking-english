# 청킹잉글리시 (Chunking English)

> 아이와 엄마가 함께하는 청킹 영어학습 플랫폼

---

## 현재 배포 환경

| 항목 | 내용 |
|------|------|
| 플랫폼 | **GCP App Engine Standard (PHP 8.3)** |
| URL | https://chunking-english-489506.du.r.appspot.com |
| DB | Cloud SQL MySQL (unix socket) |
| 설정 파일 | `app.yaml`, `config/db.php` |
| GitHub | https://github.com/minjunbyeon-netizen/chunking-english |
| 인스턴스 | F1 (최소), max 1 |

> **이사 예정**: 한글 인코딩 깨짐 이슈로 새 서버로 이전 작업 중

---

## 이사(마이그레이션) 체크리스트

### 1. DB 덤프
```bash
# GCP Cloud SQL에서 덤프 (또는 chunking_english_dump.sql 사용)
mysqldump -u chunking_user -p chunking_english > chunking_english_dump.sql
```

### 2. 새 서버 환경 필수 사항
- PHP 8.0 이상
- MySQL 8.0 이상 (utf8mb4 + utf8mb4_unicode_ci)
- `mbstring`, `pdo_mysql` PHP 확장 필수

### 3. 한글 인코딩 필수 설정
```sql
-- DB 생성 시 반드시 utf8mb4 지정
CREATE DATABASE chunking_english
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- DB 연결 후 확인
SHOW VARIABLES LIKE 'character_set%';
-- character_set_database, character_set_server 모두 utf8mb4여야 함
```

```php
// config/db.php DSN에 charset=utf8mb4 포함 (현재 적용됨)
$dsn = "mysql:host=...;dbname=chunking_english;charset=utf8mb4";
```

```ini
; php.ini 또는 .htaccess
default_charset = "UTF-8"
mbstring.internal_encoding = UTF-8
```

### 4. config/db.php 수정 항목
```php
// 새 서버에 맞게 아래 값 변경
$dsn    = "mysql:host=새서버호스트;dbname=chunking_english;charset=utf8mb4";
$dbUser = '새DB유저';
$dbPass = '새DB패스워드';
define('APP_BASE', '');           // 도메인 루트면 빈 값
define('APP_URL',  'https://새도메인');
```

### 5. 세션 설정
- 현재 PHP 기본 파일 세션 사용 (`config/session_handler.php` 정의만 있고 미적용)
- 새 서버가 멀티 인스턴스라면 DB 세션 핸들러 활성화 필요

```php
// config/db.php 하단에 추가하면 DB 세션 활성화됨
require_once __DIR__ . '/session_handler.php';
$handler = new DbSessionHandler($pdo);
session_set_save_handler($handler, true);
```

---

## 프로젝트 개요

| 항목 | 내용 |
|------|------|
| 목적 | 청킹 방식 영어 표현 매일 학습 |
| 대상 | 초등학생 + 학부모 |
| 스택 | PHP + MySQL + HTML/CSS/JS |
| 콘텐츠 | 50일 × 3동사 × 7표현 = 1,050개 |

---

## 파일 구조

```
chunking-english/
├── index.php             메인 학습 페이지 (트레인 맵 + 카드 드릴)
├── book.php              A4 E-Book 학습지 뷰어 (?day=N)
├── together.html         함께하기 모달 (Firebase 실시간)
├── board.php             커뮤니티 게시판 (lazy iframe)
├── login.php             로그인 (AJAX)
├── register.php          회원가입 (지자체 인증코드)
├── tree.php              나무 성장 UI (DB 미연동)
├── notice.php            공지사항 (DB 미연동)
├── find_password.php     비밀번호 찾기 (미연동)
├── admin/
│   ├── _auth.php         관리자 인증 (role=admin 필수)
│   ├── dashboard.php     대시보드
│   ├── index.php         Day 목록 (이미지/MP3 현황)
│   ├── overview.php      전체 현황 + DB 동기화
│   ├── organizations.php 지자체 관리
│   ├── users.php         사용자 관리
│   ├── generate_audio.php TTS 오디오 생성
│   └── debug.php         디버그 도구 (Day 직접 이동, TTS 테스트)
├── api/
│   ├── auth/             login / register / logout / check
│   ├── content/          get_day.php
│   ├── progress/         save.php / my_tree.php
│   ├── stats/            record_view.php
│   └── download/         day_audio.php / day_mp3_merged.php / day_pdf.php
├── config/
│   ├── db.php            DB 연결 + 상수 정의
│   ├── auth.php          JWT 등 인증 유틸
│   └── session_handler.php DB 세션 핸들러 (현재 미적용)
├── css/style.css
├── js/script.js          메인 JS (TTS, 카드, 맵, 오디오)
├── asset/
│   ├── img/day N/        이미지 (836개 매핑, ~214개 누락)
│   └── audio/day N/      MP3 (Typecast TTS)
├── database/
│   ├── schema.sql        테이블 DDL
│   └── data.sql          콘텐츠 데이터
├── app.yaml              GCP App Engine 설정
└── chunking_english_dump.sql  전체 DB 덤프
```

---

## DB 스키마

| 테이블 | 설명 |
|--------|------|
| days | 50일 정보 |
| verbs | 동사 150개 (global_num 01~150) |
| expressions | 표현 1050개 (image_path, audio_path) |
| users | 회원 (email, bcrypt, role, org_id) |
| progress | 학습 진도 (user_id, day_id, completed) |
| expression_progress | 표현별 정답 횟수 |
| organizations | 지자체 (org_code 4자리, license_code) |
| page_views | 조회수 로그 |
| sessions | DB 세션 (현재 미사용) |

---

## 2026-03-09 주요 수정 내역

| 항목 | 내용 |
|------|------|
| TTS 클리핑 | `cancel()` 후 150ms 지연으로 "I" 첫 단어 잘림 해결 |
| 카드 Listen 버튼 | 오른쪽 하단에 7문장 1회씩 재생 버튼 추가 |
| Listen & Repeat | 항상 1인칭(I ~) 문장 7번 재생으로 고정 |
| 디버그 페이지 | admin/debug.php — Day 직접 이동, TTS 테스트, 진도 초기화 |
| 스피너 무한 | board.php iframe lazy load로 변경 |
| 관리자 홈 버튼 | 모든 admin 페이지 좌하단 "학습 홈" 플로팅 버튼 |
| admin 성능 | file_exists() 2100회 → 0회로 DB 경로 판단으로 대체 |

---

## 남은 작업

1. **누락 이미지 ~214개** 제작 (ComfyUI)
2. **tree.php / board.php / notice.php** DB 연동
3. **find_password.php** 이메일 발송 (Resend API)
4. **새 서버 이사** + 한글 인코딩 완전 해결 + HTTPS + 도메인

---

## 로컬 개발 세팅 (XAMPP)

```
1. C:\xampp\htdocs\chunking-english 에 클론
2. config/db.php — 로컬 설정 확인 (host=localhost, user=root, pass=)
3. XAMPP Apache + MySQL 실행
4. http://localhost/chunking-english 접속
5. chunking_english_dump.sql 임포트
```

> 주의: PHP 내장 서버(php -S)로 열면 DB 연결 실패. 반드시 XAMPP Apache 사용.

---

*Last updated: 2026-03-09*
