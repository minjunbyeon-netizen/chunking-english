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
03_chunking/
├── book.php              ← A4 학습지 뷰어 (동적 PHP, ?day=N 파라미터)
├── index.php             ← 메인 페이지 (정적, DB 연동 예정)
├── login.php             ← 로그인 UI (API 연결 예정)
├── find_password.php     ← 비밀번호 찾기 UI
├── board.php             ← 커뮤니티 게시판 UI
├── notice.php            ← 공지사항 UI
├── tree.php              ← My English Tree (게이미피케이션)
├── together.html         ← 함께하기 페이지
├── check_mapping.php     ← 이미지 매핑 시각 확인 도구 (?day=N)
├── config/
│   ├── db.php            ← DB 연결 설정 (gitignore - 개인 설정)
│   └── db.example.php    ← DB 연결 템플릿 (복사해서 db.php로 사용)
├── api/
│   ├── content/
│   │   └── get_day.php        ← GET ?day=N → 동사+표현 JSON 반환
│   ├── auth/
│   │   ├── login.php          ← POST {email, password}
│   │   ├── register.php       ← POST {email, password, nickname}
│   │   ├── logout.php         ← 세션 제거
│   │   └── check.php          ← 로그인 상태 확인
│   └── progress/
│       ├── save.php           ← 학습 완료 저장
│       └── my_tree.php        ← 완료한 Day 목록 반환 (tree.php 연동)
├── database/
│   ├── schema.sql         ← DB 생성 스크립트 (최초 1회 실행)
│   ├── import.py          ← Excel → data.sql 파이썬 스크립트
│   └── data.sql           ← 생성된 SQL 데이터 (50일치, utf8mb4)
├── asset/
│   ├── 청킹 Basic _20260303.xlsx   ← 원본 콘텐츠 Excel
│   └── img/               ← 이미지 1.6GB (gitignore, Google Drive 동기화)
├── css/ js/ img/          ← 기존 프론트엔드 자산
└── .gitignore
```

---

## DB 스키마 (chunking_english)

| 테이블 | 내용 |
|--------|------|
| `days` | 50일치 날짜 정보 |
| `verbs` | 동사 150개 (day_id, global_num, verb_en/kr, sentence_en/kr) |
| `expressions` | 표현 1050개 (verb_id, expression_en/kr, image_path) |
| `users` | 회원 (email, password bcrypt, nickname, role) |
| `progress` | 학습 진도 (user_id, day_id, completed) |

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

### Phase 2 — 프론트엔드 연동 (진행 예정)
- [ ] login.php UI → api/auth/login.php 연결
- [ ] index.php 동적화 (DB 연동)
- [ ] tree.php → api/progress/my_tree.php 연동

### Phase 3 — 이미지 정리 (진행 예정)
- [ ] 파일명 숫자 오류 23개 수정 (plant/keep/be/think_in/fix 폴더)
- [ ] 누락 이미지 23개 제작 (Day29/31/34/14/47)

### Phase 4 — 배포
- [ ] 외부 서버 배포 (Railway 검토 중, Cafe24 비선호)

---

## 이미지 자산 현황

| 항목 | 수치 |
|------|------|
| 필요 이미지 | 1,050개 |
| DB 매핑 성공 | **976개** |
| DB NULL (미매핑) | 74개 |
| 원인① 파일명 숫자 오류 | 23개 |
| 원인② 이미지 자체 누락 | 23개 |

### 파일명 숫자 오류 목록 (수정 필요)
| Day | 동사 | 문제 파일 (숫자 제거 필요) |
|-----|------|--------------------------|
| Day 5 | plant | plant_the_tree_50.png 외 6개 |
| Day 5 | keep | keep_strong_60.png 외 6개 |
| Day 4 | think in | think_in_Korean_37.png |
| Day 10 | be | be_proud_57.png 외 6개 |
| 미확인 | fix | fix_the_topic_119.png |

### 이미지 누락 목록 (제작 필요)
| Day | 동사 폴더 | 누락 수 |
|-----|-----------|---------|
| Day 14 | 42. do | 1개 |
| Day 29 | 86. listen to | **7개 전부** |
| Day 31 | 93. read | **7개 전부** |
| Day 34 | 100. speak | **7개 전부** |
| Day 47 | 140. be | 1개 |

---

## 주요 기술 이슈 & 해결책

| 이슈 | 원인 | 해결 |
|------|------|------|
| 동사 153개 파싱 오류 | Day 마커를 START로 해석 | 마커는 END 구분선 → 스킵 처리 |
| 이미지 매핑 90개만 됨 | Day 번호 오류로 폴더 경로 틀림 | 위 마커 버그 수정으로 해결 |
| check_mapping.php 전부 ❌ | zero-padding 누락 (`1.`→`01.`) | `str_pad($gv, 2, '0', STR_PAD_LEFT)` |
| 한글 깨짐 | data.sql에 SET NAMES 없음 | `SET NAMES utf8mb4;` 추가 후 재임포트 |
| 이미지 URL 공백 | 폴더명에 공백 포함 | `rawurlencode()` per path segment |
