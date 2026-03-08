<?php
/* ── App Engine Gen2 Front-Controller 라우터 ─────────────────────
   App Engine Standard PHP8.3은 모든 요청을 index.php로 라우팅함.
   요청 URL에 해당하는 PHP 파일이 있으면 해당 파일을 직접 실행함.
──────────────────────────────────────────────────────────────────*/
(function() {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    // 루트 또는 index.php 요청은 그냥 통과
    if ($path === '/' || $path === '/index.php' || $path === '') return;
    // .php 확장자 파일만 라우팅
    if (substr($path, -4) !== '.php') return;
    $file = __DIR__ . $path;
    $real = realpath($file);
    if (!$real) return;
    // 보안: 앱 루트 내에 있고, config/ database/ 폴더 제외
    if (strpos($real, __DIR__ . DIRECTORY_SEPARATOR) !== 0) return;
    if (strpos($real, __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR) === 0) return;
    if (strpos($real, __DIR__ . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR) === 0) return;
    if ($real === __FILE__) return;
    chdir(dirname($real));  // 상대경로(../../config/db.php 등)가 올바르게 해석되도록
    require $real;
    exit;
})();

require_once 'config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$isGuest = empty($_SESSION['user_id']);

// ── 섹션 정의 (9개 테마, Day 1~250) ─────────────────────────────────────────
$sectionDefs = [
    ['num' => 1, 'startDay' => 1,   'endDay' => 10,  'kr' => '희망 & 실천',   'en' => 'Hope & Practice',         'video' => './video/hope01.mp4'],
    ['num' => 2, 'startDay' => 11,  'endDay' => 29,  'kr' => '아침 일상',     'en' => 'Morning Routine',          'video' => './video/morning01.mp4'],
    ['num' => 3, 'startDay' => 30,  'endDay' => 87,  'kr' => '학교 생활',     'en' => 'School Life',              'video' => './video/school01.mp4'],
    ['num' => 4, 'startDay' => 88,  'endDay' => 99,  'kr' => '운동 & 스포츠', 'en' => 'Exercise & Sports',        'video' => './video/sport01.mp4'],
    ['num' => 5, 'startDay' => 100, 'endDay' => 138, 'kr' => '음식 & 요리',   'en' => 'Food & Cooking',           'video' => './video/cooking01.mp4'],
    ['num' => 6, 'startDay' => 139, 'endDay' => 193, 'kr' => '일상 생활',     'en' => 'Daily Life',               'video' => './video/daily01.mp4'],
    ['num' => 7, 'startDay' => 194, 'endDay' => 224, 'kr' => '교통 & 여행',   'en' => 'Transportation & Travel',  'video' => './video/travel01.mp4'],
    ['num' => 8, 'startDay' => 225, 'endDay' => 234, 'kr' => '건강 & 의료',   'en' => 'Health & Medicine',        'video' => './video/health01.mp4'],
    ['num' => 9, 'startDay' => 235, 'endDay' => 250, 'kr' => '저녁 일상',     'en' => 'Evening Routine',          'video' => './video/evening01.mp4'],
];

// ── DB에서 Day 1~250 전체 데이터 로드 ──────────────────────────────────────────
$stmt = $pdo->query("
    SELECT d.day_number,
           v.id AS verb_id, v.global_num, v.verb_en, v.verb_kr,
           v.sentence_en, v.sentence_kr, v.order_num AS verb_order,
           e.order_num AS expr_order, e.expression_en, e.expression_kr,
           e.image_path, e.audio_path
    FROM days d
    JOIN verbs v ON v.day_id = d.id
    JOIN expressions e ON e.verb_id = v.id
    WHERE d.day_number BETWEEN 1 AND 250
      AND d.is_active = 1
      AND v.verb_en REGEXP '^[a-zA-Z]'
    ORDER BY d.day_number, v.order_num, e.order_num
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 이미지/오디오 URL 헬퍼 ──────────────────────────────────────────────────────
function chunk_url(?string $path): ?string {
    if (!$path) return null;
    $clean = str_replace('\\', '/', $path);
    if (!file_exists(__DIR__ . '/' . $clean)) return null;
    $parts = explode('/', $clean);
    return './' . implode('/', array_map('rawurlencode', $parts));
}

// ── levelData / masterChunkData 빌드 ─────────────────────────────────────────
$levelData      = [];
$masterChunkData = [];

foreach ($rows as $row) {
    $day     = (int)$row['day_number'];
    $verbKey = strtolower($row['verb_en']) . $row['global_num']; // 고유 키 (have1, change2…)

    // levelData 구성
    if (!isset($levelData[$day])) {
        $levelData[$day] = [
            'ride'  => "Day {$day}",
            'title' => "Day {$day}",
            'verbs' => []
        ];
    }
    if (!in_array($verbKey, $levelData[$day]['verbs'])) {
        $levelData[$day]['verbs'][] = $verbKey;
    }

    // masterChunkData 구성
    if (!isset($masterChunkData[$verbKey])) {
        $masterChunkData[$verbKey] = [];
    }
    $masterChunkData[$verbKey][] = [
        'eng'   => $row['expression_en'],
        'kor'   => $row['expression_kr'] ?? '',
        'image' => chunk_url($row['image_path']) ?? './img/exc_n1.png',
        'audio' => chunk_url($row['audio_path']),
    ];
}

// ── 완료 Day 로드 (로그인 시 DB, 비로그인 시 빈 배열) ───────────────────────
$completedDays = [];
if (!empty($_SESSION['user_id'])) {
    $ps = $pdo->prepare("
        SELECT d.day_number FROM progress p
        JOIN days d ON p.day_id = d.id
        WHERE p.user_id = ? AND p.completed = 1
    ");
    $ps->execute([$_SESSION['user_id']]);
    $completedDays = array_column($ps->fetchAll(PDO::FETCH_ASSOC), 'day_number');
}

// 완료한 Day 최대값 계산
$maxCompletedDay = empty($completedDays) ? 0 : max(array_map('intval', $completedDays));

// 다음에 공부할 Day = 완료한 최대 Day + 1, 없으면 1
$currentDay = $maxCompletedDay + 1;

// 잠금 해제 범위: 완료한 Day까지 + 바로 다음 Day 1개만 열어줌
// (Day 1이 아직 미완료면 Day 1만, Day N 완료면 Day N+1까지 열림)
$unlockedDays = $maxCompletedDay + 1;

$serverData = [
    'appBase'         => APP_BASE,
    'sectionDefs'     => $sectionDefs,
    'levelData'       => $levelData,
    'masterChunkData' => $masterChunkData,
    'iconMap'         => (object)[],
    'progress'        => [
        'unlockedDays'    => $unlockedDays,
        'completedVerbs'  => [],
        'completedDays'   => $completedDays,
        'currentDay'      => $currentDay,
        'stationProgress' => [1=>1,2=>1,3=>1,4=>1,5=>1,6=>1,7=>1,8=>1,9=>1],
    ],
];
$serverDataJson = json_encode($serverData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>청킹잉글리시 - 엄마랑 아이랑 영어 여행</title>
    <meta name="description" content="하루 3동사, 21표현으로 완성하는 엄마표 영어. 청킹잉글리시와 함께 영어 여행을 떠나요!">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%23C75A6F'/><text x='50' y='62' font-family='Arial Black,sans-serif' font-size='34' font-weight='900' fill='white' text-anchor='middle' letter-spacing='-1'>CKE</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Quicksand:wght@400;600;700&family=Jua&family=Gowun+Dodum&family=Press+Start+2P&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__.'/css/style.css') ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Noto+Sans+KR:wght@100..900&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Jua&family=Noto+Sans+KR:wght@100..900&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Jua&family=Noto+Sans+KR:wght@100..900&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">

    <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>
    <script src="./js/fonts.js?v=<?= filemtime(__DIR__.'/js/fonts.js') ?>"></script>

    <script src="./js/tailwind-config.js?v=<?= filemtime(__DIR__.'/js/tailwind-config.js') ?>"></script>
    <script src="js/script.js?v=<?= filemtime(__DIR__.'/js/script.js') ?>" defer></script>


    <script>
        window.SERVER_DATA = <?= $serverDataJson ?>;
    </script>

</head>
<body class="font-body text-brand-text antialiased bg-brand-cream">

<div id="main-menu-container" class="fixed top-4 left-4 md:top-6 md:left-6 z-[200]">
    <div class="relative">

        <button id="main-menu-btn" class="main-menu-btn" type="button">
            <i class="fa-solid fa-bars-staggered main-menu-btn__icon"></i>
        </button>

        <div id="main-menu-dropdown" class="main-menu-dropdown">

            <div class="main-menu-dropdown__inner">

                <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="./api/auth/logout.php" class="main-menu-item">
                    <div class="main-menu-item__icon main-menu-item__icon--pink">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </div>
                    <span class="main-menu-text"><?= htmlspecialchars($_SESSION['nickname'] ?? '로그아웃') ?></span>
                </a>
                <?php else: ?>
                <a href="./login.php" class="main-menu-item">
                    <div class="main-menu-item__icon main-menu-item__icon--pink">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    </div>
                    <span class="main-menu-text">로그인</span>
                </a>
                <?php endif; ?>

                <div class="main-menu-divider"></div>

                <a href="./book.php" class="main-menu-item">
                    <div class="main-menu-item__icon main-menu-item__icon--pink">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                    <span class="main-menu-text">교재 PDF</span>
                </a>

                <a href="./tree.php" class="main-menu-item">
                    <div class="main-menu-item__icon main-menu-item__icon--yellow">
                        <i class="fa-solid fa-tree"></i>
                    </div>
                    <span class="main-menu-text">나의 나무</span>
                </a>

                <a href="./notice.php" class="main-menu-item">
                    <div class="main-menu-item__icon main-menu-item__icon--yellow">
                        <i class="fa-solid fa-table-list"></i>
                    </div>
                    <span class="main-menu-text">게시판</span>
                </a>

            </div>

        </div>
    </div>
</div>

<div class="together-fixed">
    <button class="together-btn" onclick="openTogetherModal()">

        <div class="together-icon">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
        </div>
<?php if (!$isGuest): ?><div class="logout-fixed">    <button class="logout-btn" onclick="doLogout()" title="로그아웃">        <div class="logout-icon">            <i class="fa-solid fa-right-from-bracket"></i>        </div>        <span class="logout-text">Logout</span>    </button></div><?php endif; ?>

        <div class="together-text">
            <span class="together-title">Together</span>
            <span class="together-sub">
                마법사 <span class="highlight">청킹</span>
            </span>
        </div>

    </button>
</div>

<div class="app-shell">

    <div id="logo-container" class="logo-layer">

        <h1 id="main-logo" class="main-logo">

            <div class="logo-stage">

                <div id="logo-original" class="logo-original">
                    <div class="logo-line logo-line--primary">
            <span class="logo-accent logo-accent--mr">
                청킹
                <span class="logo-accent-shadow">청킹</span>
            </span>
                        <span class="logo-brand-text">으로 쉽게 영어말하기</span>
                    </div>
                    <div class="logo-line logo-line--secondary">
            <span class="logo-accent">
                Chunking
                <span class="logo-accent-shadow">Chunking</span>
            </span>
                        <span class="logo-brand-text">-Based Easy Speaking</span>
                    </div>
                </div>

                <div id="logo-scrolled" class="logo-scrolled">
                    <div class="logo-line logo-line--scrolled">
            <span class="logo-accent logo-accent--mr">
                Chunking English
                <span class="logo-accent-shadow">Chunking English</span>
            </span>
                        <span class="logo-kids-mom">
                Kids & Mom
            </span>
                    </div>
                </div>

            </div>

            <div class="hero-stack">

                <div class="hero-center">

                    <div id="main-exc-img" class="hero-exc">
                        <img src="./img/exc_n1.png" alt="Seed" class="hero-exc-img">
                    </div>

                    <div id="wizard-badge"
                         onclick="openTogetherModal()"
                         class="wizard-badge animate-button-feedback">

                <span class="wizard-badge__text">
                    Wizard Chunking <span class="wizard-badge__sub">마법사 청킹</span>
                </span>

                        <div class="wizard-badge__cursor animate-fake-cursor">
                            <i class="fa-solid fa-arrow-pointer wizard-badge__cursor-icon"></i>
                        </div>
                    </div>

                    <div id="guide-bubble" class="guide-bubble">

                        <div class="guide-bubble__caret"></div>

                        <div class="guide-bubble__inner">

                            <div class="guide-bubble__glow"></div>

                            <div class="guide-icon-wrap">
                                <div class="guide-icon-wrap__inner">
                                    <div id="guide-icon-bg" class="guide-icon-bg">
                                        <i id="guide-icon" class="fa-solid fa-gamepad guide-icon"></i>
                                    </div>
                                    <div class="guide-step-badge">
                                        STEP <span id="guide-step-num">1</span>
                                    </div>
                                </div>
                            </div>

                            <div class="guide-content">

                                <div id="guide-text-area" class="guide-text">
                                    <h3 id="guide-title" class="guide-title">
                                        Let's Play!
                                    </h3>
                                    <p id="guide-desc" class="guide-desc">
                                        주사위 게임으로<br>영어 여행을 시작해요.
                                    </p>
                                </div>

                                <div class="guide-footer">
                                    <div class="guide-dots" id="guide-dots">
                                    </div>

                                    <div class="guide-controls">
                                        <button onclick="prevGuideStep()" class="guide-btn guide-btn--prev">
                                            <i class="fa-solid fa-chevron-left guide-btn__icon"></i>
                                        </button>
                                        <button onclick="nextGuideStep()" class="guide-btn guide-btn--next">
                                            <i class="fa-solid fa-chevron-right guide-btn__icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </h1>
    </div>

    <div class="hero-bg-stage hero-bg">
        <div class="hero-blob hero-blob--1"></div>
       <!-- <div class="hero-blob hero-blob--2 blob-2"></div>
        <div class="hero-blob hero-blob--3"></div>-->
    </div>

    <main class="main-col">
        <section id="hero" class="hero-section">

            <div class="scroll-hint">
                SCROLL TO EXPLORE
                <br>
                <i class="fa-solid fa-chevron-down scroll-hint__icon"></i>
            </div>
        </section>

        <div id="marquee-bar" class="marquee-bar">
            <div class="marquee-container">
                <div class="marquee-content marquee-text">
                    <span>HAVE A DREAM</span> <span class="marquee-sep">✦</span>
                    <span>GO TO SCHOOL</span> <span class="marquee-sep">✦</span>
                    <span>BRUSH MY TEETH</span> <span class="marquee-sep">✦</span>
                    <span>WASH MY FACE</span> <span class="marquee-sep">✦</span>
                    <span>EAT BREAKFAST</span> <span class="marquee-sep">✦</span>
                    <span>READ A BOOK</span> <span class="marquee-sep">✦</span>
                    <span>RIDE A BIKE</span> <span class="marquee-sep">✦</span>
                    <span>PLAY SOCCER</span> <span class="marquee-sep">✦</span>
                    <span>DRAW A PICTURE</span> <span class="marquee-sep">✦</span>
                    <span>SING A SONG</span> <span class="marquee-sep">✦</span>
                    <span>LISTEN TO MUSIC</span> <span class="marquee-sep">✦</span>
                    <span>WRITE A LETTER</span> <span class="marquee-sep">✦</span>
                    <span>MEET MY FRIENDS</span> <span class="marquee-sep">✦</span>
                    <span>TAKE A BUS</span> <span class="marquee-sep">✦</span>
                    <span>CLEAN MY ROOM</span> <span class="marquee-sep">✦</span>
                    <span>HELP MY MOM</span> <span class="marquee-sep">✦</span>
                    <span>WASH THE DISHES</span> <span class="marquee-sep">✦</span>
                    <span>WATCH TV</span> <span class="marquee-sep">✦</span>
                    <span>TAKE A SHOWER</span> <span class="marquee-sep">✦</span>
                    <span>GO TO BED</span> <span class="marquee-sep">✦</span>
                    <span>SAY HELLO</span> <span class="marquee-sep">✦</span>
                    <span>OPEN THE DOOR</span> <span class="marquee-sep">✦</span>
                    <span>CLOSE THE WINDOW</span> <span class="marquee-sep">✦</span>
                    <span>TURN ON THE LIGHT</span> <span class="marquee-sep">✦</span>
                    <span>TURN OFF THE LIGHT</span> <span class="marquee-sep">✦</span>
                    <span>DRINK MILK</span> <span class="marquee-sep">✦</span>
                    <span>EAT AN APPLE</span> <span class="marquee-sep">✦</span>
                    <span>MAKE A ROBOT</span> <span class="marquee-sep">✦</span>
                    <span>FLY A KITE</span> <span class="marquee-sep">✦</span>
                    <span>SWIM IN THE POOL</span> <span class="marquee-sep">✦</span>
                    <span>BUILD A SANDCASTLE</span> <span class="marquee-sep">✦</span>
                    <span>CATCH A BALL</span> <span class="marquee-sep">✦</span>
                    <span>THROW A BALL</span> <span class="marquee-sep">✦</span>
                    <span>KICK A BALL</span> <span class="marquee-sep">✦</span>
                    <span>RUN FAST</span> <span class="marquee-sep">✦</span>
                    <span>WALK SLOWLY</span> <span class="marquee-sep">✦</span>
                    <span>DANCE TOGETHER</span> <span class="marquee-sep">✦</span>
                    <span>CLAP MY HANDS</span> <span class="marquee-sep">✦</span>
                    <span>STAMP MY FEET</span> <span class="marquee-sep">✦</span>
                    <span>LOOK AT THE SKY</span> <span class="marquee-sep">✦</span>
                    <span>SMELL A FLOWER</span> <span class="marquee-sep">✦</span>
                    <span>TOUCH A DOG</span> <span class="marquee-sep">✦</span>
                    <span>HEAR A BIRD</span> <span class="marquee-sep">✦</span>
                    <span>TASTE A CANDY</span> <span class="marquee-sep">✦</span>
                    <span>FEEL HAPPY</span> <span class="marquee-sep">✦</span>
                    <span>FEEL SAD</span> <span class="marquee-sep">✦</span>
                    <span>GET ANGRY</span> <span class="marquee-sep">✦</span>
                    <span>BE CAREFUL</span> <span class="marquee-sep">✦</span>
                    <span>HURRY UP</span> <span class="marquee-sep">✦</span>
                    <span>SLOW DOWN</span> <span class="marquee-sep">✦</span>
                    <span>COME HERE</span> <span class="marquee-sep">✦</span>
                    <span>GO THERE</span> <span class="marquee-sep">✦</span>
                    <span>STAND UP</span> <span class="marquee-sep">✦</span>
                    <span>SIT DOWN</span> <span class="marquee-sep">✦</span>
                    <span>RAISE YOUR HAND</span> <span class="marquee-sep">✦</span>
                    <span>DO HOMEWORK</span> <span class="marquee-sep">✦</span>
                    <span>STUDY ENGLISH</span> <span class="marquee-sep">✦</span>
                    <span>PLAY A GAME</span> <span class="marquee-sep">✦</span>
                    <span>USE A COMPUTER</span> <span class="marquee-sep">✦</span>
                    <span>WEAR A COAT</span>
                </div>
            </div>
        </div>

        <section id="map-section" class="map-section">

            <div class="map-top-banner">

                <div class="map-swing">

                    <div class="map-hangers">
                        <div class="map-hanger"></div>
                        <div class="map-hanger"></div>
                    </div>

                    <div class="map-sign">

                        <div class="map-sign__dot map-sign__dot--tl"></div>
                        <div class="map-sign__dot map-sign__dot--tr"></div>
                        <div class="map-sign__dot map-sign__dot--bl"></div>
                        <div class="map-sign__dot map-sign__dot--br"></div>

                        <div class="map-sign__title-row">
                    <span class="map-sign__title-text">
                        청킹으로
                    </span>

                            <div class="map-sign__highlight">
    <span class="map-sign__highlight-text">
        쉽게
    </span>
                                <div class="map-sign__underline"></div>
                                <i class="fa-solid fa-star map-sign__star"></i>
                            </div>

                            <span class="map-sign__title-text">
                        영어말하기
                    </span>
                        </div>

                        <div class="map-sign__subtitle-row">
                            <div class="map-sign__line"></div>
                            <p class="map-sign__subtitle">
                                Chunking-Based Easy Speaking
                            </p>
                            <div class="map-sign__line"></div>
                        </div>

                    </div>
                </div>
            </div>

            <div id="bg-layer-day" class="bg-layer"></div>
            <div id="bg-layer-sunset" class="bg-layer"></div>
            <div id="bg-layer-night" class="bg-layer"></div>

            <div class="sky-effects">
                <div id="celestial-body" class="celestial-body"></div>
                <div class="sky-glow-corner"></div>
                <div id="stars-container" class="stars-container"></div>
            </div>

            <div id="alphabet-container" class="alphabet-container"></div>

            <div id="drag-hint" class="drag-hint">
                <div class="drag-hint__pill">
                    <i class="fa-solid fa-hand-pointer drag-hint__hand"></i>
                    <span class="drag-hint__text">Drag to Explore!</span>
                </div>
            </div>

            <div class="ground-layer" id="ground-layer">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="ground-svg ground-svg--base">
                    <path fill="#8AC9A6" id="ground-path-1" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,165.3C1248,149,1344,171,1392,181.3L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="ground-svg ground-svg--overlay">
                    <path fill="#A5D6A7" id="ground-path-2" fill-opacity="1" d="M0,288L48,272C96,256,192,224,288,213.3C384,203,480,213,576,229.3C672,245,768,267,864,261.3C960,256,1056,224,1152,213.3C1248,203,1344,213,1392,218.7L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                </svg>
            </div>

            <div id="game-container" class="map-game-container">
                <div id="map-view" class="map-view">
                    <div id="map-scroll-container" class="map-scroll-container hide-scrollbar">
                        <div id="map-nodes-container" class="map-nodes-container">
                            <svg id="track-svg" class="track-svg" style="filter: drop-shadow(0px 8px 12 rgba(141, 110, 99, 0.15));">
                                <path id="path-ties-shadow" d="" fill="none" stroke="rgba(0,0,0,0.1)" stroke-width="140" stroke-dasharray="16, 30" stroke-linecap="butt" />
                                <path id="path-ties" d="" fill="none" stroke="#E0C097" stroke-width="130" stroke-dasharray="16, 30" stroke-linecap="butt" />
                                <path id="path-rail-base" d="" fill="none" stroke="#BCAAA4" stroke-width="60" stroke-linecap="round" />
                                <path id="path-rail-inner" d="" fill="none" stroke="#ECEFF1" stroke-width="45" stroke-linecap="round" />
                                <path id="path-rail-center" d="" fill="none" stroke="#B0BEC5" stroke-width="6" stroke-linecap="round" stroke-dasharray="1, 15" />
                            </svg>
                            <div id="park-train" class="park-train" style="top: 0; left: 0;">

                                <div id="train-body" class="train-body">

                                    <img src="./img/ck_train.png"
                                         onerror="this.src='https://cdn-icons-png.flaticon.com/512/3063/3063823.png'"
                                         alt="Train"
                                         class="train-img"
                                         style="object-fit: contain;">

                                    <div class="steam-puff steam-puff--sm -top-2 right-6" style="animation-delay: 0s;"></div>
                                    <div class="steam-puff steam-puff--md -top-3 right-5" style="animation-delay: 0.5s;"></div>
                                    <div class="steam-puff steam-puff--sm -top-2 right-7" style="animation-delay: 1.0s;"></div>
                                </div>
                            </div>



                            <div  class="map-nodes--grid">

                                <!-- 카드 오픈 -->
                                <div class="station-wrap node-item" id="station-wrap-1">
                                    <div class="map-station-point" id="track-anchor-1"></div>
                                    <button id="node-day-1" class="station-btn" data-day="1">
                                        <div class="station-box-frame">
                                            <div class="station-awning"></div>

                                            <div class="station-video">
                                                <video
                                                        src="./img/hope01.mp4"
                                                        poster="./img/hope01.png"
                                                        class="lazy-video"
                                                        loop
                                                        muted
                                                        playsinline
                                                        preload="none"></video>

                                                <div class="station-video-glass"></div>

                                                <div class="station-play-layer">
                                                    <i class="fa-solid fa-play"></i>
                                                </div>
                                            </div>

                                            <div class="station-body">
                                                <div class="station-text-wrap">
                                                    <div class="station-ride">Hope &amp; Practice</div>
                                                    <div class="station-title">희망 &amp; 실천</div>
                                                </div>

                                                <div class="station-stars">
                                                    <i class="fa-solid fa-star"></i>
                                                    <i class="fa-solid fa-star"></i>
                                                    <i class="fa-solid fa-star"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="station-leg station-leg-left"></div>
                                        <div class="station-leg station-leg-right"></div>

                                        <div class="station-label">Station 1</div>

                                        <div class="station-badge">
                                            <i class="fa-solid fa-ticket"></i>
                                        </div>
                                    </button>
                                </div>

                                <!-- 카드 잠김 -->
                                <div class="station-wrap node-item station--locked"
                                     id="station-wrap-2">
                                    <div class="map-station-point" id="track-anchor-2"></div>
                                    <button id="node-day-2"
                                            class="station-btn"
                                            style="cursor:not-allowed;"
                                            disabled
                                            aria-disabled="true">
                                        <div class="station-box-frame">
                                            <div class="station-awning"></div>

                                            <div class="station-video">
                                                <video
                                                        src="./img/morning01.mp4"
                                                        poster="./img/morning01.png"
                                                        class="lazy-video"
                                                        loop
                                                        muted
                                                        playsinline
                                                        preload="none"></video>

                                                <div class="station-video-glass"></div>
                                                <!-- 잠김 상태는 재생 오버레이 없음 -->
                                            </div>

                                            <div class="station-body">
                                                <div class="station-text-wrap">
                                                    <div class="station-ride">Morning Routine</div>
                                                    <div class="station-title">아침 일상</div>
                                                </div>

                                                <div class="station-stars">
                                                    <i class="fa-solid fa-star"></i>
                                                    <i class="fa-solid fa-star"></i>
                                                    <i class="fa-solid fa-star"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="station-leg station-leg-left"></div>
                                        <div class="station-leg station-leg-right"></div>

                                        <div class="station-label">Station 2</div>

                                        <!-- 잠김 배지 -->
                                        <div class="station-badge station-badge--locked" aria-hidden="true">
                                            <i class="fa-solid fa-lock"></i>
                                        </div>
                                    </button>

                                    <!-- 백엔드 주석
                                    - 잠김 상태: station--locked 클래스 추가 + button disabled
                                    - id 규칙: station-wrap-2 / node-day-2 유지
                                    -->
                                </div>

                                <div class="station-wrap node-item station--locked"
                                     id="station-wrap-3">
                                    <div class="map-station-point" id="track-anchor-3"></div>
                                    <button id="node-day-3"
                                            class="station-btn"
                                            style="cursor:not-allowed;"
                                            disabled
                                            aria-disabled="true">
                                        <div class="station-box-frame">
                                            <div class="station-awning"></div>

                                            <div class="station-video">
                                                <video
                                                        src="./img/morning01.mp4"
                                                        poster="./img/morning01.png"
                                                        class="lazy-video"
                                                        loop
                                                        muted
                                                        playsinline
                                                        preload="none"></video>

                                                <div class="station-video-glass"></div>
                                                <!-- 잠김 상태는 재생 오버레이 없음 -->
                                            </div>

                                            <div class="station-body">
                                                <div class="station-text-wrap">
                                                    <div class="station-ride">Morning Routine</div>
                                                    <div class="station-title">아침 일상</div>
                                                </div>

                                                <div class="station-stars">
                                                    <i class="fa-solid fa-star"></i>
                                                    <i class="fa-solid fa-star"></i>
                                                    <i class="fa-solid fa-star"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="station-leg station-leg-left"></div>
                                        <div class="station-leg station-leg-right"></div>

                                        <div class="station-label">Station 2</div>

                                        <!-- 잠김 배지 -->
                                        <div class="station-badge station-badge--locked" aria-hidden="true">
                                            <i class="fa-solid fa-lock"></i>
                                        </div>
                                    </button>
                                </div>

                                <div class="station-wrap node-item station--locked"
                                     id="station-wrap-4">
                                    <div class="map-station-point" id="track-anchor-4"></div>
                                    <button id="node-day-4"
                                            class="station-btn"
                                            style="cursor:not-allowed;"
                                            disabled
                                            aria-disabled="true">
                                        <div class="station-box-frame">
                                            <div class="station-awning"></div>

                                            <div class="station-video">
                                                <video
                                                        src="./img/morning01.mp4"
                                                        poster="./img/morning01.png"
                                                        class="lazy-video"
                                                        loop
                                                        muted
                                                        playsinline
                                                        preload="none"></video>

                                                <div class="station-video-glass"></div>
                                                <!-- 잠김 상태는 재생 오버레이 없음 -->
                                            </div>

                                            <div class="station-body">
                                                <div class="station-text-wrap">
                                                    <div class="station-ride">Morning Routine</div>
                                                    <div class="station-title">아침 일상</div>
                                                </div>

                                                <div class="station-stars">
                                                    <i class="fa-solid fa-star"></i>
                                                    <i class="fa-solid fa-star"></i>
                                                    <i class="fa-solid fa-star"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="station-leg station-leg-left"></div>
                                        <div class="station-leg station-leg-right"></div>

                                        <div class="station-label">Station 2</div>

                                        <!-- 잠김 배지 -->
                                        <div class="station-badge station-badge--locked" aria-hidden="true">
                                            <i class="fa-solid fa-lock"></i>
                                        </div>
                                    </button>

                                    <!-- 백엔드 주석
                                    - 잠김 상태: station--locked 클래스 추가 + button disabled
                                    - id 규칙: station-wrap-2 / node-day-2 유지
                                    -->
                                </div>



                            </div>





                        </div>
                    </div>
                </div>
            </div>

            <div id="day-intro-view" class="hidden ui-day-intro-view">
                <div class="ui-day-intro-inner">
                    <button onclick="backToMap()" class="ui-back-btn">
                        <i class="fa-solid fa-arrow-left ui-back-btn__icon"></i> 뒤로가기
                    </button>

                    <div class="ui-intro-head">
                        <div class="ui-intro-subtitle-wrap animate-pop-in">
                            <div id="intro-subtitle" class="ui-intro-subtitle">음식 & 요리</div>
                            <div class="ui-intro-subtitle-underline animate-draw-line"></div>
                        </div>

                        <div class="ui-intro-title-wrap">
                            <h2 class="ui-intro-title" id="intro-title">Food & Cooking</h2>
                            <div class="ui-intro-title-underline animate-draw-line"></div>
                        </div>

                        <div class="ui-intro-day group">
                            <div class="ui-intro-day-text">
                                <span class="ui-intro-day-label">Day</span>
                                <span id="intro-day-number">1</span>
                            </div>
                        </div>
                    </div>

                    <div class="ui-mission-grid" id="mission-grid"></div>
                </div>
            </div>

            <div id="summary-view" class="hidden ui-summary-view animate-fade">
                <div class="ui-summary-inner">
                    <div class="ui-summary-head">
                        <div class="ui-summary-badge animate-pulse-slow">Mission Complete!</div>
                        <h2 id="summary-title" class="ui-summary-title animate-soft-bounce">Day 1 Clear!</h2>
                        <p class="ui-summary-subtitle">오늘 배운 핵심 표현 3가지</p>
                    </div>

                    <div id="summary-grid" class="ui-summary-grid"></div>

                    <button onclick="completeDayAndReturnToMap()" class="ui-summary-cta group">
                        <span class="ui-summary-cta__content">Grow My Tree! <i class="fa-solid fa-tree animate-wiggle"></i></span>
                        <div class="ui-summary-cta__shine"></div>
                    </button>
                </div>
            </div>

            <div id="drill-view" class="hidden ui-drill-view animate-fade">
                <div class="ui-drill-inner">
                    <div class="ui-drill-topbar">
                        <button onclick="exitDrillConfirmation()" class="ui-drill-close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <div class="ui-drill-pill">
                            <span id="current-verb-title" class="ui-drill-verb">have</span>
                            <span class="ui-drill-found">Found: <span id="found-count">0</span>/7</span>
                        </div>

                        <div class="ui-drill-spacer"></div>
                    </div>

                    <div class="ui-drill-body">
                        <h3 class="ui-drill-title">
                            Pick a card! <i class="fa-solid fa-wand-magic-sparkles ui-drill-title__icon"></i>
                        </h3>

                        <div id="card-grid" class="ui-card-grid">
                            <!--
                              [더미 카드 영역]
                              - 백엔드 개발자는 이 영역을 보고 카드 구조를 바로 파악하고
                                PHP 루프/조건문으로 카드 수/내용을 채우면 됩니다.
                              - JS는 여기 DOM을 새로 만들지 않고, data-index 기준으로
                                수집상태/이미지 src만 업데이트합니다.
                            -->
                            <div>
                                <div class="ui-mini-card mini-card card-item" data-index="0">
                                    <div class="mini-card-inner relative w-full h-full">
                                        <div class="mini-card-back absolute inset-0 card-back">
                                            <i class="fa-solid fa-star text-white opacity-60 text-3xl"></i>
                                        </div>
                                        <div class="mini-card-front absolute inset-0 hidden">
                                            <div class="bg-seed-green/10 absolute inset-0"></div>
                                            <img class="mini-card-img object-cover w-full h-full z-10 p-1 rounded-2xl" src="./img/sample.png" alt="SAMPLE" />
                                        </div>
                                    </div>
                                </div>

                                <div class="ui-mini-card mini-card card-item" data-index="1">
                                    <div class="mini-card-inner relative w-full h-full">
                                        <div class="mini-card-back absolute inset-0 card-back">
                                            <i class="fa-solid fa-star text-white opacity-60 text-3xl"></i>
                                        </div>
                                        <div class="mini-card-front absolute inset-0 hidden">
                                            <div class="bg-seed-green/10 absolute inset-0"></div>
                                            <img class="mini-card-img object-cover w-full h-full z-10 p-1 rounded-2xl" src="./img/sample.png" alt="SAMPLE" />
                                        </div>
                                    </div>
                                </div>

                                <div class="ui-mini-card mini-card card-item" data-index="2">
                                    <div class="mini-card-inner relative w-full h-full">
                                        <div class="mini-card-back absolute inset-0 card-back">
                                            <i class="fa-solid fa-star text-white opacity-60 text-3xl"></i>
                                        </div>
                                        <div class="mini-card-front absolute inset-0 hidden">
                                            <div class="bg-seed-green/10 absolute inset-0"></div>
                                            <img class="mini-card-img object-cover w-full h-full z-10 p-1 rounded-2xl" src="./img/sample.png" alt="SAMPLE" />
                                        </div>
                                    </div>
                                </div>

                                <div class="ui-mini-card mini-card card-item" data-index="3">
                                    <div class="mini-card-inner relative w-full h-full">
                                        <div class="mini-card-back absolute inset-0 card-back">
                                            <i class="fa-solid fa-star text-white opacity-60 text-3xl"></i>
                                        </div>
                                        <div class="mini-card-front absolute inset-0 hidden">
                                            <div class="bg-seed-green/10 absolute inset-0"></div>
                                            <img class="mini-card-img object-cover w-full h-full z-10 p-1 rounded-2xl" src="./img/sample.png" alt="SAMPLE" />
                                        </div>
                                    </div>
                                </div>



                            </div>

                            <div>
                                <div class="ui-mini-card mini-card card-item" data-index="4">
                                    <div class="mini-card-inner relative w-full h-full">
                                        <div class="mini-card-back absolute inset-0 card-back">
                                            <i class="fa-solid fa-star text-white opacity-60 text-3xl"></i>
                                        </div>
                                        <div class="mini-card-front absolute inset-0 hidden">
                                            <div class="bg-seed-green/10 absolute inset-0"></div>
                                            <img class="mini-card-img object-cover w-full h-full z-10 p-1 rounded-2xl" src="./img/sample.png" alt="SAMPLE" />
                                        </div>
                                    </div>
                                </div>
                                <div class="ui-mini-card mini-card card-item" data-index="5">
                                    <div class="mini-card-inner relative w-full h-full">
                                        <div class="mini-card-back absolute inset-0 card-back">
                                            <i class="fa-solid fa-star text-white opacity-60 text-3xl"></i>
                                        </div>
                                        <div class="mini-card-front absolute inset-0 hidden">
                                            <div class="bg-seed-green/10 absolute inset-0"></div>
                                            <img class="mini-card-img object-cover w-full h-full z-10 p-1 rounded-2xl" src="./img/sample.png" alt="SAMPLE" />
                                        </div>
                                    </div>
                                </div>

                                <div class="ui-mini-card mini-card card-item" data-index="6">
                                    <div class="mini-card-inner relative w-full h-full">
                                        <div class="mini-card-back absolute inset-0 card-back">
                                            <i class="fa-solid fa-star text-white opacity-60 text-3xl"></i>
                                        </div>
                                        <div class="mini-card-front absolute inset-0 hidden">
                                            <div class="bg-seed-green/10 absolute inset-0"></div>
                                            <img class="mini-card-img object-cover w-full h-full z-10 p-1 rounded-2xl" src="./img/sample.png" alt="SAMPLE" />
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>

                        <div class="ui-seed-pocket-wrap">
                            <div class="ui-seed-pocket-head">
                                <span class="ui-seed-pocket-title">My Seed Pocket <i class="fa-solid fa-seedling"></i></span>
                                <span class="ui-seed-pocket-hint">Collect 7 seeds!</span>
                            </div>
                            <div id="seed-pocket" class="ui-seed-pocket"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="focus-overlay" class="hidden ui-focus-overlay">
                <div id="focus-card" class="ui-focus-card">
                    <button onclick="closeFocusOverlay()" class="ui-focus-close">
                        <i class="fa-solid fa-xmark ui-focus-close__icon"></i>
                    </button>

                    <div class="ui-focus-left">
                        <div id="main-focus-display" class="ui-focus-display">
                            <div id="focus-img-container" class="cloud-blob-container ui-focus-img-container">
                                <img id="focus-img" src="" alt="Word" class="cloud-img-effect ui-focus-img">
                            </div>

                            <div class="ui-focus-text">
                                <h2 id="focus-eng" class="ui-focus-eng"></h2>
                                <p id="focus-kor" class="ui-focus-kor"></p>
                            </div>
                        </div>

                        <div id="action-buttons-row" class="ui-action-buttons-row"></div>
                    </div>

                    <div class="ui-focus-right">
                        <div class="ui-focus-tabs">
                            <button id="tab-basic" onclick="showUsageTab('basic')" class="tab-button ui-tab-full active">Basic</button>
                        </div>

                        <div class="ui-focus-panels">
                            <div id="tab-content-basic" class="tab-content ui-tab-panel">
                                <div class="ui-tab-scroll">
                                    <div id="basic-usage-table" class="ui-tab-table"></div>
                                </div>
                                <div id="btn-wrapper-basic" class="ui-tab-buttons"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section> <!-- map-section 닫힘 -->

        <section id="board-section">
            <iframe src="./board.php" title="Community Board"></iframe>
        </section>

    </main>
</div>

<button id="scroll-to-top" title="Go to top">
    <i class="fa-solid fa-chevron-up"></i>
</button>

<a href="#board-section" id="floating-board-btn" title="게시판 가기">
    <i class="fa-solid fa-pen-to-square"></i>
    <span>게시판</span>
</a>



<div id="together-modal" class="ui-together-modal hidden pointer-events-none opacity-0">
    <div class="ui-together-backdrop" onclick="closeTogetherModal()"></div>

    <div class="ui-together-center">

        <button onclick="closeTogetherModal()" class="ui-together-close-mobile">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div id="together-wrapper" class="ui-together-wrapper scale-90">

            <button onclick="closeTogetherModal()" class="ui-together-close-desktop">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <iframe id="together-frame" src="" class="ui-together-iframe" allowtransparency="true"></iframe>
        </div>
    </div>
</div>




<div id="drill-guide-modal" class="ui-drill-guide-modal hidden">

    <div class="ui-drill-guide-card">

        <div class="ui-drill-bg-blob ui-drill-bg-blob--green"></div>
        <div class="ui-drill-bg-blob ui-drill-bg-blob--pink"></div>

        <button onclick="closeDrillGuide()" class="ui-drill-close-btn">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="ui-drill-header">
            <h3 class="ui-drill-title">
                <span class="ui-drill-title-accent">How to</span> Play?
            </h3>
            <p class="ui-drill-subtitle">
                마법사가 되는 3가지 단계! 🧙‍♂️
            </p>
        </div>

        <div class="ui-drill-steps">

            <div class="ui-drill-step">
                <div class="ui-drill-step-icon ui-step-pink">
                    <i class="fa-solid fa-hand-pointer"></i>
                </div>
                <div>
                    <h4 class="ui-drill-step-title">1. Pick a Card</h4>
                    <p class="ui-drill-step-desc">
                        화면의 카드를 <span class="ui-bold">클릭</span>하세요.<br>
                        숨겨진 영어 문장이 나타납니다!
                    </p>
                </div>
            </div>

            <div class="ui-drill-step">
                <div class="ui-drill-step-icon ui-step-blue">
                    <i class="fa-solid fa-volume-high"></i>
                </div>
                <div>
                    <h4 class="ui-drill-step-title">2. Listen & Speak</h4>
                    <p class="ui-drill-step-desc">
                        원어민 소리를 듣고 <span class="ui-bold">큰 소리로</span> 따라하세요.<br>
                        문장을 외우면 버튼이 열립니다.
                    </p>
                </div>
            </div>

            <div class="ui-drill-step">
                <div class="ui-drill-step-icon ui-step-green">
                    <i class="fa-solid fa-seedling animate-wiggle"></i>
                </div>
                <div>
                    <h4 class="ui-drill-step-title">3. Collect 7 Seeds</h4>
                    <p class="ui-drill-step-desc">
                        씨앗 <span class="ui-bold">7개</span>를 모두 모으면 성공!<br>
                        나만의 마법 나무를 키워보세요. 🌳
                    </p>
                </div>
            </div>

        </div>

        <button onclick="closeDrillGuide()" class="ui-drill-start-btn btn-glow-border">
            <span class="ui-drill-start-content">
                Let's Start!
                <span class="ui-drill-start-sub">(시작하기)</span>
                <i class="fa-solid fa-arrow-right"></i>
            </span>
            <div class="ui-drill-shimmer"></div>
        </button>

    </div>
</div>







<div id="clear-modal" class="clear-modal is-hidden">
    <div class="clear-modal__backdrop animate-fade"></div>

    <div class="clear-modal__panel animate-pop-in">
        <div class="clear-modal__bird-wrap">
            <i id="success-bird" class="fa-solid fa-dove clear-modal__bird"></i>
        </div>

        <div class="clear-modal__hero">
            <div id="success-tree" class="clear-modal__tree">
                <i class="fa-solid fa-tree"></i>
            </div>

            <i class="fa-solid fa-star clear-modal__star clear-modal__star--a animate-twinkle"></i>
            <i class="fa-solid fa-star clear-modal__star clear-modal__star--b animate-twinkle" style="animation-delay: 0.5s"></i>
        </div>

        <h2 class="clear-modal__title">Mission Complete!</h2>
        <p class="clear-modal__desc">씨앗을 모두 모았어요!</p>

        <button type="button" class="clear-modal__cta" onclick="finishVerb()">
            <span>Get a Stamp!</span>
            <i class="fa-solid fa-stamp clear-modal__cta-icon"></i>
        </button>
    </div>
</div>





<div id="intro-reading-modal" class="intro-modal is-hidden" aria-hidden="true">

    <div class="intro-modal__backdrop" onclick="skipIntroReading()"></div>

    <div id="modal-rain-container" class="intro-modal__rain" aria-hidden="true"></div>

    <button type="button" onclick="skipIntroReading()" class="intro-modal__skip">
        <span>SKIP</span> <i class="fa-solid fa-forward-step"></i>
    </button>

    <div class="intro-modal__panel">

        <div class="intro-modal__panel-bg" aria-hidden="true"></div>

        <div class="intro-modal__content">

            <div class="intro-modal__img-row">
                <div class="intro-shape-frame">
                    <img id="intro-big-img" src="" alt="Intro Image" class="intro-modal__img">
                </div>
            </div>

            <div class="intro-modal__note-wrap">
                <div class="notebook-paper">
                    <div class="notebook-paper__holes" aria-hidden="true"></div>

                    <div class="notebook-paper__body">
                        <h2 id="intro-big-eng" class="intro-modal__eng"></h2>
                        <p id="intro-big-kor" class="intro-modal__kor"></p>
                    </div>
                </div>

                <div class="intro-modal__tape" aria-hidden="true"></div>
            </div>

        </div>

        <div class="intro-modal__footer">

            <div class="intro-modal__footer-top">
                <div class="intro-modal__listen">
                    <i class="fa-solid fa-headphones-simple intro-modal__listen-icon"></i>
                    <span class="intro-modal__listen-text">Listen &amp; Repeat</span>
                </div>

                <div class="intro-modal__count">
                    <span id="intro-read-count" class="intro-modal__count-now">1</span>
                    <span class="intro-modal__count-total">/ 7</span>
                </div>
            </div>

            <div class="intro-modal__progress-track">
                <div id="intro-progress-bar" class="intro-modal__progress-bar" style="width:0%">
                    <div class="intro-modal__train">
                        <img src="./img/ck_train.png"
                             onerror="this.src='https://cdn-icons-png.flaticon.com/512/3063/3063823.png'"
                             class="intro-modal__train-img" alt="">
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>









<div id="tree-php-modal" class="tree-modal is-hidden is-faded" aria-hidden="true">
    <div class="tree-modal__backdrop" onclick="closeTreeModal()"></div>

    <div class="tree-modal__layout">
        <div id="tree-php-wrapper" class="tree-modal__wrapper">
            <button type="button" onclick="closeTreeModal()" class="tree-modal__close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <iframe id="tree-php-frame" src="" class="tree-modal__frame" allowtransparency="true"></iframe>
        </div>
    </div>
</div>

<div id="mobile-card-preview-modal" class="ui-mobile-preview-modal is-hidden" onclick="closeMobilePreview()">
    <div class="ui-mobile-preview-card" onclick="event.stopPropagation()">

        <button type="button" class="ui-mobile-preview-close" onclick="closeMobilePreview()">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="ui-mobile-preview-img-wrap">
            <img id="preview-img" src="" alt="preview" class="ui-mobile-preview-img">
        </div>

        <div class="ui-mobile-preview-text-wrap">
            <h2 id="preview-eng" class="ui-mobile-preview-eng"></h2>
            <p id="preview-kor" class="ui-mobile-preview-kor"></p>

            <button type="button" class="ui-mobile-preview-listen-btn" onclick="playPreviewAudio7Times()">
                <i class="fa-solid fa-volume-high"></i> Listen & Repeat
                <span id="preview-listen-count" class="is-hidden preview-badge">0/7</span>
            </button>
        </div>

        <button type="button" class="ui-mobile-preview-btn" onclick="closeMobilePreview()">
            <span>학습 시작하기</span>
            <i class="fa-solid fa-wand-magic-sparkles"></i>
        </button>

    </div>
</div>
<?php if ($isGuest): ?>
<div id="guest-login-overlay" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);backdrop-filter:blur(6px);align-items:center;justify-content:center">
  <div style="position:relative;background:#fff;border:4px solid #2D2D2D;border-radius:40px;padding:50px 40px;box-shadow:12px 12px 0 #2D2D2D;width:100%;max-width:400px;margin:20px;font-family:Jua,sans-serif">
    <button id="guestCloseBtn" style="position:absolute;top:16px;right:20px;background:none;border:none;font-size:1.5rem;color:#9CA3AF;cursor:pointer;padding:4px 8px"><i class="fa-solid fa-xmark"></i></button>
    <h2 style="font-family:Chewy,cursive;font-size:2rem;text-align:center;margin-bottom:8px;color:#2D2D2D;text-shadow:3px 3px 0 #FF8FA3">chunking english<br>kids&mom</h2>
    <p style="text-align:center;color:#9CA3AF;font-size:0.85rem;margin-bottom:24px">로그인 후 이용할 수 있어요!</p>
    <form id="guestLoginForm">
      <label style="display:block;color:#2D2D2D;font-size:1rem;margin-bottom:10px;padding-left:5px">이메일</label>
      <input type="email" id="guestEmail" placeholder="이메일 입력" required style="width:100%;height:55px;border:3px solid #2D2D2D;border-radius:20px;padding:0 20px;font-size:1.1rem;outline:none;margin-bottom:20px;box-sizing:border-box">
      <label style="display:block;color:#2D2D2D;font-size:1rem;margin-bottom:10px;padding-left:5px">비밀번호</label>
      <input type="password" id="guestPassword" placeholder="비밀번호 입력" required style="width:100%;height:55px;border:3px solid #2D2D2D;border-radius:20px;padding:0 20px;font-size:1.1rem;outline:none;margin-bottom:20px;box-sizing:border-box">
      <div id="guestLoginError" style="color:#FA4252;font-size:0.85rem;margin-bottom:8px;display:none;text-align:center"></div>
      <button type="submit" style="width:100%;height:60px;background:#FF8FA3;color:#fff;border:3px solid #2D2D2D;border-radius:22px;font-size:1.4rem;font-family:Jua,sans-serif;cursor:pointer;box-shadow:0 6px 0 #2D2D2D;display:flex;align-items:center;justify-content:center">ENTER</button>
    </form>
    <a href="find_password.php" style="display:block;text-align:center;margin-top:20px;color:#9CA3AF;font-size:0.9rem;text-decoration:none">비밀번호 찾기</a>
  </div>
</div>
<script>
(function(){
  var ol = document.getElementById("guest-login-overlay");
  var triggered = false;
  var cooldown = false;

  function showLogin() {
    if (triggered || cooldown) return;
    triggered = true;
    ol.style.display = "flex";
  }

  document.getElementById("guestCloseBtn").addEventListener("click", function(e) {
    e.stopPropagation();
    ol.style.display = "none";
    triggered = false;
    cooldown = true;
    setTimeout(function(){ cooldown = false; }, 500);
  });

  ol.addEventListener("click", function(e) { e.stopPropagation(); });

  window.addEventListener("scroll", function() { if (window.scrollY > 50) showLogin(); }, {passive:true});
  document.addEventListener("click", function(e) {
    if (e.target.closest("#logo-container") || e.target.closest(".logo-layer")) return;
    showLogin();
  });

  document.getElementById("guestLoginForm").addEventListener("submit", async function(e) {
    e.preventDefault();
    var errEl = document.getElementById("guestLoginError");
    errEl.style.display = "none";
    try {
      var res = await fetch("./api/auth/login.php", {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({email:document.getElementById("guestEmail").value.trim(), password:document.getElementById("guestPassword").value})
      });
      var data = await res.json();
      if (data.success) { location.reload(); } else { errEl.textContent = data.error || "로그인 실패"; errEl.style.display = "block"; }
    } catch(err) { errEl.textContent = "서버 오류가 발생했습니다."; errEl.style.display = "block"; }
  });
})();
</script>
<?php endif; ?>

<!-- 관리자 진입 버튼 (희미하게 고정) -->
<a href="admin/index.php" title="관리자" style="
    position: fixed;
    left: 12px;
    bottom: 12px;
    z-index: 9999;
    font-size: 10px;
    color: rgba(150,130,135,0.25);
    text-decoration: none;
    letter-spacing: .05em;
    transition: color .3s;
    user-select: none;
" onmouseover="this.style.color='rgba(150,130,135,0.6)'" onmouseout="this.style.color='rgba(150,130,135,0.25)'">admin</a>

</body>
</html>