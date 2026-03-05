# 청킹잉글리시 (Chunking English)
> Wizard Chunking Together - 아이와 엄마가 함께하는 청킹 영어학습 플랫폼

---

## 프로젝트 개요

- **목적**: 청킹(Chunking) 방식으로 영어 표현을 매일 학습하는 웹 서비스
- **대상**: 초등학생 및 학부모 (Kids & Mom)
- **스택**: PHP + MySQL + HTML/CSS/JS (XAMPP 로컬 환경)
- **현재 상태**: Phase 1~3 완료, Phase 4(이미지) 진행중, Phase 5(프론트 연동) 대부분 완료

---

## 콘텐츠 구조

```
1일 = 동사 3개 / 1동사 = 표현 7개 (이미지 + 한국어 뜻)
전체 = 50일 x 3동사 x 7표현 = 1,050개
```

### 이미지/오디오 경로 규칙
```
asset/img/day {N}/{GV:02d}. {verb_en}/{expression_snake}.png
asset/audio/day {N}/{GV:02d}. {verb_en}/{expression_snake}.mp3
GV = 전체 누적 동사 번호 (01~150)
```

---

## 파일 구조

```
chunking-english/
+-- index.php             <- 메인 학습 페이지 (맵+드릴+카드수집+게시판iframe)
+-- book.php              <- A4 E-Book 학습지 뷰어 (?day=N, PDF 저장)
+-- together.html         <- 함께하기 모달 (학습완료/자료받기/음원받기)
+-- board.php             <- 커뮤니티 게시판 (iframe 임베드)
+-- login.php             <- 로그인 (AJAX, 이메일 기반)
+-- register.php          <- 회원가입 (지자체 드롭다운)
+-- tree.php / notice.php / find_password.php  <- UI만, DB 미연동
+-- admin/                <- 관리자 (Day관리, 대시보드, 지자체, 사용자)
+-- api/auth/             <- 로그인/회원가입/로그아웃 API
+-- api/content/          <- get_day.php (동사+표현 JSON)
+-- api/progress/         <- save.php (학습완료), my_tree.php
+-- api/stats/            <- record_view.php (조회수)
+-- api/download/         <- day_audio.php (ZIP), day_mp3_merged.php (합본MP3)
+-- config/db.php         <- DB 연결 (gitignore)
+-- database/             <- schema.sql, data.sql, import.py, TTS 스크립트
+-- css/style.css / js/script.js / img/
```

---

## DB 스키마 (chunking_english)

| 테이블 | 내용 |
|--------|------|
| days | 50일치 날짜 정보 |
| verbs | 동사 150개 (day_id, global_num, verb_en/kr) |
| expressions | 표현 1050개 (verb_id, expression_en/kr, image_path, audio_path) |
| users | 회원 (email, bcrypt password, nickname, role, org_id) |
| progress | 학습 진도 (user_id, day_id, completed) |
| expression_progress | 표현별 정답 횟수 |
| organizations | 지자체 (license_code, max_users, expires_at) |
| page_views | 조회수 로그 |

---

## 기능 구현 현황

### Phase 1 -- DB & 콘텐츠 백엔드 (완료)
- [x] DB 스키마 + Excel->SQL 임포트 + PHP API 백엔드
- [x] book.php 동적 PHP 변환 (DB 연동, Day 네비게이션)

### Phase 2 -- B2B 지자체 시스템 (완료 2026-03-04)
- [x] 전국 230 기초자치단체 + 허가코드 + 조회수 기록
- [x] 관리자 대시보드/지자체관리/사용자관리

### Phase 3 -- TTS 오디오 (완료 2026-03-04)
- [x] Typecast API TTS + MP3 ZIP 다운로드

### Phase 4 -- 이미지 정리 (진행중)
- [x] 파일명 오류 수정 + 이미지 재매핑 836개 -- 2026-03-05
- [ ] **누락 이미지 ~214개 제작 필요**

### Phase 5 -- 프론트엔드 연동 (대부분 완료 2026-03-05)
- [x] 카드 플립 애니메이션 + 씨앗 수집 (scaleX flip)
- [x] Listen & Repeat: 카드별 문장 패턴 매핑 x7회 (Web Speech TTS)
- [x] 오디오 재생 중 모달 유지, 종료시 자동 닫힘
- [x] Together 모달: completedDays DB 연동 (완료/진행중/잠김 3상태)
- [x] Day 완료시 DB 저장 (api/progress/save.php)
- [x] 음원받기: Day별 MP3 21개 합본 다운로드
- [x] 자료받기: 해당 Day book.php 새 탭 열기 (E-Book PDF)
- [x] 게시판 섹션: board.php iframe + 플로팅 버튼
- [x] 디자이너 CSS 머지 (together-fixed, board-section, 모바일 반응형)
- [x] board.php iframe 감지시 헤더 숨김
- [x] login.php AJAX 로그인 연동
- [ ] **tree.php DB 연동**
- [ ] **board.php 게시판 DB 연동**
- [ ] **notice.php / find_password.php 연동**

### Phase 6 -- 배포 (미착수)
- [ ] 외부 서버 배포 + HTTPS + 도메인 + admin 인증 재활성화

---

## 주요 구현 상세

### 카드 플립 & 씨앗 수집
- CSS scaleX 키프레임 (3D 대신 2D 플립)
- collectCardWithoutClosing(): 카드 뒤집기+씨앗 채우기 (모달 유지)
- unlockKeepButton(): 오디오 종료 후 모달 닫기+승리 체크

### 카드별 오디오 매핑
- 카드 0~6 -> I/You/He/She/Do you/I don't/명령형 [chunk]
- 각 카드 클릭시 해당 문장 Web Speech TTS 7회 반복

### Together 모달
- window.parent.completedDays Set으로 iframe<->부모 통신
- 자료받기 -> book.php?day=N / 음원받기 -> day_mp3_merged.php?day=N

### MP3 합본 다운로드
- 동사3 x 표현7 = 21 MP3 -> PHP 바이너리 concat -> audio/mpeg 스트림

---

## 이미지 자산 현황 (2026-03-05)

| 항목 | 수치 |
|------|------|
| DB 매핑 성공 | 836개 |
| DB NULL (미매핑) | 214개 |
| 이미지 자체 누락 | ~25개 (listen to, speak 등) |
| Day 42~50 신규 | ~189개 (미제작) |

---

## 남은 작업

1. 누락 이미지 ~214개 제작 (ComfyUI)
2. tree.php / board.php / notice.php DB 연동
3. find_password.php 이메일 발송 (Resend API)
4. admin 인증 재활성화 + 외부 서버 배포 + HTTPS

---

## 기술 이슈 해결 이력

| 이슈 | 해결 |
|------|------|
| 이미지 매핑 실패 | Day 마커 파싱 버그 수정 + zero-padding |
| 한글 깨짐 | SET NAMES utf8mb4 |
| 이미지 URL 공백 | rawurlencode() per segment |
| Edit EEXIST (Windows) | Python 스크립트로 우회 |
| 카드 플립 3D 불가 | scaleX 2D 플립 구현 |
| 오디오 강제 종료 | collectCardWithoutClosing 분리 |

---

*Last updated: 2026-03-05*
