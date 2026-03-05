<?php
require_once 'config/db.php';
require_once 'config/auth.php';

$day_num = max(1, min(250, intval($_GET['day'] ?? 1)));

// Day 조회
$stmt = $pdo->prepare("SELECT * FROM days WHERE day_number = ? AND is_active = 1");
$stmt->execute([$day_num]);
$day_row = $stmt->fetch();

if (!$day_row) { http_response_code(404); die("Day를 찾을 수 없습니다."); }

// 동사 3개 + 표현 7개씩 조회
$stmt = $pdo->prepare("SELECT * FROM verbs WHERE day_id = ? ORDER BY order_num");
$stmt->execute([$day_row['id']]);
$verbs = $stmt->fetchAll();

foreach ($verbs as &$verb) {
    $stmt = $pdo->prepare("SELECT * FROM expressions WHERE verb_id = ? ORDER BY order_num");
    $stmt->execute([$verb['id']]);
    $verb['expressions'] = $stmt->fetchAll();
}
unset($verb);

// 경로 → 웹 URL 변환
function book_asset_url($path) {
    if (!$path) return null;
    $clean = str_replace('\\', '/', $path);
    if (!file_exists(__DIR__ . '/' . $clean)) return null;
    $parts = explode('/', $clean);
    return './' . implode('/', array_map('rawurlencode', $parts));
}

$prev_day = $day_num > 1  ? $day_num - 1 : null;
$next_day = $day_num < 250 ? $day_num + 1 : null;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Chunking English E-Book - Day <?= $day_num ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ---------------------------------
           [CSS 변수 정의]
        --------------------------------- */
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
            --shadow-inner: inset 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        /* ---------------------------------
           [기본 레이아웃 및 초기화]
        --------------------------------- */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background-color: white; padding: 0; }
            .page-break { break-before: always; }
            .no-print { display: none !important; }
            .no-print-temp { display: none !important; }
            .sheet { box-shadow: none !important; margin: 0 !important; border-radius: 0 !important; }
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

        /* ---------------------------------
           [상단 컨트롤]
        --------------------------------- */
        .top-controls {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }

        /* Day 네비게이션 */
        .day-nav {
            display: flex; align-items: center; gap: 12px;
            font-family: 'Poppins', sans-serif;
        }
        .day-nav a {
            background: #FF7E96; color: white; padding: 7px 18px;
            border-radius: 20px; text-decoration: none; font-size: 0.95rem;
            font-weight: 600; transition: opacity 0.2s;
        }
        .day-nav a:hover { opacity: 0.85; }
        .day-nav a.disabled { background: #888; pointer-events: none; }
        .day-nav span { color: white; font-size: 1.1rem; font-weight: 700; }

        .btn-pdf-download {
            background: #1F2937;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 8px 18px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.2s;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-pdf-download:hover { transform: translateY(-2px); background: #000000; box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4); }

        /* ---------------------------------
           [PDF 저장 모달창]
        --------------------------------- */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);
            display: none; justify-content: center; align-items: center; z-index: 9999;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: white; padding: 28px; border-radius: 16px;
            width: 380px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            font-family: 'Pretendard', sans-serif;
        }
        .modal-content h2 { margin-bottom: 16px; font-size: 1.2rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px; }

        .print-option-label {
            display: flex; align-items: center; gap: 12px; padding: 11px 14px;
            border: 2px solid #F0E4E7; border-radius: 12px; margin-bottom: 8px;
            cursor: pointer; transition: all 0.2s; font-weight: 600; color: var(--text-main); font-size: 0.9rem;
        }
        .print-option-label:hover { border-color: #3B82F6; background: #EFF6FF; }
        .print-option-label input[type="radio"] { accent-color: #3B82F6; transform: scale(1.2); }

        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn-modal { padding: 9px 16px; border-radius: 10px; border: none; cursor: pointer; font-weight: 700; font-family: 'Pretendard', sans-serif; font-size: 0.95rem; transition: all 0.2s; }
        .btn-cancel { background: #F0E4E7; color: #6E767B; }
        .btn-cancel:hover { background: #E2D5D8; }
        .btn-confirm { background: #3B82F6; color: white; }
        .btn-confirm:hover { background: #2563EB; }

        /* ---------------------------------
           [시트 공통]
        --------------------------------- */
        .sheet {
            width: 210mm;
            min-height: 297mm;
            background: var(--brand-bg);
            position: relative;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
            margin-bottom: 40px;
            padding: 10mm 12mm 12mm;
            border-radius: 4px;
        }
        .bg-deco {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(var(--line-gray) 1.5px, transparent 1.5px);
            background-size: 28px 28px; z-index: 0; opacity: 0.7; pointer-events: none;
        }
        .z-content { position: relative; z-index: 10; display: flex; flex-direction: column; flex-grow: 1; }

        /* ---------------------------------
           [상단 헤더 스타일]
        --------------------------------- */
        .main-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 0.8rem; padding-bottom: 0.6rem;
            border-bottom: 2px solid rgba(240, 228, 231, 0.6);
            min-height: 60px;
            gap: 10px;
            flex-shrink: 0;
        }

        .header-left { display: flex; align-items: center; width: 100px; }

        .day-badge {
            background: var(--dark-box);
            color: #FFFFFF;
            padding: 6px 16px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1.15rem; font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            letter-spacing: 0.5px;
        }

        .header-center {
            flex: 1; text-align: center;
            white-space: nowrap;
        }
        .header-center h1 { font-family: 'Pretendard', sans-serif; font-weight: 700; font-size: 1.5rem; margin-bottom: 0; }
        .sub-header-text { font-family: 'Poppins', sans-serif; font-size: 0.82rem; font-weight: 600; color: var(--text-sub); opacity: 0.8; }

        .header-right { display: flex; justify-content: flex-end; gap: 8px; align-items: center; }

        .app-mode-btn {
            background: transparent !important;
            border: none;
            padding: 6px 10px;
            border-radius: 8px;
            display: flex; align-items: center; gap: 6px;
            font-family: 'Pretendard', sans-serif; font-weight: 600; font-size: 1rem;
            color: var(--text-gray) !important;
            cursor: pointer;
        }
        .app-mode-btn img { width: 28px; height: 28px; object-fit: contain; }
        .app-mode-btn.active { font-weight: 700; }

        .btn-listen-repeat {
            background-color: #FFFFFF !important;
            color: #3B82F6 !important;
            border: 2px solid #3B82F6 !important;
            border-radius: 10px;
            padding: 6px 14px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all 0.2s ease-in-out;
        }
        .btn-listen-repeat:hover {
            background-color: #3B82F6 !important;
            color: #FFFFFF !important;
            transform: translateY(-1px);
        }
        .btn-listen-repeat:active { transform: translateY(0); }

        .tts-player-bar {
            display: none;
            background: white; border: 2px solid #BFDBFE; border-radius: 12px;
            padding: 6px 14px; align-items: center; gap: 12px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
            flex-shrink: 0;
        }
        .tts-player-bar.active { display: flex; }

        .player-ctrl-btn {
            width: 28px; height: 28px; border-radius: 50%; border: none;
            background: #EFF6FF; color: #3B82F6; cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 0.8rem;
            transition: all 0.2s; flex-shrink: 0;
        }
        .player-ctrl-btn:hover { background: #3B82F6; color: white; }

        .progress-text { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 0.9rem; color: #3B82F6; text-align: center; min-width: 40px; }

        .font-red { color: var(--red-point); }
        .drop-shadow { filter: drop-shadow(0 2px 4px rgba(250, 66, 82, 0.2)); }

        /* ---------------------------------
           [청킹 그리드 (Page 1~3)]
        --------------------------------- */
        .chunk-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            flex-grow: 1;
            margin-bottom: 5px;
        }

        .chunk-card {
            background: #FFFFFF; padding: 8px;
            border-radius: 14px;
            border: 1px solid #FDF4F6; display: flex; flex-direction: column;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: all 0.3s ease;
        }
        .chunk-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(255,126,150,0.12); border-color: #FFC9D4; }
        .chunk-card.main-point { border: 2px solid rgba(255, 126, 150, 0.4); box-shadow: var(--shadow-float); }

        .img-container {
            width: 100%; height: 48%;
            background: #FAFAFA;
            border-radius: 10px; margin-bottom: 8px; overflow: hidden;
            display: flex; justify-content: center; align-items: center;
        }
        .img-container img { width: 100%; height: 100%; object-fit: cover; }
        .img-container .no-img { font-size: 2rem; color: #ddd; }

        .note-area {
            position: relative; flex-grow: 1; display: flex; flex-direction: column;
            justify-content: center; align-items: center; border-radius: 10px;
            background: #FFFAFB;
            border: 1px solid #FFEFF2;
            overflow: hidden;
        }
        .note-area.dark { background: var(--primary-light); }
        .note-area.light { background: #FFFAFB; }
        .note-line {
            position: absolute; inset: 0;
            background-image: repeating-linear-gradient(transparent, transparent 22px, rgba(255, 126, 150, 0.12) 23px);
        }
        .note-margin {
            position: absolute; left: 14px; top: 0; bottom: 0; width: 1.5px;
            background: rgba(250, 66, 82, 0.25);
        }

        .note-text-wrap {
            position: relative; z-index: 10; text-align: center;
            background: rgba(255,255,255,0.85);
            padding: 6px 12px; border-radius: 8px; backdrop-filter: blur(4px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.03); border: 1px solid rgba(255, 126, 150, 0.1);
        }
        .note-text-wrap h3 { font-family: 'Poppins', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 2px; }
        .note-text-wrap span { font-family: 'Pretendard', sans-serif; font-size: 0.85rem; font-weight: 500; color: var(--text-sub); }

        /* ---------------------------------
           [매직 카드 리스트 (Page 4)]
        --------------------------------- */
        .verb-divider {
            font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 0.8rem;
            color: var(--primary); background: var(--primary-light);
            padding: 3px 12px; border-radius: 6px; display: inline-block;
            margin: 6px 0 4px; align-self: flex-start;
        }

        .magic-card-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex-grow: 1;
            margin-bottom: 5px;
        }

        .magic-card {
            background: #FFFFFF; border: 1px solid rgba(255, 126, 150, 0.15); border-radius: 12px;
            padding: 6px 14px; display: flex; align-items: center; gap: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .magic-card:hover {
            box-shadow: 0 6px 16px rgba(255,126,150,0.1);
            border-color: #FFC9D4;
        }
        .magic-card.reading {
            border-color: #00F3FF;
            box-shadow: 0 0 12px rgba(0, 243, 255, 0.6), inset 0 0 8px rgba(0, 243, 255, 0.1);
            transform: scale(1.01);
            z-index: 10;
        }

        .magic-number-tag {
            background: var(--dark-box);
            color: #FFFFFF;
            border-radius: 6px;
            width: 30px; height: 30px;
            font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 0.95rem;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .magic-content {
            flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center;
        }

        .magic-text-box {
            padding: 2px 0;
        }

        .eng-sentence { font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 1.05rem; color: var(--text-main); line-height: 1.2; margin-bottom: 1px; }
        .kor-sentence { font-family: 'Pretendard', sans-serif; font-weight: 400; font-size: 0.85rem; color: var(--text-sub); }

        /* ---------------------------------
           [푸터]
        --------------------------------- */
        .page-footer {
            margin-top: auto;
            padding-top: 10px; border-top: 2px solid var(--line-gray);
            display: flex; justify-content: space-between; font-size: 11px; color: #A0AAB2;
            font-family: 'Poppins', sans-serif; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;
            flex-shrink: 0;
        }
    </style>

    <script>
        /** PDF 출력 로직 */
        function openPdfModal() {
            document.getElementById('pdfModal').classList.add('active');
        }
        function closePdfModal() {
            document.getElementById('pdfModal').classList.remove('active');
        }
        function printCurrentDay() {
            closePdfModal();
            window.print();
        }

        /** TTS 엔진 */
        let ttsState = {
            isPlaying: false, isPaused: false, currentSheet: null,
            sentences: [], currentSentenceIndex: 0, currentAudio: null
        };
        const wait = ms => new Promise(r => setTimeout(r, ms));

        async function startTTSSequence(btn) {
            const sheet = btn.closest('.sheet');
            window.speechSynthesis.cancel();
            if (ttsState.currentAudio) { ttsState.currentAudio.pause(); ttsState.currentAudio = null; }
            ttsState = {
                isPlaying: true, isPaused: false, currentSheet: sheet,
                sentences: Array.from(sheet.querySelectorAll('.magic-card')),
                currentSentenceIndex: 0, currentAudio: null
            };
            btn.style.display = 'none';
            sheet.querySelector('.tts-player-bar').classList.add('active');
            updatePlayerUI(sheet);
            processQueue();
        }

        async function processQueue() {
            while (ttsState.isPlaying && ttsState.currentSentenceIndex < ttsState.sentences.length) {
                if (ttsState.isPaused) { await wait(200); continue; }
                const card = ttsState.sentences[ttsState.currentSentenceIndex];
                const audioUrl = card.dataset.audioUrl;
                const text = card.querySelector('.eng-sentence').innerText;
                ttsState.sentences.forEach(c => c.classList.remove('reading'));
                card.classList.add('reading');
                updatePlayerUI(ttsState.currentSheet);
                if (audioUrl) { await playMp3(audioUrl); } else { await speakTTS(text); }
                ttsState.currentSentenceIndex++;
                await wait(500);
            }
            stopTTS();
        }

        function playMp3(url) {
            return new Promise(resolve => {
                const audio = new Audio(url);
                ttsState.currentAudio = audio;
                audio.onended = () => resolve();
                audio.onerror = () => resolve();
                audio.play().catch(() => resolve());
            });
        }

        function speakTTS(text) {
            return new Promise(resolve => {
                const u = new SpeechSynthesisUtterance(text);
                u.lang = 'en-US'; u.rate = 0.85;
                u.onend = () => resolve(); u.onerror = () => resolve();
                window.speechSynthesis.speak(u);
            });
        }

        function updatePlayerUI(sheet) {
            const countEl = sheet.querySelector('.current-count');
            const pauseIcon = sheet.querySelector('.pause-btn i');
            const total = ttsState.sentences.length;
            const current = ttsState.currentSentenceIndex + 1;
            if (countEl) countEl.innerText = current + ' / ' + total;
            if (pauseIcon) pauseIcon.className = ttsState.isPaused ? 'fa-solid fa-play' : 'fa-solid fa-pause';
        }

        function togglePause() {
            ttsState.isPaused = !ttsState.isPaused;
            if (ttsState.currentAudio) {
                ttsState.isPaused ? ttsState.currentAudio.pause() : ttsState.currentAudio.play();
            } else {
                ttsState.isPaused ? window.speechSynthesis.pause() : window.speechSynthesis.resume();
            }
            updatePlayerUI(ttsState.currentSheet);
        }

        function restartTTS() {
            ttsState.currentSentenceIndex = 0;
            if (ttsState.currentAudio) { ttsState.currentAudio.pause(); ttsState.currentAudio = null; }
            window.speechSynthesis.cancel();
            updatePlayerUI(ttsState.currentSheet);
        }

        function stopTTS() {
            ttsState.isPlaying = false;
            if (ttsState.currentAudio) { ttsState.currentAudio.pause(); ttsState.currentAudio = null; }
            window.speechSynthesis.cancel();
            if (ttsState.currentSheet) {
                ttsState.currentSheet.querySelector('.tts-player-bar').classList.remove('active');
                ttsState.currentSheet.querySelector('.btn-listen-repeat').style.display = 'flex';
                ttsState.sentences.forEach(c => c.classList.remove('reading'));
            }
        }
    </script>
</head>
<body>

<!-- ── 상단 컨트롤 ── -->
<div class="top-controls no-print">
    <div class="day-nav">
        <a href="?day=<?= $prev_day ?>" class="<?= $prev_day ? '' : 'disabled' ?>">
            <i class="fa-solid fa-chevron-left"></i> Day <?= $prev_day ?? '-' ?>
        </a>
        <span>Day <?= $day_num ?></span>
        <a href="?day=<?= $next_day ?>" class="<?= $next_day ? '' : 'disabled' ?>">
            Day <?= $next_day ?? '-' ?> <i class="fa-solid fa-chevron-right"></i>
        </a>
    </div>
    <button onclick="openPdfModal()" class="btn-pdf-download">
        <i class="fa-solid fa-file-pdf"></i> PDF 저장하기
    </button>
</div>

<!-- ── PDF 모달 ── -->
<div id="pdfModal" class="modal-overlay no-print">
    <div class="modal-content">
        <h2><i class="fa-solid fa-print"></i> PDF 저장</h2>
        <p style="margin-bottom: 16px; font-size: 0.9rem; color: #6E767B;">Ctrl+P → PDF로 저장 → '배경 그래픽' 체크</p>
        <label class="print-option-label">
            <input type="radio" name="printOption" value="all" checked>
            전체 페이지 (<?= count($verbs) + 1 ?>페이지)
        </label>
        <div class="modal-actions">
            <button class="btn-modal btn-cancel" onclick="closePdfModal()">취소</button>
            <button class="btn-modal btn-confirm" onclick="printCurrentDay()">저장하기</button>
        </div>
    </div>
</div>

<!-- ===================================================
     Page 1~<?= count($verbs) ?> : 동사별 청킹 이미지 그리드
     =================================================== -->
<?php foreach ($verbs as $vi => $verb): ?>
<div class="sheet <?= $vi > 0 ? 'page-break' : '' ?>">
    <div class="bg-deco"></div>
    <div class="z-content">
        <header class="main-header">
            <div class="header-left"><div class="day-badge">Day <?= $day_num ?></div></div>
            <div class="header-center">
                <h1><span class="font-red drop-shadow">청킹</span>으로 쉽게 영어말하기</h1>
                <p class="sub-header-text">(<span class="font-red">Chunking</span>-Based Easy Speaking &mdash; <?= htmlspecialchars($verb['verb_en']) ?> · <?= htmlspecialchars($verb['verb_kr']) ?>)</p>
            </div>
            <div class="header-right">
                <div class="app-mode-btn active">
                    <img src="./img/wct01_n.png" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                    <span>청킹기본</span>
                </div>
                <div class="app-mode-btn">
                    <img src="./img/wct02.png" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    <span>청킹변화</span>
                </div>
            </div>
        </header>

        <section class="chunk-grid">
            <?php foreach ($verb['expressions'] as $ei => $expr):
                $img = book_asset_url($expr['image_path']);
                $is_main = ($ei === 0);
            ?>
            <div class="chunk-card <?= $is_main ? 'main-point' : '' ?>">
                <div class="img-container">
                    <?php if ($img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($expr['expression_en']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="no-img">📷</div>
                    <?php endif; ?>
                </div>
                <div class="note-area <?= $is_main ? 'dark' : 'light' ?>">
                    <div class="note-line"></div>
                    <div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3><?= htmlspecialchars($expr['expression_en']) ?></h3>
                        <span><?= htmlspecialchars($expr['expression_kr'] ?? '뜻 적기') ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </section>

        <footer class="page-footer">
            <span>© Chunking English Kids&amp;Mom</span>
            <span>Page 0<?= $vi + 1 ?> / Day <?= $day_num ?></span>
        </footer>
    </div>
</div>
<?php endforeach; ?>

<!-- ===================================================
     Page <?= count($verbs) + 1 ?> : Listen & Repeat 매직 카드
     =================================================== -->
<div class="sheet page-break">
    <div class="bg-deco"></div>
    <div class="z-content">
        <header class="main-header">
            <div class="header-left"><div class="day-badge">Day <?= $day_num ?></div></div>
            <div class="header-center"></div>
            <div class="header-right">
                <div class="app-mode-btn">
                    <img src="./img/wct01_n.png" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                    <span>청킹기본</span>
                </div>
                <div class="app-mode-btn active">
                    <img src="./img/wct02.png" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    <span>청킹변화</span>
                </div>
                <button class="btn-listen-repeat" onclick="startTTSSequence(this)">
                    <i class="fa-solid fa-volume-high"></i>
                    <span>Listen &amp; Repeat!</span>
                </button>
                <div class="tts-player-bar">
                    <button class="player-ctrl-btn pause-btn" onclick="togglePause()"><i class="fa-solid fa-pause"></i></button>
                    <button class="player-ctrl-btn" onclick="restartTTS()"><i class="fa-solid fa-arrow-rotate-left"></i></button>
                    <div class="progress-text"><span class="current-count">1 / 1</span></div>
                    <button class="player-ctrl-btn" style="background:#f8d7da; color:#dc3545;" onclick="stopTTS()"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </header>

        <section class="magic-card-list">
            <?php
            $card_num = 1;
            foreach ($verbs as $verb):
            ?>
            <div class="verb-divider"><?= htmlspecialchars($verb['verb_en']) ?> · <?= htmlspecialchars($verb['verb_kr']) ?></div>
            <?php foreach ($verb['expressions'] as $ei => $expr):
                $aurl = book_asset_url($expr['audio_path'] ?? null);
            ?>
            <div class="magic-card" <?= $aurl ? 'data-audio-url="'.htmlspecialchars($aurl).'"' : '' ?>>
                <div class="magic-number-tag"><?= $card_num++ ?></div>
                <div class="magic-content">
                    <div class="magic-text-box">
                        <div class="eng-sentence">I <?= htmlspecialchars($expr['expression_en']) ?>.</div>
                        <div class="kor-sentence">나는 <?= htmlspecialchars($expr['expression_kr'] ?? '') ?>.</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </section>

        <footer class="page-footer">
            <span>© Chunking English Kids&amp;Mom</span>
            <span>Page 0<?= count($verbs) + 1 ?> / Day <?= $day_num ?></span>
        </footer>
    </div>
</div>

</body>
</html>
