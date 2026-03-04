# 청킹잉글리시 (Chunking English)
> Wizard Chunking Together - 아이와 엄마가 함께하는 청킹 영어학습 플랫폼

---

## 프로젝트 개요

- **목적**: 청킹(Chunking) 방식으로 영어 표현을 매일 학습하는 웹 서비스
- **대상**: 초등학생 및 학부모 (Kids & Mom)
- **스택**: PHP + MySQL + HTML/CSS/JS (XAMPP 로컬 환경)
- **현재 상태**: Phase 1 완료 — 백엔드 DB 연동, book.php 동적화, 인코딩 정상

---

## 콘텐츠 구조

```
1일 = 동사 3개
1동사 = 표현 7개 (이미지 + 한국어 뜻)
전체 = 50일 × 3동사 × 7표현 = 1,050개
```

### 이미지 경로 규칙
```
asset/img/day {N}/{GV:02d}. {verb_en}/{expression_snake}.png
예) asset/img/day 1/01. have/have_a_dream.png
    GV = 전체 누적 동사 번호 (01~150)
```

---

## 파일 구조

```
chunking-english/
├── index.php             ← 메인 학습 페이지 (Day 선택 + 드릴 + 조회수 배지)
├── book.php              ← A4 학습지 뷰어 (?day=N)
├── register.php          ← 회원가입 (지자체 드롭다운 선택)
├── login.php             ← 로그인
├── verify_email.php      ← 이메일 인증
├── find_password.php     ← 비밀번호 찾기
├── board.php             ← 커뮤니티 게시판
├── notice.php            ← 공지사항
├── tree.php              ← My English Tree (게이미피케이션)
├── together.html         ← 함께하기 페이지
├── check_mapping.php     ← 이미지 매핑 시각 확인 (?day=N)
│
├── admin/
│   ├── _auth.php         ← 관리자 인증 미들웨어
│   ├── index.php         ← Day 목록 관리
│   ├── overview.php      ← 전체 현황
│   ├── dashboard.php     ← 통계 대시보드 (신규 가입·조회수·지자체별 현황)
│   ├── organizations.php ← 지자체 관리 (허가코드 발급·활성화 토글)
│   └── users.php         ← 사용자 관리 (수동 인증·지자체 필터)
│
├── api/
│   ├── auth/
│   │   ├── login.php     ← POST {email, password} → 세션 발급
│   │   ├── register.php  ← POST {email, password, nickname, org_id}
│   │   ├── logout.php    ← 세션 제거
│   │   └── check.php     ← 로그인 상태 확인
│   ├── content/
│   │   └── get_day.php   ← GET ?day=N → 동사+표현 JSON
│   ├── progress/
│   │   ├── save.php      ← 학습 완료 저장
│   │   └── my_tree.php   ← 완료한 Day 목록 (tree.php 연동)
│   ├── stats/
│   │   └── record_view.php ← POST {day_number} → 조회수 기록+반환
│   └── download/
│       └── day_audio.php ← GET ?day=N → MP3 ZIP 다운로드
│
├── config/
│   ├── db.php            ← DB 연결 설정 (gitignore)
│   └── db.example.php    ← DB 연결 템플릿
│
├── database/
│   ├── schema.sql        ← 전체 DB 생성 스크립트 (최초 1회)
│   ├── data.sql          ← 콘텐츠 데이터 (50일/150동사/1050표현)
│   ├── import.py         ← Excel → data.sql 변환 스크립트
│   ├── import_excel.py   ← 보조 임포트 스크립트
│   ├── generate_audio.py           ← TTS 오디오 생성 (Web Speech)
│   ├── generate_audio_elevenlabs.py ← ElevenLabs TTS
│   ├── generate_audio_typecast.py  ← Typecast TTS (현재 사용)
│   └── migrations/
│       ├── 001_initial.sql          ← 초기 스키마
│       ├── 002_b2b_organizations.sql ← 지자체 허가코드 시스템
│       ├── fix_image_mapping_20260304.sql
│       └── README.md
│
├── asset/
│   ├── 청킹 Basic _20260303.xlsx    ← 원본 콘텐츠 Excel
│   ├── audio/                       ← TTS MP3 파일 (day별 폴더)
│   ├── img/                         ← 이미지 1.6GB (gitignore, Google Drive)
│   └── chunkingEnglishKidsAndMom_소스코드/  ← 프론트엔드 원본 소스 백업
│
├── chunkingEnglishKidsAndMom/  ← 프론트엔드 UI 소스 (Kids&Mom 버전)
│   ├── index.php / login.php / book.php 등
│   ├── css/style.css
│   └── js/ (script.js, fonts.js, tailwind-config.js)
│
├── css/style.css         ← 메인 스타일시트
├── js/script.js          ← 메인 자바스크립트
├── img/                  ← UI 이미지 자산
│
├── CHANGELOG.txt         ← 작업 이력 전체
├── WORKTHROUGH.md        ← 개발 과정 노트
├── API_가이드_프론트엔드용.txt
├── 집_세팅_가이드.txt
└── .gitignore
```

---

## DB 스키마 (chunking_english)

| 테이블 | 내용 |
|--------|------|
| `days` | 50일치 날짜 정보 |
| `verbs` | 동사 150개 (day_id, global_num, verb_en/kr, sentence_en/kr, audio) |
| `expressions` | 표현 1050개 (verb_id, expression_en/kr, image_path, audio_path) |
| `users` | 회원 (email, bcrypt password, nickname, role, org_id, email_verified) |
| `progress` | 학습 진도 (user_id, day_id, completed) |
| `expression_progress` | 표현별 정답 횟수 (user_id, expression_id, correct_count) |
| `organizations` | 지자체 (name, region, license_code, max_users, expires_at, is_active) |
| `page_views` | 조회수 로그 (day_number, user_id, org_id, viewed_at) |

**로컬 DB 접속**: root / 비밀번호없음 / localhost / chunking_english

---

## 사용 기술 스택

| 분류 | 기술 |
|------|------|
| 서버 | XAMPP (Apache + MySQL + PHP) |
| DB | MySQL (MariaDB 10.4), charset utf8mb4 |
| 백엔드 | PHP PDO, session 기반 인증 |
| 데이터 임포트 | Python 3 + openpyxl |
| 프론트 | HTML / CSS / Vanilla JS |
| 폰트 | Chewy, Quicksand, Jua, Noto Sans KR |
| 아이콘 | Font Awesome 6 |
| TTS | Web Speech API (브라우저 내장) |
| 인쇄 | CSS @media print (A4 최적화) |
| 버전관리 | Git + GitHub |
| 이미지 공유 | Google Drive 데스크톱 앱 |

---

## 세팅 방법 (새 컴퓨터)

```bash
# 1. 코드 클론
git clone https://github.com/minjunbyeon-netizen/chunking-english.git 03_chunking

# 2. DB 설정 복사
cp config/db.example.php config/db.php
# config/db.php에서 DB 접속 정보 수정

# 3. DB 생성
mysql -u root < database/schema.sql

# 4. 데이터 임포트
mysql -u root --default-character-set=utf8mb4 chunking_english < database/data.sql

# 5. 이미지 동기화
# Google Drive에서 chunking-english/img/ 폴더를 asset/img/ 에 복사
```

---

## 기능 구현 현황

### Phase 1 — DB & 콘텐츠 백엔드 ✅ 완료
- [x] DB 스키마 설계 및 생성
- [x] Excel → SQL 파이썬 임포트 스크립트
- [x] MySQL 데이터 임포트 (50일/150동사/1050표현)
- [x] PHP API 백엔드 (content, auth, progress)
- [x] book.php 동적 PHP 변환 (DB 연동, prev/next 네비게이션)
- [x] 이미지 매핑 확인 페이지 (check_mapping.php)
- [x] 한글 인코딩 수정 (SET NAMES utf8mb4)
- [x] GitHub 연동 + Google Drive 이미지 동기화

### Phase 2 — B2B 지자체 시스템 ✅ 완료 (2026-03-04)
- [x] organizations 테이블 + page_views 테이블 생성
- [x] 전국 230 기초자치단체 시드 데이터 입력
- [x] register.php 지자체 드롭다운 선택 (optgroup 지역별)
- [x] api/auth/register.php 지자체 검증 (활성·만료·인원초과)
- [x] api/auth/login.php org_id 세션 저장
- [x] api/stats/record_view.php 조회수 기록 API
- [x] index.php 우하단 조회수 배지 (Day별 실시간)
- [x] admin/dashboard.php 통계 대시보드
- [x] admin/organizations.php 지자체 관리 (아코디언+검색)
- [x] admin/users.php 사용자 관리
- [x] DB 마이그레이션 파일 정리 (001~002)

### Phase 3 — TTS 오디오 ✅ 완료 (2026-03-04)
- [x] Typecast API 연동 generate_audio_typecast.py
- [x] api/download/day_audio.php MP3 ZIP 다운로드
- [x] admin/index.php Day별 ↓MP3 ZIP 버튼

### Phase 4 — 이미지 정리
- [x] 파일명 숫자 오류 23개 수정 (plant/keep/be/think_in/fix 폴더) ✅ 2026-03-05
- [ ] 누락 이미지 23개 제작 (Day29/31/34/14/47)

### Phase 5 — 프론트엔드 연동 (진행 예정)
- [ ] login.php UI → api/auth/login.php 연결
- [ ] index.php 동적화 완성 (DB 연동)
- [ ] tree.php → api/progress/my_tree.php 연동

### Phase 6 — 배포
- [ ] 외부 서버 배포 (Railway 검토 중, Cafe24 비선호)

---

## 이미지 자산 현황

> DB 전체: 5,250개 표현 (Day 1~201, 한국어 동사 포함)
> 이미지 폴더: Day 1~50 기준 global 1~150 (영어 동사)

| 항목 | 수치 |
|------|------|
| 이미지 폴더 내 전체 파일 | ~1,050개 |
| DB 매핑 성공 | **836개** |
| DB NULL (미매핑) | 214개 |
| ~~원인① 파일명 숫자 오류~~ | ~~23개~~ → ✅ 수정 완료 (2026-03-05) |
| 원인② 이미지 자체 누락 | ~25개 (listen to 7, speak 7, do yoga 1 등) |
| 원인③ DB 내용 변경으로 불일치 | ~7개 (improve) |
| 원인④ Day 42~50 신규 콘텐츠 | ~189개 (global 154~186, 이미지 미제작) |

### ✅ 완료: 파일명 숫자 오류 23개 수정 (2026-03-05)
| Day | 동사 폴더 | 처리 |
|-----|-----------|------|
| Day 5 | 14. plant | ✅ _숫자 제거 + DB 업데이트 (7개) |
| Day 5 | 15. keep  | ✅ _숫자 제거 + DB 업데이트 (7개) |
| Day 4 | 12. think in | ✅ think_in_Korean_37 → think_in_Korean (1개) |
| Day 10 | 30. be | ✅ _숫자 제거 + DB 업데이트 (7개) |
| Day 47 | 141. fix | ✅ fix_the_topic_119 → fix_the_topic (1개) |

### 이미지 누락 목록 (제작 필요)
| Day | 동사 폴더 | 누락 수 | 비고 |
|-----|-----------|---------|------|
| Day 8  | 25. improve | 7개 | DB 표현 변경으로 기존 이미지 불일치 |
| Day 12 | 42. do | 1개 | do yoga.png 미존재 |
| Day 20 | 72. pack | 1개 | pack_the_lunch.png 미존재 |
| Day 24 | 86. listen to | 7개 | 폴더 비어있음 |
| Day 25 | 88. arrive at | 1개 | arrive_at_the_office.png 미존재 |
| Day 28 | 100. speak | 7개 | 폴더 비어있음 |
| Day 38 | 140. be | 1개 | be_dishonest.png 미존재 |
| Day 42~50 | global 154~186 | ~189개 | 신규 콘텐츠, 이미지 미제작 |

---

## 주요 기술 이슈 & 해결책

| 이슈 | 원인 | 해결 |
|------|------|------|
| 동사 153개 파싱 오류 | Day 마커를 START로 해석 | 마커는 END 구분선 → 스킵 처리 |
| 이미지 매핑 90개만 됨 | Day 번호 오류로 폴더 경로 틀림 | 위 마커 버그 수정으로 해결 |
| check_mapping.php 전부 ❌ | zero-padding 누락 (`1.`→`01.`) | `str_pad($gv, 2, '0', STR_PAD_LEFT)` |
| 한글 깨짐 | data.sql에 SET NAMES 없음 | `SET NAMES utf8mb4;` 추가 후 재임포트 |
| 이미지 URL 공백 | 폴더명에 공백 포함 | `rawurlencode()` per path segment |
