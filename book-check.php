<?php
require_once 'config/db.php';

// 세션 시작 (auth.php 대신 직접 처리 — localhost 바이패스)
if (session_status() === PHP_SESSION_NONE)
    session_start();
$is_localhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
if (!$is_localhost && empty($_SESSION['user_id'])) {
    require_once 'config/config.php';
    header('Location: ' . (defined('APP_BASE') ? APP_BASE : '') . '/login.php');
    exit;
}

// 범위 파라미터 (배치 분할용)
$mode = $_GET['mode'] ?? 'all';  // all | cover | days | last
$from_day = max(1, intval($_GET['from'] ?? 1));
$to_day = min(250, intval($_GET['to'] ?? 250));

// 본문 데이터 로드 (cover/last 모드는 스킵)
$days = [];
if ($mode === 'all' || $mode === 'days') {
    $stmt = $pdo->prepare("
        SELECT
            d.id AS day_id, d.day_number,
            v.id AS verb_id, v.order_num AS verb_order, v.verb_en, v.verb_kr, v.global_num,
            v.sentence_en AS verb_sentence_en, v.sentence_kr AS verb_sentence_kr,
            e.id AS expr_id, e.order_num AS expr_order, e.expression_en, e.expression_kr,
            e.image_path, e.audio_path
        FROM days d
        JOIN verbs v ON v.day_id = d.id
        JOIN expressions e ON e.verb_id = v.id
        WHERE d.is_active = 1
          AND d.day_number BETWEEN ? AND ?
        ORDER BY d.day_number, v.order_num, e.order_num
    ");
    $stmt->execute([$from_day, $to_day]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $dn = $row['day_number'];
        $vi = $row['verb_id'];
        if (!isset($days[$dn]))
            $days[$dn] = ['day_number' => $dn, 'verbs' => []];
        if (!isset($days[$dn]['verbs'][$vi])) {
            $days[$dn]['verbs'][$vi] = [
                'verb_id' => $vi,
                'verb_en' => $row['verb_en'],
                'verb_kr' => $row['verb_kr'],
                'global_num' => $row['global_num'],
                'order_num' => $row['verb_order'],
                'sentence_en' => $row['verb_sentence_en'],
                'sentence_kr' => $row['verb_sentence_kr'],
                'expressions' => []
            ];
        }
        $days[$dn]['verbs'][$vi]['expressions'][] = [
            'expr_id' => $row['expr_id'],
            'expression_en' => $row['expression_en'],
            'expression_kr' => $row['expression_kr'],
            'image_path' => $row['image_path'],
            'audio_path' => $row['audio_path'],
            'order_num' => $row['expr_order'],
        ];
    }
}

// asset/book-img 슬러그 인덱스 (요청당 1회 빌드, slug → 상대경로)
function book_img_index()
{
    static $idx = null;
    if ($idx !== null)
        return $idx;
    $idx = [];
    $base = __DIR__ . '/asset/book-img';
    if (!is_dir($base))
        return $idx;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (strtolower($file->getExtension()) !== 'png')
            continue;
        $slug = $file->getBasename('.png');
        if (!isset($idx[$slug])) {
            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(__DIR__) + 1));
            $idx[$slug] = $rel;
        }
    }
    return $idx;
}

function book_img_url($expr, $verb, $day_num)
{
    // DB 경로 우선
    if (!empty($expr['image_path'])) {
        $clean = str_replace('\\', '/', $expr['image_path']);
        if (file_exists(__DIR__ . '/' . $clean)) {
            return './' . implode('/', array_map('rawurlencode', explode('/', $clean)));
        }
    }
    // asset/book-img 인덱스에서 표현 슬러그로 검색 (슬러그 정규화: 다중공백 → 단일, 하이픈 제거)
    $expr_en = trim($expr['expression_en']);
    $normalized = preg_replace('/\s+/', ' ', $expr_en);
    $slugs = [
        str_replace(' ', '_', $normalized),                             // 기본
        str_replace([' ', '-'], ['_', ''], $normalized),                // 하이픈 제거
    ];
    $idx = book_img_index();
    foreach ($slugs as $slug) {
        if (isset($idx[$slug])) {
            $rel = $idx[$slug];
            return './' . implode('/', array_map('rawurlencode', explode('/', $rel)));
        }
    }
    return null;
}

/**
 * 이미지 폴더 기반으로 Day 데이터 로드
 * @return array [ ['img_url'=>..., 'expression_en'=>..., 'expression_kr'=>..., 'verb_en'=>...], ... ]
 */
function get_day_folder_images($day_num, $db_days)
{
    $base = __DIR__ . '/asset/book-img';

    // Day 번호 → 범위 폴더 매핑
    $ranges = [
        [1, 50, 'day 1 ~ 50'],
        [51, 100, 'day 51 ~ 100'],
        [101, 150, 'day 101 ~ 150'],
        [151, 200, 'day 151 ~ 200'],
        [201, 220, 'day 201 ~ 220'],
        [221, 250, 'day 221 ~ 250'],
    ];
    $range_folder = null;
    foreach ($ranges as $r) {
        if ($day_num >= $r[0] && $day_num <= $r[1]) {
            $range_folder = $r[2];
            break;
        }
    }
    if (!$range_folder)
        return [];

    $day_path = $base . '/' . $range_folder . '/day ' . $day_num;
    if (!is_dir($day_path))
        return [];

    // Excel 기반 한글 번역 인덱스 로드 (kr_mapping.json)
    static $kr_mapping = null;
    if ($kr_mapping === null) {
        $json_path = __DIR__ . '/asset/kr_mapping.json';
        if (file_exists($json_path)) {
            $kr_mapping = json_decode(file_get_contents($json_path), true) ?: [];
        } else {
            $kr_mapping = [];
        }
    }

    // 동사 폴더 스캔 (숫자 접두어로 정렬)
    $verb_dirs = [];
    foreach (scandir($day_path) as $item) {
        if ($item === '.' || $item === '..')
            continue;
        if (is_dir($day_path . '/' . $item)) {
            $verb_dirs[] = $item;
        }
    }
    sort($verb_dirs); // "01. have", "02. change" ... 순서 정렬

    $all_items = [];
    foreach ($verb_dirs as $vdir) {
        // 폴더명에서 동사 추출 (예: "01. have" → "have")
        $verb_en = preg_replace('/^\d+\.\s*/', '', $vdir);

        $vpath = $day_path . '/' . $vdir;
        $files = [];
        foreach (scandir($vpath) as $f) {
            if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) !== 'png')
                continue;
            if ($f === 'desktop.ini')
                continue;
            $files[] = $f;
        }
        sort($files);

        foreach ($files as $f) {
            $slug = pathinfo($f, PATHINFO_FILENAME); // "have_a_dream"
            $expr_en = str_replace('_', ' ', $slug);  // "have a dream"
            $expr_kr = $kr_mapping[$slug] ?? '';

            $rel = 'asset/book-img/' . $range_folder . '/day ' . $day_num . '/' . $vdir . '/' . $f;
            $img_url = './' . implode('/', array_map('rawurlencode', explode('/', $rel)));

            $all_items[] = [
                'img_url' => $img_url,
                'expression_en' => $expr_en,
                'expression_kr' => $expr_kr,
                'verb_en' => $verb_en,
            ];
        }
    }

    return $all_items;
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <title>청킹으로 쉽게 영어말하기 — <?= $mode === 'cover' ? '표지' : ($mode === 'last' ? '서문' : "Day {$from_day}-{$to_day}") ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Jua&family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" as="style" crossorigin
        href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand-bg: #FFF8FA;
            --brand-white: #FFFFFF;
            --primary: #FF7E96;
            --primary-light: #FFEFF2;
            --secondary: #7CC29D;
            --accent: #FFCE54;
            --text-main: #2A2F32;
            --text-sub: #6E767B;
            --text-gray: #9A9EA3;
            --line-gray: #F0E4E7;
            --red-point: #FA4252;
            --dark-box: #2A2F32;
            --shadow-soft: 0 8px 24px -6px rgba(255, 126, 150, 0.12);
            --shadow-float: 0 12px 32px -8px rgba(255, 126, 150, 0.2);
            --font-kid: 'Jua', cursive;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background-color: white;
                padding: 0;
            }

            .page-break {
                break-before: always;
            }

            .no-print {
                display: none !important;
            }

            .sheet {
                box-shadow: none !important;
                margin: 0 !important;
                border-radius: 0 !important;
            }
        }

        body {
            background: #4A4E53;
            font-family: 'Pretendard', 'Poppins', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0 60px;
            color: var(--text-main);
        }

        /* 상단 컨트롤 (화면 전용, 인쇄 제외) */
        .top-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            color: #ccc;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
        }

        .btn-pdf-download {
            background: #1F2937;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 9px 20px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-pdf-download:hover {
            background: #000;
            transform: translateY(-2px);
        }

        /* 시트 공통 */
        .sheet {
            width: 210mm;
            min-height: 297mm;
            background: var(--brand-bg);
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            margin-bottom: 40px;
            padding: 10mm 12mm 12mm;
            border-radius: 4px;
        }

        .cover-sheet {
            background: var(--brand-bg);
        }

        .bg-deco {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: radial-gradient(var(--line-gray) 1.5px, transparent 1.5px);
            background-size: 28px 28px;
            z-index: 0;
            opacity: 0.7;
            pointer-events: none;
        }

        .z-content {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .cover-sheet .z-content {
            padding: 20px 30px;
        }

        /* 헤더 */
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
            padding-bottom: 0.6rem;
            border-bottom: 2px solid rgba(240, 228, 231, 0.6);
            min-height: 60px;
            gap: 10px;
            flex-shrink: 0;
        }

        .header-left {
            display: flex;
            align-items: center;
            width: 100px;
        }

        .day-badge {
            background: var(--primary);
            color: #FFFFFF;
            padding: 6px 16px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(250, 66, 82, 0.2);
            letter-spacing: 0.5px;
        }

        .header-center {
            flex: 1;
            text-align: right;
            white-space: nowrap;
        }

        .header-center h1 {
            font-family: 'Pretendard', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0;
        }

        .sub-header-text {
            font-family: 'Poppins', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-sub);
            opacity: 0.8;
        }

        .header-right {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            align-items: center;
        }

        .app-mode-btn {
            background: transparent !important;
            border: none;
            padding: 6px 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'Pretendard', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-gray) !important;
        }

        .app-mode-btn img {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }

        .app-mode-btn.active {
            font-weight: 700;
        }

        .font-red {
            color: var(--red-point);
        }

        .drop-shadow {
            filter: drop-shadow(0 2px 4px rgba(250, 66, 82, 0.2));
        }

        /* 청킹 그리드 */
        .chunk-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            flex-grow: 1;
            margin-bottom: 5px;
        }

        .chunk-card {
            background: #FFFFFF;
            padding: 8px;
            border-radius: 14px;
            border: 1px solid #FDF4F6;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .chunk-card.main-point {
            border: 2px solid rgba(255, 126, 150, 0.4);
            box-shadow: var(--shadow-float);
        }

        .img-container {
            width: 100%;
            height: 48%;
            background: #FAFAFA;
            border-radius: 10px;
            margin-bottom: 8px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .img-container .no-img {
            font-size: 2rem;
            color: #ddd;
        }

        .note-area {
            position: relative;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-radius: 10px;
            background: #FFFAFB;
            border: 1px solid #FFEFF2;
            overflow: hidden;
        }

        .note-area.dark {
            background: var(--primary-light);
        }

        .note-line {
            position: absolute;
            inset: 0;
            background-image: repeating-linear-gradient(transparent, transparent 22px, rgba(255, 126, 150, 0.12) 23px);
        }

        .note-margin {
            position: absolute;
            left: 14px;
            top: 0;
            bottom: 0;
            width: 1.5px;
            background: rgba(250, 66, 82, 0.25);
        }

        .note-text-wrap {
            position: relative;
            z-index: 10;
            text-align: center;
            background: rgba(255, 255, 255, 0.85);
            padding: 6px 12px;
            border-radius: 8px;
            backdrop-filter: blur(4px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 126, 150, 0.1);
        }

        .note-text-wrap h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        .note-text-wrap span {
            font-family: 'Pretendard', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-sub);
        }

        /* 매직 카드 */
        .verb-divider {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--primary);
            background: var(--primary-light);
            padding: 3px 12px;
            border-radius: 6px;
            display: inline-block;
            margin: 6px 0 4px;
            align-self: flex-start;
        }

        .magic-card-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex-grow: 1;
            margin-bottom: 5px;
        }

        .magic-card {
            background: #FFFFFF;
            border: 1px solid rgba(255, 126, 150, 0.15);
            border-radius: 12px;
            padding: 6px 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .magic-number-tag {
            background: var(--primary);
            color: #FFFFFF;
            border-radius: 6px;
            width: 28px;
            height: 28px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .magic-chunk-pattern {
            display: flex;
            align-items: center;
            gap: 3px;
            flex-shrink: 0;
            background: var(--primary-light);
            border-radius: 8px;
            padding: 3px 7px;
        }

        .magic-chunk-pattern img {
            width: 20px;
            height: 20px;
            object-fit: contain;
        }

        .magic-chunk-label {
            font-family: 'Pretendard', sans-serif;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--primary);
            white-space: nowrap;
        }

        .magic-content {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .magic-text-box {
            padding: 2px 0;
        }

        .eng-sentence {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 0.98rem;
            color: var(--text-main);
            line-height: 1.2;
            margin-bottom: 1px;
        }

        .kor-sentence {
            font-family: 'Pretendard', sans-serif;
            font-weight: 400;
            font-size: 0.8rem;
            color: var(--text-sub);
        }

        /* 푸터 */
        .page-footer {
            margin-top: auto;
            padding-top: 10px;
            border-top: 2px solid var(--line-gray);
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #A0AAB2;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }

        /* 저작권 안내 바 */
        .copyright-bar {
            margin-top: 8px;
            padding: 8px 14px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            text-align: center;
            flex-shrink: 0;
        }

        .copyright-bar p {
            font-family: 'Pretendard', sans-serif;
            font-size: 8.5px;
            color: #64748B;
            line-height: 1.5;
            letter-spacing: -0.2px;
            margin: 0;
        }

        .copyright-bar strong {
            color: #64748B;
            font-weight: 700;
        }

        /* Listen & Repeat 매직카드 스타일 */
        .lr-list {
            display: flex;
            flex-direction: column;
            gap: 0;
            width: 100%;
            flex-grow: 1;
        }

        .lr-item {
            display: grid;
            grid-template-columns: 40px 250px 1fr;
            align-items: center;
            padding: 7px 8px;
            border-bottom: 1px solid #F0F0F0;
            min-height: 50px;
            flex: 1;
        }

        .lr-item:last-child {
            border-bottom: none;
        }

        .lr-item:nth-child(odd) {
            background: #FFF8FA;
        }

        .lr-item:nth-child(even) {
            background: #FFFFFF;
        }

        .lr-num {
            min-width: 26px;
            height: 24px;
            padding: 0 6px;
            background: var(--primary);
            color: #fff;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.75rem;
            margin: 0 auto;
        }

        .lr-badge-area {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
        }

        .lr-badge-area img {
            width: 18px;
            height: 18px;
            object-fit: contain;
        }

        .lr-chunk-label {
            font-family: 'Pretendard', sans-serif;
            font-size: 0.68rem;
            color: var(--text-sub);
            font-weight: 600;
            white-space: nowrap;
        }

        .lr-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 2px;
            padding-left: 10px;
            text-align: left;
            align-items: flex-start;
        }

        .lr-text .en {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 0.82rem;
            color: var(--text-main);
        }

        .lr-text .kr {
            font-family: 'Pretendard', sans-serif;
            font-weight: 400;
            font-size: 0.68rem;
            color: var(--text-sub);
        }
    </style>
</head>

<body>

    <!-- ── 상단 컨트롤 (인쇄 제외) ── -->
    <div class="top-controls no-print">
        <span>저작권 등록용 —
            <?= $mode === 'cover' ? '표지' : ($mode === 'last' ? '서문(뒷표지)' : "Day {$from_day}~{$to_day}") ?></span>
        <button class="btn-pdf-download" onclick="window.print()">
            <i class="fa-solid fa-file-pdf"></i> PDF 저장 (Ctrl+P)
        </button>
    </div>

    <!-- ==========================================
     표지 1: 메인 앞표지
=========================================== -->
    <?php if ($mode === 'all' || $mode === 'cover' || $mode === 'days'): ?>
        <div class="sheet cover-sheet" style="border: 15px solid #2A2F32;">
            <div class="bg-deco"></div>
            <div class="z-content" style="justify-content: center; align-items: center; text-align: center;">
                <div
                    style="flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                    <h1
                        style="font-family: var(--font-kid); font-size: 3.5rem; color: var(--text-main); margin-bottom: 5px; line-height: 1.2;">
                        <span style="color: var(--red-point);">청킹</span>으로 쉽게<br>영어말하기
                    </h1>
                    <h2
                        style="font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.4rem; color: var(--text-main); margin-bottom: 50px; letter-spacing: 0.5px;">
                        <span style="color: var(--red-point);">Chunking</span>-Based Easy Speaking
                    </h2>
                    <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 50px;">
                        <img src="./img/exc_n1.png" onerror="this.style.display='none'"
                            style="width: 150px; height: 150px; object-fit: contain;" alt="character">
                    </div>
                    <p style="font-size: 1.25rem; font-weight: 700; color: var(--text-sub); letter-spacing: 1px;">
                        이지윤 윤재우 윤보영 <span style="font-weight: 500; opacity: 0.8;">공저</span>
                    </p>
                </div>
                <div
                    style="font-size: 2.8rem; font-weight: 900; color: transparent; padding-bottom: 30px; letter-spacing: -2px; user-select: none;">
                    선</div>
            </div>
        </div>

        <!-- ==========================================
     표지 2: 공동저자 소개
=========================================== -->
        <div class="sheet cover-sheet page-break">
            <div class="bg-deco"></div>
            <div class="z-content">
                <h2
                    style="font-family: 'Pretendard', sans-serif; font-weight: 800; font-size: 2.2rem; color: var(--text-main); text-align: center; margin: 10px 0 60px 0; letter-spacing: -1px;">
                    공동저자</h2>

                <?php
                $authors = [
                    [
                        'name_kr' => '이지윤',
                        'name_en' => 'Lee Ji Yoon',
                        'role' => '캐릭터',
                        'photo' => './img/au01.png',
                        'items' => [
                            '현) ㈜투게더7500 대표',
                            '현) 한국지역커뮤니티협회 발행인',
                            '전) 경향에듀케이션 부사장',
                            '뉴질랜드 피지컬칼리지 영어과 Certificate',
                            '평생교육사 2급 자격증',
                        ],
                    ],
                    [
                        'name_kr' => '윤재우',
                        'name_en' => 'Yoon Jae Woo',
                        'role' => '콘텐츠',
                        'photo' => './img/au02.png',
                        'items' => [
                            '현) PPSS(ㅍㅍㅅㅅ) 뉴미디어 대표',
                            '현) 한국인공지능빅데이터연구조합 부회장',
                            '전) 한국식품안전관리인증원 기획경영이사',
                            '전) 대통령비서실 디지털소통비서관실 선임행정관',
                            '\'킹스피킹AUTO: 마법의 숫자7\' 저자 (2012년 11월, 선)',
                        ],
                    ],
                    [
                        'name_kr' => '윤보영',
                        'name_en' => 'Yoon Bo Young',
                        'role' => '스피킹',
                        'photo' => './img/au03.png',
                        'items' => [
                            '현) 문화예술 스타트업 ㈜사콘느 대표',
                            '현) 키즈오페라 콘텐츠 크리에이터 겸 바이올리니스트',
                            '서울대 음대 학사 석사 졸업, 한양대 아동심리치료학과 박사 수료',
                            '키즈오페라 동화 애니메이션 10종(2022년 04월) 저자',
                            '키즈오페라 동화 10종(2022년 06월) 저자',
                        ],
                    ],
                ];
                foreach ($authors as $i => $a):
                    ?>
                    <div
                        style="background: #FFFFFF; border: 1px solid var(--line-gray); border-radius: 16px; padding: 20px 24px; margin-bottom: 16px; display: flex; gap: 20px; align-items: flex-start; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                        <div
                            style="width: 72px; height: 72px; border-radius: 50%; overflow: hidden; flex-shrink: 0; border: 2px solid var(--primary-light);">
                            <img src="<?= $a['photo'] ?>"
                                onerror="this.parentNode.style.background='var(--primary-light)';this.style.display='none'"
                                style="width:100%;height:100%;object-fit:cover;" alt="<?= $a['name_kr'] ?>">
                        </div>
                        <div style="flex:1;">
                            <p
                                style="font-family: 'Pretendard', sans-serif; font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-bottom: 2px;">
                                <?= $a['name_kr'] ?>
                                <span
                                    style="font-family:'Poppins'; font-weight:500; font-size:0.88rem; color:var(--text-sub); margin-left:6px;"><?= $a['name_en'] ?></span>
                                <span
                                    style="font-size:0.78rem; font-weight:700; color:var(--primary); background:var(--primary-light); padding:2px 8px; border-radius:20px; margin-left:6px;">&lt;<?= $a['role'] ?>&gt;</span>
                            </p>
                            <ul style="list-style:none; margin-top:8px; padding:0;">
                                <?php foreach ($a['items'] as $item): ?>
                                    <li
                                        style="font-size:0.85rem; color:var(--text-sub); line-height:1.7; padding-left:14px; position:relative;">
                                        <span style="position:absolute;left:0;color:var(--primary);">·</span><?= $item ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>

    <?php endif; /* cover */ ?>

    <!-- ==========================================
     서문 페이지 (Day 본문 앞에 배치)
=========================================== -->
    <?php if ($mode === 'all' || $mode === 'days' || $mode === 'last'): ?>
        <div class="sheet cover-sheet page-break">
            <div class="bg-deco"></div>
            <div class="z-content" style="justify-content: center;">
                <h2
                    style="font-family: var(--font-kid); font-size: 1.8rem; color: var(--text-main); text-align: center; margin-bottom: 25px; line-height: 1.4;">
                    드디어, 청킹<span style="font-family: 'Poppins';">Chunking</span>하다?<br>
                    <span style="color: var(--primary);">자유롭고 유창하게 말하다 영어를!</span>
                </h2>

                <div style="font-size: 0.95rem; line-height: 1.6; color: var(--text-main); font-weight: 500;">
                    <p style="margin-bottom: 15px; font-weight: 700; font-size: 1.05rem;">
                        지금까지 이런 책은 없었습니다! 세상에 없었던 새로운 콘텐츠!<br>
                        원어민과 같은 속도로 유창하게 영어 말하기를 250시간에 가능하게 하는<br>
                        숨겨진 그 비법의 의미덩어리 청킹 기본표현을 완벽 정리하여 마침내 공개합니다!
                    </p>

                    <h3
                        style="color: var(--primary); margin: 20px 0 10px; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-star"></i> 제1단계 : 청킹 기본표현 반복숙달
                    </h3>
                    <p style="margin-bottom: 10px;">
                        실제 일상생활에서 많이 사용하는, 아주 쉬운 초급동사 354개를 활용한,<br>
                        직관적인 마법사 청킹 이미지와 함께하는 짧고 간단한 5,250개 의미덩어리 청킹 표현!
                    </p>

                    <p
                        style="margin-bottom: 10px; background: #FFF; padding: 12px; border-radius: 12px; border: 1px solid #F0E4E7;">
                        속도와 정확성이 생명인 영어 동시통역에서는, 단어 하나하나를 번역하는 게 아니라, 의미 있는 덩어리 청킹(chunking)으로 묶어서 통역하는 것이 아주 중요한 핵심 기술입니다.
                        즉 문장을 청킹으로 나눠서 각 덩어리를 하나의 단위로 통역합니다.
                    </p>

                    <p style="margin-bottom: 10px;">
                        누구나 청킹학습법의 우수성을 말하고 권장하였지만, 지금 이 책처럼 이렇게 방대한 핵심적인 청킹표현을 체계적으로 정리한 자료는 없었습니다. <span
                            style="color: var(--text-sub);">(꼬박 1년의 시간을 쏟아 부었습니다.)</span><br>
                        쉬운 단어의 의미덩어리 청킹이 반복 숙달되면 학습자는 실제 생활에서 청킹을 바로 꺼내 쓰기 때문에, 단기간에 영어말하기 능력이 문장 수준으로 급격히 올라갑니다.
                    </p>

                    <h3
                        style="color: var(--primary); margin: 25px 0 10px; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-puzzle-piece"></i> 제2단계 : 청킹과 청킹을 투게더
                    </h3>
                    <p style="margin-bottom: 10px; font-weight: 700; color: var(--red-point);">
                        청킹과 청킹을 투게더하면, 나의 영어나무가 자라요!<br>
                        청킹과 청킹을 투게더하면, 나의 창의력도 함께 자라요!
                    </p>
                    <p style="margin-bottom: 10px;">
                        1일 3개 청킹으로 to부정사, 동명사ing, 전치사+동명사구, 부사절, 등위절 까지 10개문장을,<br>
                        250일 750개의 청킹으로 2,500개 문장을 만드는 know-how와 예시를 책에 담았습니다.
                    </p>

                    <p
                        style="margin-top: 20px; font-size: 0.8rem; color: #64748B; background: #F8FAFC; padding: 12px 16px; border-radius: 12px; border: 1px dashed #CBD5E1; line-height: 1.5;">
                        * 웹기반 트레이닝 훈련용 프로그램은 5,250개 청킹의 1,2,3인칭과 의문문 부정문 명령문 등 36,750개 활용문장을 각 7번씩 반복하여 훈련함으로써 학습자가 자연스럽게
                        말하기 속도와 유창성을 향상하도록 합니다.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; /* preface */ ?>

    <!-- ==========================================
     Day 본문
=========================================== -->
    <?php if ($mode === 'all' || $mode === 'days'): ?>
        <?php
        $global_page_num = 3; // 표지 1, 2 이후부터
        for ($day_num = $from_day; $day_num <= $to_day; $day_num++):
            // 이미지 폴더 기반으로 데이터 로드
            $folder_items = get_day_folder_images($day_num, $days);
            if (empty($folder_items))
                continue;

            $pages = array_chunk($folder_items, 8);
            foreach ($pages as $pi => $page_items):
                ?>
                <div class="sheet page-break">
                    <div class="bg-deco"></div>
                    <div class="z-content">
                        <header class="main-header">
                            <div class="header-left">
                                <div class="day-badge">Day <?= $day_num ?></div>
                            </div>
                            <div class="header-center">
                                <h1><span class="font-red drop-shadow">청킹</span>으로 쉽게 영어말하기</h1>
                                <p class="sub-header-text">(<span class="font-red">Chunking</span>-Based Easy Speaking)</p>
                            </div>
                        </header>

                        <section class="chunk-grid">
                            <?php foreach ($page_items as $ci => $item):
                                $is_main = ($ci === 0);
                                ?>
                                <div class="chunk-card <?= $is_main ? 'main-point' : '' ?>">
                                    <div class="img-container">
                                        <img src="<?= htmlspecialchars($item['img_url']) ?>"
                                            alt="<?= htmlspecialchars($item['expression_en']) ?>">
                                    </div>
                                    <div class="note-area <?= $is_main ? 'dark' : 'light' ?>">
                                        <div class="note-line"></div>
                                        <div class="note-margin"></div>
                                        <div class="note-text-wrap">
                                            <h3><?= htmlspecialchars($item['expression_en']) ?></h3>
                                            <span><?= htmlspecialchars($item['expression_kr'] ?: '뜻 적기') ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (count($page_items) < 9): ?>
                                <!-- 아이콘 셀: 청킹기본 / 청킹변화 -->
                                <div
                                    style="display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 10px; padding: 10px;">
                                    <div class="app-mode-btn active">
                                        <img src="./img/wct01_n.png" onerror="this.style.display='none'">
                                        <span>청킹기본</span>
                                    </div>
                                    <div class="app-mode-btn">
                                        <img src="./img/wct02.png" onerror="this.style.display='none'">
                                        <span>청킹변화</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </section>

                        <footer class="page-footer">
                            <span>© CHUNKING ENGLISH KIDS&amp;MOM</span>
                            <span>PAGE <?= str_pad($global_page_num++, 2, '0', STR_PAD_LEFT) ?></span>
                        </footer>
                        <div class="copyright-bar">
                            <p><strong>© 저작권 안내</strong> | 이 책에 실린 내용, 이미지, 소리, 음원, 디자인, 편집 구성의 저작권은 저자에게 있습니다.<br>상업적 사용목적으로 허락 없이
                                복제하거나 함부로 사용할 경우 민형사상 책임을 질 수 있습니다. 개인 학습의 경우, 출처 '청킹으로 쉽게 영어말하기'를 밝히면 언제 어디서나 저작권 제한 없이 사용 가능합니다.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; /* pages */ ?>

            <?php
            // 10words_mapping.json에서 Listen & Repeat 데이터 로드
            static $ten_words = null;
            if ($ten_words === null) {
                $tw_path = __DIR__ . '/asset/10words_mapping.json';
                if (file_exists($tw_path)) {
                    $ten_words = json_decode(file_get_contents($tw_path), true) ?: [];
                } else {
                    $ten_words = [];
                }
            }
            $lr_items = $ten_words[strval($day_num)] ?? [];
            if (!empty($lr_items)):
                ?>
                <div class="sheet page-break">
                    <div class="bg-deco"></div>
                    <div class="z-content">
                        <header class="main-header">
                            <div class="header-left">
                                <div class="day-badge">Day <?= $day_num ?></div>
                            </div>
                            <div class="header-center">
                                <h1><span class="font-red drop-shadow">청킹</span>으로 쉽게 영어말하기</h1>
                                <p class="sub-header-text">(<span class="font-red">Chunking</span>-Based Easy Speaking)</p>
                            </div>
                            <div class="header-right" style="display:flex;gap:10px;align-items:center;">
                                <div class="app-mode-btn active" style="padding:4px 10px;display:flex;align-items:center;gap:4px;">
                                    <img src="./img/wct01_n.png" onerror="this.style.display='none'"
                                        style="width:20px;height:20px;">
                                    <span style="font-size:0.7rem;">청킹기본</span>
                                </div>
                                <div class="app-mode-btn" style="padding:4px 10px;display:flex;align-items:center;gap:4px;">
                                    <img src="./img/wct02.png" onerror="this.style.display='none'" style="width:20px;height:20px;">
                                    <span style="font-size:0.7rem;font-weight:800;">청킹변화</span>
                                </div>
                            </div>
                        </header>

                        <div class="lr-list">
                            <?php
                            $card_num = 1;
                            foreach ($lr_items as $item):
                                $type_label = $item['chunk_type'] ?: '';
                                $en_display = trim($item['en_front'] . ' ' . $item['en_back']);
                                $kr_front = $item['kr_front'] ?: '';
                                $kr_back = $item['kr_back'] ?: '';
                                $kr_display = trim($kr_front . ' ' . $kr_back);
                                if (empty($kr_display)) $kr_display = '뜻 적기';
                                ?>
                                <div class="lr-item">
                                    <div class="lr-num"><?= $card_num++ ?></div>
                                    <div class="lr-badge-area">
                                        <img src="./img/wct01_n.png" onerror="this.style.display='none'" alt="">
                                        <span class="lr-chunk-label">+&nbsp;<?= htmlspecialchars($type_label) ?>&nbsp;+</span>
                                        <img src="./img/wct02.png" onerror="this.style.display='none'" alt="">
                                    </div>
                                    <div class="lr-text">
                                        <span class="en"><?= htmlspecialchars($en_display) ?></span>
                                        <span class="kr"><?= htmlspecialchars($kr_display) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <footer class="page-footer">
                            <span>© CHUNKING ENGLISH KIDS&amp;MOM</span>
                            <span>PAGE <?= str_pad($global_page_num++, 2, '0', STR_PAD_LEFT) ?></span>
                        </footer>
                        <div class="copyright-bar">
                            <p><strong>© 저작권 안내</strong> | 이 책에 실린 내용, 이미지, 소리, 음원, 디자인, 편집 구성의 저작권은 저자에게 있습니다.<br>상업적 사용목적으로 허락 없이
                                복제하거나 함부로 사용할 경우 민형사상 책임을 질 수 있습니다. 개인 학습의 경우, 출처 '청킹으로 쉽게 영어말하기'를 밝히면 언제 어디서나 저작권 제한 없이 사용 가능합니다.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; /* listen & repeat */ ?>

        <?php endfor; /* days */ ?>
    <?php endif; /* days */ ?>

    <!-- ==========================================
     뒷표지: 마무리 메시지
=========================================== -->
    <div class="sheet cover-sheet page-break" style="border: 15px solid #2A2F32;">
        <div class="bg-deco"></div>
        <div class="z-content" style="justify-content: center; align-items: center; padding: 30px 36px;">

            <h2
                style="font-family: var(--font-kid); font-size: 2.4rem; color: var(--text-main); line-height: 1.45; margin-bottom: 24px; text-align: center;">
                내 아이와 나의 인생을 바꿀 기회...<br>놓치지 마세요!
            </h2>

            <h3
                style="font-family: 'Pretendard', sans-serif; font-weight: 800; font-size: 1.5rem; color: var(--text-main); margin-bottom: 8px; text-align: center;">
                영어로.... 누구나... 즉시... 저절로 말한다!
            </h3>
            <p style="font-size: 1.1rem; color: var(--text-sub); margin-bottom: 22px; text-align: center;">보고 듣고 생각한 것을
            </p>

            <div
                style="background: var(--primary-light); border-radius: 30px; padding: 14px 28px; margin-bottom: 28px; text-align: center;">
                <p
                    style="font-family: 'Pretendard', sans-serif; font-weight: 700; font-size: 1.2rem; color: var(--red-point); margin: 0;">
                    영어 말하기는 공부가 아닙니다. 단지 트레이닝 훈련입니다!
                </p>
            </div>

            <p
                style="font-size: 1.1rem; color: var(--text-main); line-height: 1.9; margin-bottom: 6px; text-align: center;">
                영어단어 영어문법 영어어순을 각각 따로따로 학습하여<br>
                영어로 말할 때 무엇을 어떻게 할 것인가 고민하던 것은
            </p>
            <p
                style="font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.6rem; color: var(--red-point); margin-bottom: 24px; text-align: center;">
                이제 No No No!
            </p>

            <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                <img src="./img/exc_n1.png" onerror="this.style.display='none'"
                    style="width: 150px; height: 150px; object-fit: contain;" alt="character">
            </div>

            <p
                style="font-size: 1.1rem; color: var(--text-main); line-height: 1.9; margin-bottom: 6px; text-align: center;">
                마법사 청킹 <span style="font-family: 'Poppins'; font-weight: 700;">Chunking</span>과 함께<br>
                영어단어 영어문법 영어어순이 결합되어 있는
            </p>

            <div
                style="background: var(--secondary); border-radius: 30px; padding: 14px 28px; margin: 16px 0 26px; text-align: center;">
                <p
                    style="font-family: 'Pretendard', sans-serif; font-weight: 800; font-size: 1.2rem; color: #FFFFFF; margin: 0;">
                    의미덩어리 청킹<span style="font-family: 'Poppins';">Chunking</span>을 반복 숙달하여
                </p>
            </div>

            <div
                style="border: 2px solid var(--primary); border-radius: 16px; padding: 18px 24px; margin-bottom: 26px; background: #FFFBFC; text-align: center;">
                <p style="font-size: 1.15rem; color: var(--primary); font-weight: 600; line-height: 1.8; margin: 0;">
                    우리 아이의 영어미래를 활짝 열어주고<br>
                    나의 영어세계를 더 높이 더 넓게 펼쳐가세요.
                </p>
            </div>

            <p
                style="font-family: 'Pretendard', sans-serif; font-weight: 800; font-size: 1.3rem; color: var(--text-main); margin-bottom: 6px; text-align: center;">
                의미덩어리 청킹<span style="font-family: 'Poppins';">Chunking</span>으로...
            </p>
            <p
                style="font-weight: 700; font-size: 1.1rem; color: var(--primary); margin-bottom: 0; text-align: center;">
                영어로... 자유롭고 유창하게... 글로벌 세상과 소통하세요^^
            </p>

            <div class="copyright-bar" style="margin-top: 24px; width: 100%;">
                <p><strong>© 저작권 안내</strong> | 이 책에 실린 내용, 이미지, 소리, 음원, 디자인, 편집 구성의 저작권은 저자에게 있습니다.<br>상업적 사용목적으로 허락 없이
                    복제하거나 함부로 사용할 경우 민형사상 책임을 질 수 있습니다.<br>개인 학습의 경우, 출처 '청킹으로 쉽게 영어말하기'를 밝히면 언제 어디서나 저작권 제한 없이 사용
                    가능합니다.</p>
            </div>
        </div>
    </div>

</body>

</html>