<?php
require_once 'config/db.php';

$day_num = max(1, min(50, intval($_GET['day'] ?? 1)));

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

// 이미지 경로 → 웹 URL 변환
function img_url($path) {
    if (!$path) return null;
    $parts = explode('/', str_replace('\\', '/', $path));
    return '/03_chunking/' . implode('/', array_map('rawurlencode', $parts));
}

$prev_day = $day_num > 1  ? $day_num - 1 : null;
$next_day = $day_num < 50 ? $day_num + 1 : null;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Chunking English - Day <?= $day_num ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Jua&family=Quicksand:wght@500;600;700&family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand-bg: #FFF8FA;
            --brand-white: #FFFFFF;
            --primary: #FF7E96;
            --primary-light: #FFEFF2;
            --text-main: #2A2F32;
            --text-sub: #6E767B;
            --line-gray: #F0E4E7;
            --red-point: #FA4252;
            --listen-pink: #FF5A82;
            --shadow-soft: 0 8px 24px -6px rgba(255,126,150,0.12);
            --shadow-float: 0 12px 32px -8px rgba(255,126,150,0.2);
            --shadow-inner: inset 0 2px 6px rgba(0,0,0,0.02);
        }
        * { margin:0; padding:0; box-sizing:border-box; }

        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; background:white; }
            .page-break { break-before: always; }
            .no-print { display: none !important; }
            .sheet { box-shadow:none !important; margin:0 !important; }
        }

        body {
            background:#4A4E53;
            font-family:'Quicksand','Noto Sans KR',sans-serif;
            display:flex; flex-direction:column; align-items:center;
            padding:40px 0; color:var(--text-main);
        }

        /* ── 상단 네비게이션 ── */
        .day-nav {
            display:flex; align-items:center; gap:16px;
            margin-bottom:20px; font-family:'Jua',sans-serif;
        }
        .day-nav a {
            background:#FF7E96; color:white; padding:8px 20px;
            border-radius:20px; text-decoration:none; font-size:1rem;
            transition:opacity 0.2s;
        }
        .day-nav a:hover { opacity:0.85; }
        .day-nav a.disabled { background:#888; pointer-events:none; }
        .day-nav span { color:white; font-size:1.2rem; }

        /* ── 시트 공통 ── */
        .sheet {
            width:210mm; background:var(--brand-bg); position:relative;
            box-shadow:0 20px 50px rgba(0,0,0,0.3);
            display:flex; flex-direction:column;
            margin-bottom:40px; padding:12mm 14mm 14mm;
            border-radius:4px;
        }
        .bg-deco {
            position:absolute; top:0; left:0; right:0; bottom:0;
            background-image:radial-gradient(var(--line-gray) 1.5px, transparent 1.5px);
            background-size:28px 28px; z-index:0; opacity:0.7; pointer-events:none;
        }
        .z-content { position:relative; z-index:10; display:flex; flex-direction:column; }

        /* ── 헤더 ── */
        .main-header {
            display:flex; justify-content:space-between; align-items:center;
            margin-bottom:1.5rem; padding-bottom:0.8rem;
            border-bottom:2px solid rgba(240,228,231,0.8);
            min-height:70px; gap:10px;
        }
        .header-left { display:flex; align-items:center; }
        .day-badge {
            background:linear-gradient(135deg,#FF94A4 0%,#FFB6A0 100%);
            color:white; padding:6px 20px; border-radius:14px;
            font-family:'Chewy',cursive; font-size:1.35rem;
            display:inline-flex; align-items:center;
            box-shadow:0 4px 10px rgba(255,148,164,0.3);
        }
        .header-center { flex:1; text-align:center; white-space:nowrap; }
        .header-center h1 { font-family:'Jua',sans-serif; font-size:1.55rem; margin-bottom:2px; }
        .sub-header-text { font-size:0.8rem; font-weight:bold; color:var(--text-sub); opacity:0.8; }
        .header-right { display:flex; justify-content:flex-end; gap:8px; align-items:center; }
        .app-mode-btn {
            background:white; border:2px solid var(--line-gray); border-radius:12px;
            padding:6px 12px; display:flex; align-items:center; gap:6px;
            font-family:'Jua',sans-serif; font-size:0.85rem; color:var(--text-sub);
            transition:all 0.2s; cursor:pointer;
        }
        .app-mode-btn.active { border-color:var(--primary); background:var(--primary-light); color:var(--primary); }
        .app-mode-btn img { width:22px; height:22px; object-fit:contain; }

        /* ── TTS ── */
        .btn-listen-repeat {
            background:linear-gradient(270deg,#fff0f5,#ffffff,#ffeef2,#fff0f5);
            background-size:400% 400%; border:2px solid #ffc2d1; border-radius:14px;
            padding:6px 14px; font-family:'Jua',sans-serif; font-size:0.9rem;
            color:var(--listen-pink); box-shadow:0 4px 0 #ff9db5;
            display:flex; align-items:center; gap:8px; cursor:pointer;
            width:220px; justify-content:center;
        }
        .btn-listen-repeat:active { transform:translateY(3px); box-shadow:none; }
        .tts-player-bar {
            display:none; background:white; border:2px solid #ffdae3; border-radius:14px;
            padding:5px 12px; align-items:center; gap:6px;
            box-shadow:var(--shadow-float); width:220px; justify-content:space-between;
        }
        .tts-player-bar.active { display:flex; }
        .player-ctrl-btn {
            width:26px; height:26px; border-radius:50%; border:none;
            background:var(--primary-light); color:var(--listen-pink); cursor:pointer;
            display:flex; align-items:center; justify-content:center; font-size:0.75rem;
            transition:all 0.2s; flex-shrink:0;
        }
        .player-ctrl-btn:hover { background:var(--listen-pink); color:white; }
        .progress-container { flex:1; display:flex; flex-direction:column; gap:3px; min-width:60px; }
        .progress-text { font-family:'Jua',sans-serif; font-size:0.6rem; color:var(--text-sub); text-align:center; }
        .progress-track { width:100%; height:4px; background:#F0E4E7; border-radius:10px; overflow:hidden; }
        .progress-fill { width:0%; height:100%; background:var(--primary); transition:width 0.3s; }

        /* ── Page1: 청킹 이미지 그리드 ── */
        .verb-section { margin-bottom:18px; }
        .verb-label {
            font-family:'Chewy',cursive; font-size:1.1rem; color:var(--primary);
            margin-bottom:8px; padding:2px 12px;
            background:var(--primary-light); border-radius:8px; display:inline-block;
        }
        .chunk-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:8px; }
        .chunk-card {
            background:var(--brand-white); padding:8px; border-radius:16px;
            border:1px solid white; display:flex; flex-direction:column;
            box-shadow:var(--shadow-soft); transition:transform 0.3s ease;
        }
        .chunk-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-float); }
        .chunk-card.main-point { border:2px solid rgba(255,126,150,0.4); }
        .img-container {
            width:100%; aspect-ratio:1/1; background:var(--brand-bg);
            border-radius:10px; margin-bottom:6px; overflow:hidden;
        }
        .img-container img { width:100%; height:100%; object-fit:cover; }
        .img-container .no-img {
            width:100%; height:100%; display:flex; align-items:center; justify-content:center;
            font-size:1.5rem; color:#ddd;
        }
        .note-area {
            position:relative; flex-grow:1; display:flex; flex-direction:column;
            justify-content:center; align-items:center; border-radius:10px;
            border:1px solid rgba(255,126,150,0.15); padding:6px 0; overflow:hidden;
        }
        .note-area.dark { background:var(--primary-light); }
        .note-area.light { background:#FCFCFD; }
        .note-line {
            position:absolute; inset:0;
            background-image:repeating-linear-gradient(transparent,transparent 15px,rgba(255,126,150,0.2) 16px);
        }
        .note-text-wrap { position:relative; z-index:10; text-align:center; padding:2px 4px; }
        .note-text-wrap h3 { font-size:0.7rem; color:var(--text-main); font-family:'Chewy',cursive; }
        .note-text-wrap span { font-size:0.6rem; color:var(--text-sub); font-family:'Jua',sans-serif; }

        /* ── Page2~4: 매직 카드 ── */
        .magic-card-list { display:flex; flex-direction:column; flex-grow:1; gap:10px; padding-bottom:5px; }
        .magic-card {
            flex:1; background:white; border:1.5px solid #FFEDF1; border-radius:22px;
            padding:0 24px; display:flex; align-items:center; gap:20px;
            box-shadow:var(--shadow-soft); transition:all 0.3s;
        }
        .magic-card.reading { border-color:var(--primary); box-shadow:var(--shadow-float); transform:scale(1.01); z-index:10; }
        .magic-number-tag {
            background:linear-gradient(135deg,#FF7E96 0%,#FFA8B9 100%); color:white;
            border-radius:50%; width:40px; height:40px; font-family:'Chewy',cursive; font-size:1.4rem;
            display:flex; align-items:center; justify-content:center;
            border:3px solid white; box-shadow:0 4px 8px rgba(255,126,150,0.35); flex-shrink:0;
        }
        .magic-content { flex:1; min-width:0; display:flex; flex-direction:column; justify-content:center; }
        .magic-text-box { padding:10px 16px; border-radius:14px; background:#FAFAFA; border:1px solid #F2E8EB; }
        .eng-sentence { font-family:'Chewy',cursive; font-size:1.8rem; color:var(--text-main); line-height:1.15; margin-bottom:4px; }
        .kor-sentence { font-family:'Noto Sans KR',sans-serif; font-size:0.95rem; color:var(--text-sub); font-weight:500; }
        .verb-tag {
            display:inline-block; background:var(--primary-light); color:var(--primary);
            font-family:'Jua',sans-serif; font-size:0.75rem; padding:2px 10px;
            border-radius:8px; margin-bottom:6px; border:1px solid rgba(255,126,150,0.3);
        }

        /* ── 푸터 ── */
        .page-footer {
            margin-top:12px; padding-top:10px; border-top:2px solid var(--line-gray);
            display:flex; justify-content:space-between; font-size:11px; color:#A0AAB2;
            text-transform:uppercase; font-family:'Quicksand',sans-serif; font-weight:600;
        }
        .font-red { color:var(--red-point); }
        .drop-shadow { filter:drop-shadow(0 2px 4px rgba(250,66,82,0.2)); }
    </style>

    <script>
    let ttsState = {
        isPlaying:false, isPaused:false, currentSheet:null,
        sentences:[], currentSentenceIndex:0, repeatCount:0, maxRepeat:7
    };
    const wait = ms => new Promise(r => setTimeout(r, ms));

    async function startTTSSequence(btn) {
        const sheet = btn.closest('.sheet');
        window.speechSynthesis.cancel();
        ttsState = { isPlaying:true, isPaused:false, currentSheet:sheet,
            sentences:Array.from(sheet.querySelectorAll('.magic-card')),
            currentSentenceIndex:0, repeatCount:0, maxRepeat:7 };
        btn.style.display = 'none';
        sheet.querySelector('.tts-player-bar').classList.add('active');
        updatePlayerUI(sheet);
        processQueue();
    }
    async function processQueue() {
        while (ttsState.isPlaying && ttsState.currentSentenceIndex < ttsState.sentences.length) {
            const card = ttsState.sentences[ttsState.currentSentenceIndex];
            const text = card.querySelector('.eng-sentence').innerText;
            ttsState.sentences.forEach(c => c.classList.remove('reading'));
            card.classList.add('reading');
            while (ttsState.repeatCount < ttsState.maxRepeat) {
                if (!ttsState.isPlaying) return;
                if (ttsState.isPaused) { await wait(200); continue; }
                updatePlayerUI(ttsState.currentSheet);
                await speakText(text);
                ttsState.repeatCount++;
                await wait(800);
            }
            ttsState.repeatCount = 0;
            ttsState.currentSentenceIndex++;
        }
        stopTTS();
    }
    function speakText(text) {
        return new Promise(resolve => {
            const u = new SpeechSynthesisUtterance(text);
            u.lang = 'en-US'; u.rate = 0.85;
            u.onend = () => resolve(); u.onerror = () => resolve();
            window.speechSynthesis.speak(u);
        });
    }
    function updatePlayerUI(sheet) {
        const countEl = sheet.querySelector('.current-count');
        const fillEl  = sheet.querySelector('.progress-fill');
        const pauseIcon = sheet.querySelector('.pause-btn i');
        if (countEl) countEl.innerText = ttsState.repeatCount + 1;
        if (fillEl)  fillEl.style.width = ((ttsState.repeatCount + 1) / ttsState.maxRepeat * 100) + '%';
        if (pauseIcon) pauseIcon.className = ttsState.isPaused ? 'fa-solid fa-play' : 'fa-solid fa-pause';
    }
    function togglePause() {
        ttsState.isPaused = !ttsState.isPaused;
        ttsState.isPaused ? window.speechSynthesis.pause() : window.speechSynthesis.resume();
        updatePlayerUI(ttsState.currentSheet);
    }
    function restartTTS() {
        ttsState.repeatCount = 0; ttsState.currentSentenceIndex = 0;
        window.speechSynthesis.cancel(); updatePlayerUI(ttsState.currentSheet);
    }
    function stopTTS() {
        ttsState.isPlaying = false;
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

<!-- ── Day 네비게이션 ── -->
<div class="day-nav no-print">
    <a href="?day=<?= $prev_day ?>" class="<?= $prev_day ? '' : 'disabled' ?>">
        <i class="fa-solid fa-chevron-left"></i> Day <?= $prev_day ?? '-' ?>
    </a>
    <span>Day <?= $day_num ?></span>
    <a href="?day=<?= $next_day ?>" class="<?= $next_day ? '' : 'disabled' ?>">
        Day <?= $next_day ?? '-' ?> <i class="fa-solid fa-chevron-right"></i>
    </a>
</div>

<div class="no-print" style="color:white;opacity:0.8;margin-bottom:20px;text-align:center;font-family:'Jua';">
    <p>✨ <strong>PDF 저장:</strong> Ctrl+P → 'PDF로 저장' → '배경 그래픽' 체크</p>
</div>

<!-- ===================================================
     Page 1 : 청킹 이미지 그리드 (3동사 × 7표현 = 21장)
     =================================================== -->
<div class="sheet">
    <div class="bg-deco"></div>
    <div class="z-content">
        <header class="main-header">
            <div class="header-left"><div class="day-badge">Day <?= $day_num ?></div></div>
            <div class="header-center">
                <h1><span class="font-red drop-shadow">청킹</span>으로 쉽게 영어말하기</h1>
                <p class="sub-header-text">(<span class="font-red">Chunking</span>-Based Easy Speaking)</p>
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

        <?php foreach ($verbs as $vi => $verb): ?>
        <div class="verb-section">
            <div class="verb-label"><?= htmlspecialchars($verb['verb_en']) ?> · <?= htmlspecialchars($verb['verb_kr']) ?></div>
            <div class="chunk-grid">
                <?php foreach ($verb['expressions'] as $ei => $expr):
                    $img = img_url($expr['image_path']);
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
                        <div class="note-text-wrap">
                            <h3><?= htmlspecialchars($expr['expression_en']) ?></h3>
                            <span><?= htmlspecialchars($expr['expression_kr'] ?? '뜻 적기') ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <footer class="page-footer">
            <span>© Wizard Chunking</span>
            <span>Page 01 / Day <?= $day_num ?></span>
        </footer>
    </div>
</div>

<!-- ===================================================
     Page 2~4 : 동사별 매직 카드 + TTS
     =================================================== -->
<?php foreach ($verbs as $vi => $verb):
    $page_num = $vi + 2;
?>
<div class="sheet page-break">
    <div class="bg-deco"></div>
    <div class="z-content">
        <header class="main-header">
            <div class="header-left"><div class="day-badge">Day <?= $day_num ?></div></div>
            <div class="header-center">
                <h1 style="font-family:'Chewy',cursive; font-size:1.8rem; color:var(--primary);">
                    <?= htmlspecialchars($verb['verb_en']) ?>
                    <span style="font-family:'Jua',sans-serif; font-size:1rem; color:var(--text-sub);">
                        · <?= htmlspecialchars($verb['verb_kr']) ?>
                    </span>
                </h1>
            </div>
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
                    <i class="fa-solid fa-circle-play"></i>
                    <span>Listen &amp; Repeat!</span>
                </button>
                <div class="tts-player-bar">
                    <button class="player-ctrl-btn pause-btn" onclick="togglePause()"><i class="fa-solid fa-pause"></i></button>
                    <button class="player-ctrl-btn" onclick="restartTTS()"><i class="fa-solid fa-arrow-rotate-left"></i></button>
                    <div class="progress-container">
                        <div class="progress-text"><span class="current-count">1</span> / 7</div>
                        <div class="progress-track"><div class="progress-fill"></div></div>
                    </div>
                    <button class="player-ctrl-btn" style="background:#f8d7da;color:#dc3545;" onclick="stopTTS()"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </header>

        <section class="magic-card-list">
            <?php foreach ($verb['expressions'] as $ei => $expr): ?>
            <div class="magic-card">
                <div class="magic-number-tag"><?= $ei + 1 ?></div>
                <div class="magic-content">
                    <div class="verb-tag"><?= htmlspecialchars($verb['verb_en']) ?></div>
                    <div class="magic-text-box">
                        <div class="eng-sentence">I <?= htmlspecialchars($expr['expression_en']) ?>.</div>
                        <div class="kor-sentence">나는 <?= htmlspecialchars($expr['expression_kr'] ?? '') ?>.</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </section>

        <footer class="page-footer">
            <span>© Wizard Chunking Together</span>
            <span>Page 0<?= $page_num ?> / Day <?= $day_num ?></span>
        </footer>
    </div>
</div>
<?php endforeach; ?>

</body>
</html>
