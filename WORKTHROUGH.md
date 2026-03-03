# 청킹잉글리시 백엔드 구현 워크쓰루
> 단계별 구현 가이드 - XAMPP (PHP + MySQL) 기반

---

## 전체 로드맵

```
Phase 1  DB 설계 & 기초 세팅        (1~2일)
Phase 2  회원 시스템                 (2~3일)
Phase 3  콘텐츠 DB 연동              (2~3일)
Phase 4  관리자 페이지               (2~3일)
Phase 5  학습 진도 & 게이미피케이션  (2~3일)
Phase 6  배포 & 보안                 (1~2일)
```

---

## Phase 1 - DB 설계 & 기초 세팅

### 1-1. MySQL 테이블 설계

```sql
-- ① 회원 테이블
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(100) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,          -- bcrypt 해시
    nickname    VARCHAR(50),
    role        ENUM('student','parent','admin') DEFAULT 'student',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ② 학습일(Day) 테이블
CREATE TABLE days (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    day_number   INT NOT NULL,                  -- 1, 2, 3...
    title        VARCHAR(100),                  -- "have a dream" 등 주제
    release_date DATE NOT NULL,                 -- 공개 날짜 (매일 업데이트)
    is_active    TINYINT(1) DEFAULT 1,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ③ 청크 카드 테이블 (Page 1 - 9개 이미지 그리드)
CREATE TABLE chunks (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    day_id       INT NOT NULL,
    order_num    INT NOT NULL,                  -- 1~9번 순서
    expression   VARCHAR(100) NOT NULL,         -- "have a dream"
    meaning_kr   VARCHAR(100),                  -- "꿈을 갖다"
    image_path   VARCHAR(255),                  -- "img/have_a_dream.png"
    FOREIGN KEY (day_id) REFERENCES days(id) ON DELETE CASCADE
);

-- ④ 문장 카드 테이블 (Page 2,3 - 10개 문장)
CREATE TABLE sentences (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    day_id       INT NOT NULL,
    order_num    INT NOT NULL,                  -- 1~10번 순서
    eng          TEXT NOT NULL,                 -- 영어 문장
    kor          TEXT NOT NULL,                 -- 한국어 해석
    grammar_type VARCHAR(50),                   -- "to(부정사)", "ing(동명사)", "전치사" 등
    FOREIGN KEY (day_id) REFERENCES days(id) ON DELETE CASCADE
);

-- ⑤ 학습 진도 테이블
CREATE TABLE progress (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    day_id      INT NOT NULL,
    completed   TINYINT(1) DEFAULT 0,
    score       INT DEFAULT 0,
    studied_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (day_id) REFERENCES days(id),
    UNIQUE KEY uq_user_day (user_id, day_id)
);
```

### 1-2. DB 연결 파일 생성

```
03_chunking/
└── config/
    └── db.php        ← DB 연결 설정 (단 하나의 파일에서 관리)
```

```php
// config/db.php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chunking_english');

$pdo = new PDO(
    "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
```

---

## Phase 2 - 회원 시스템

### 2-1. 파일 구조

```
api/
├── auth/
│   ├── register.php     ← 회원가입 처리
│   ├── login.php        ← 로그인 처리
│   ├── logout.php       ← 로그아웃
│   └── find_pw.php      ← 비밀번호 재설정
```

### 2-2. 로그인 처리 핵심 로직

```php
// api/auth/login.php
session_start();
require '../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$email    = $data['email'];
$password = $data['password'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    echo json_encode(['success' => true, 'nickname' => $user['nickname']]);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '이메일 또는 비밀번호가 틀렸습니다.']);
}
```

### 2-3. 프론트와 연동 방법

```javascript
// login.php 의 JS 에서 fetch 로 호출
const res = await fetch('/03_chunking/api/auth/login.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
});
const data = await res.json();
if (data.success) window.location.href = '/03_chunking/index.php';
```

---

## Phase 3 - 콘텐츠 DB 연동

### 3-1. API 구조

```
api/
└── content/
    ├── get_day.php       ← ?day=1  → 해당 날짜 데이터 반환
    ├── get_chunks.php    ← ?day_id=1 → 청크 9개 반환
    └── get_sentences.php ← ?day_id=1 → 문장 10개 반환
```

### 3-2. 콘텐츠 API 예시

```php
// api/content/get_day.php
require '../../config/db.php';

$day_number = intval($_GET['day'] ?? 1);

$stmt = $pdo->prepare("
    SELECT d.*,
           (SELECT COUNT(*) FROM chunks WHERE day_id = d.id) AS chunk_count,
           (SELECT COUNT(*) FROM sentences WHERE day_id = d.id) AS sentence_count
    FROM days d
    WHERE d.day_number = ? AND d.release_date <= CURDATE()
");
$stmt->execute([$day_number]);
$day = $stmt->fetch(PDO::FETCH_ASSOC);

// 청크 목록
$stmt2 = $pdo->prepare("SELECT * FROM chunks WHERE day_id = ? ORDER BY order_num");
$stmt2->execute([$day['id']]);
$day['chunks'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// 문장 목록
$stmt3 = $pdo->prepare("SELECT * FROM sentences WHERE day_id = ? ORDER BY order_num");
$stmt3->execute([$day['id']]);
$day['sentences'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($day);
```

### 3-3. book.php 변환 방향

```javascript
// 기존 (하드코딩)
const p2Data = [
    { n:1, eng: "I have a dream...", kor: "나는...", conn: "to(부정사)" },
    ...
];

// 변경 후 (DB에서 동적 로딩)
const dayParam = new URLSearchParams(location.search).get('day') || 1;
const res  = await fetch(`/03_chunking/api/content/get_day.php?day=${dayParam}`);
const data = await res.json();

// data.chunks    → Page 1 청크 그리드 렌더링
// data.sentences → Page 2, 3 문장 카드 렌더링
renderChunkGrid(data.chunks);
renderSentences(data.sentences);
```

---

## Phase 4 - 관리자 페이지

### 4-1. 파일 구조

```
admin/
├── index.php          ← 관리자 대시보드
├── day_list.php       ← 전체 Day 목록
├── day_edit.php       ← Day 생성 / 수정
├── image_upload.php   ← 청크 이미지 업로드
└── middleware.php     ← 관리자 권한 체크
```

### 4-2. 이미지 업로드 처리

```php
// admin/image_upload.php 핵심 로직
$allowed = ['image/png', 'image/jpeg', 'image/webp'];
$file = $_FILES['chunk_image'];

if (!in_array($file['type'], $allowed)) {
    die(json_encode(['error' => 'PNG/JPG/WEBP만 업로드 가능합니다.']));
}

// 파일명: 표현을 snake_case로 → have_a_dream.png
$expression = strtolower(str_replace(' ', '_', $_POST['expression']));
$filename   = $expression . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
$dest       = '../../img/' . $filename;

move_uploaded_file($file['tmp_name'], $dest);
echo json_encode(['success' => true, 'path' => 'img/' . $filename]);
```

### 4-3. 관리자 화면 구성

```
[대시보드]
├── 오늘의 학습 현황 (접속자 수, 완료율)
├── Day 목록 (테이블: 번호 / 주제 / 공개일 / 상태)
│
[Day 등록/수정]
├── Day 번호, 공개 날짜
├── 청크 9개 입력 (표현 + 한국어 뜻 + 이미지 업로드)
└── 문장 10개 입력 (영어 + 한국어 + 문법유형 선택)
```

---

## Phase 5 - 학습 진도 & 게이미피케이션

### 5-1. 진도 저장

```php
// api/progress/save.php
session_start();
require '../../config/db.php';

$user_id = $_SESSION['user_id'];
$day_id  = intval($_POST['day_id']);
$score   = intval($_POST['score']);   // TTS 완료 횟수 등

$stmt = $pdo->prepare("
    INSERT INTO progress (user_id, day_id, completed, score)
    VALUES (?, ?, 1, ?)
    ON DUPLICATE KEY UPDATE completed=1, score=GREATEST(score, ?)
");
$stmt->execute([$user_id, $day_id, $score, $score]);
echo json_encode(['success' => true]);
```

### 5-2. tree.php 연동 방향

```javascript
// tree.php - 현재 하드코딩된 트리 장식을
// 실제 학습 완료 데이터와 연결
const res = await fetch('/03_chunking/api/progress/my_tree.php');
const { completed_days, total_days } = await res.json();

// 완료한 Day 수만큼 트리에 전구 불 켜기
renderTree(completed_days);
```

---

## Phase 6 - 배포 & 보안

### 체크리스트

```
보안
├── [ ] 모든 SQL → PDO Prepared Statement 사용
├── [ ] 비밀번호 → password_hash(bcrypt) 사용
├── [ ] 세션 고정 공격 방지 → session_regenerate_id()
├── [ ] XSS 방지 → htmlspecialchars() 출력 시 적용
├── [ ] 파일 업로드 → 확장자 + MIME 타입 이중 검증
├── [ ] 관리자 페이지 → IP 제한 또는 별도 인증
└── [ ] config/db.php → 웹에서 직접 접근 차단 (.htaccess)

배포 옵션
├── 옵션A: 카페24 / 가비아 공유 호스팅 (PHP+MySQL, 월 3~5천원)
├── 옵션B: AWS Lightsail (월 $5, 확장 쉬움)
└── 옵션C: Cloudflare Pages + PlanetScale (무료 시작 가능)
```

---

## 최종 폴더 구조 (완성 후)

```
03_chunking/
├── index.php
├── book.php            ← DB에서 동적 렌더링으로 변환
├── login.php           ← API 연동
├── find_password.php   ← API 연동
├── board.php
├── notice.php
├── tree.php            ← 진도 API 연동
├── together.html
│
├── config/
│   └── db.php          ← DB 연결 (단일 관리)
│
├── api/
│   ├── auth/
│   │   ├── register.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── find_pw.php
│   ├── content/
│   │   ├── get_day.php
│   │   ├── get_chunks.php
│   │   └── get_sentences.php
│   └── progress/
│       ├── save.php
│       └── my_tree.php
│
├── admin/
│   ├── index.php
│   ├── day_list.php
│   ├── day_edit.php
│   └── image_upload.php
│
├── css/
├── js/
└── img/
```

---

## 다음 액션 (어디서 시작할지 선택)

| 번호 | 작업 | 예상 시간 |
|------|------|-----------|
| 1 | **DB 생성** - phpMyAdmin에서 테이블 생성 SQL 실행 | 30분 |
| 2 | **config/db.php** 작성 + 연결 테스트 | 10분 |
| 3 | **로그인 API** 구현 + login.php 연동 | 2시간 |
| 4 | **콘텐츠 API** + book.php 동적 변환 | 3시간 |
| 5 | **관리자 페이지** - Day 등록 UI + 이미지 업로드 | 4시간 |
