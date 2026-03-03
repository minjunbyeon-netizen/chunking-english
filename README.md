# 청킹잉글리시 (Chunking English)
> Wizard Chunking Together - 아이와 엄마가 함께하는 청킹 영어학습 플랫폼

---

## 프로젝트 개요

- **목적**: 청킹(Chunking) 방식으로 영어 표현을 매일 학습하는 웹 서비스
- **대상**: 초등학생 및 학부모 (Kids & Mom)
- **스택**: PHP + HTML/CSS/JS (XAMPP 환경)
- **현재 상태**: 프론트엔드 완성, 백엔드 미구현

---

## 현재 파일 구조

```
03_chunking/
├── index.php           ← 메인 학습 페이지 (청킹 시트 뷰어, 3페이지 A4 구성)
├── book.php            ← E-북 뷰어 (A4 출력 + TTS Listen & Repeat 기능)
├── login.php           ← 로그인 페이지 (UI 완성, 백엔드 미연결)
├── find_password.php   ← 비밀번호 찾기 (UI 완성, 백엔드 미연결)
├── board.php           ← 커뮤니티 게시판
├── notice.php          ← 공지사항
├── tree.php            ← My English Tree (학습 게이미피케이션, 밤하늘 트리)
├── together.html       ← 함께하기 페이지
├── css/
│   └── style.css
├── js/
│   ├── script.js
│   ├── fonts.js
│   └── tailwind-config.js
└── img/                ← 청킹 표현 이미지 (PNG) + 상황별 영상 (MP4)
    ├── have_a_dream.png
    ├── change_my_life.png
    ├── start_the_day.png
    ├── ... (표현별 이미지 40여장)
    ├── daily01.mp4
    ├── morning01.mp4
    └── ... (상황별 영상 8개)
```

---

## 현재 콘텐츠 구조 (분석 결과)

### 1일치 학습 구성 (3페이지)
```
Page 1 - 청킹 기본 그리드
  └── 9개 청크 카드 (이미지 + 뜻 적기 노트)

Page 2 - 청킹 변화 (문장 1~5번)
  └── 5개 문장 카드 (영어 문장 + 한국어 해석 + 문법 유형)
  └── TTS Listen & Repeat 기능 (7회 반복)

Page 3 - 청킹 변화 (문장 6~10번)
  └── 5개 문장 카드 (영어 문장 + 한국어 해석 + 문법 유형)
  └── TTS Listen & Repeat 기능
```

### 하드코딩된 데이터 예시 (현재 문제)
```javascript
// book.php 내부 JS 배열 - 매번 파일 직접 수정 필요
const p2Data = [
  { n:1, eng: "I have a dream to change my life.", kor: "나는 내 삶을 바꿀 꿈을 가지고 있어요.", conn: "to(부정사)" },
  { n:2, eng: "Having a dream changes my future.",  kor: "꿈을 갖는 것은 나의 미래를 바꿔요.",    conn: "ing(동명사)" },
  ...
];
```

---

## 사용 기술 스택

| 분류 | 기술 |
|------|------|
| 서버 | XAMPP (Apache + MySQL + PHP) |
| 프론트 | HTML / Tailwind CSS / Vanilla JS |
| 폰트 | Chewy, Quicksand, Jua, Noto Sans KR |
| 아이콘 | Font Awesome 6 |
| TTS | Web Speech API (브라우저 내장) |
| 인쇄 | CSS @media print (A4 최적화) |

---

## 핵심 기능 목록

- [x] A4 인쇄용 학습지 뷰어
- [x] TTS(음성 합성) Listen & Repeat 플레이어
- [x] 로그인 / 비밀번호 찾기 UI
- [x] My English Tree (게이미피케이션 UI)
- [x] 게시판 / 공지사항 UI
- [ ] 회원 DB 연동 (로그인/가입)
- [ ] 콘텐츠 DB 연동 (단어/문장 동적 로딩)
- [ ] 이미지 업로드 관리
- [ ] 관리자 페이지 (일별 콘텐츠 등록)
- [ ] 학습 진도 저장
- [ ] 결제 / 구독 시스템

---

## 백엔드 구현 예정 (워크쓰루 참고)

> 상세 계획은 WORKTHROUGH.md 참고

---

## 이미지 자산 현황 (asset/img/)

### 구조
```
asset/img/
└── day N/
    └── NN. 동사/
        └── 동사_표현.png   ← 파일명 = 엑셀 표현을 snake_case 변환
```

### 수량 현황
| 항목 | 수치 |
|------|------|
| 50일치 필요 이미지 | 1,050개 (50일 × 3동사 × 7표현) |
| 현재 보유 이미지 | 1,027개 |
| 매핑 가능 (정상) | **1,004개** |
| 매핑 불가 (파일명 오류) | 23개 |
| 누락 (이미지 없음) | 23개 |

---

## ⚠️ 체크 필요 항목

### 1. 파일명 숫자 오류 (23개) - 수정 필요
> 파일명 끝에 숫자가 붙어 엑셀 표현과 매핑 불가

| Day | 동사 | 문제 파일 |
|-----|------|-----------|
| Day 5 | plant | plant_the_tree_50.png, plant_the_crop_51.png, plant_the_flower_52.png, plant_the_grass_53.png, plant_the_seed_54.png, plant_the_seedling_55.png, plant_the_sapling_56.png |
| Day 5 | keep | keep_strong_60.png, keep_active_57.png, keep_positive_58.png, keep_confident_59.png, keep_safe_61.png, keep_patient_62.png, keep_steady_63.png |
| Day 4 | think in | think_in_Korean_37.png |
| Day 10 | be | be_proud_57.png, be_ashamed_58.png, be_shameful_59.png, be_active_60.png, be_passive_61.png, be_positive_62.png, be_negative_63.png |
| 미확인 | fix | fix_the_topic_119.png |

- [ ] 숫자 제거 후 올바른 파일명으로 교체 필요

### 2. 이미지 누락 (23개) - 제작 필요
> 폴더는 있으나 이미지 없음

| Day | 동사 폴더 | 누락 수 |
|-----|-----------|---------|
| Day 14 | 42. do | 1개 부족 (6개만 있음) |
| Day 29 | 86. listen to | **7개 전부 없음** |
| Day 31 | 93. read | **7개 전부 없음** |
| Day 34 | 100. speak | **7개 전부 없음** |
| Day 47 | 140. be | 1개 부족 (6개만 있음) |

- [ ] 해당 이미지 제작 후 각 폴더에 추가 필요

### 3. img/ 루트 폴더 정리 필요
- [ ] `change_my_life..png` → 점 두 개 오타 + 엑셀 미존재 표현
- [ ] `have an idea.png` → 공백 포함, `have_an_idea.png` 와 중복
