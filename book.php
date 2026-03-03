<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Chunking English E-Book</title>
    <!-- 폰트 및 아이콘 라이브러리 -->
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Jua&family=Quicksand:wght@500;600;700&family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ---------------------------------
           [CSS 변수 정의]
        --------------------------------- */
        :root {
            --brand-bg: #FFF8FA; /* 살짝 더 따뜻한 크림 핑크 톤 */
            --brand-white: #FFFFFF;
            --primary: #FF7E96; /* 채도를 살짝 높여 더 생기있게 */
            --primary-light: #FFEFF2;
            --secondary: #7CC29D;
            --accent: #FFCE54;
            --text-main: #2A2F32; /* 더 세련된 차콜 그레이 */
            --text-sub: #6E767B;
            --line-gray: #F0E4E7;
            --red-point: #FA4252;
            --listen-pink: #FF5A82;
            --shadow-soft: 0 8px 24px -6px rgba(255, 126, 150, 0.12);
            --shadow-float: 0 12px 32px -8px rgba(255, 126, 150, 0.2);
            --shadow-inner: inset 0 2px 6px rgba(0, 0, 0, 0.02);
        }

        /* ---------------------------------
           [기본 레이아웃 및 초기화]
        --------------------------------- */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background-color: white; }
            .page-break { break-before: always; }
            .no-print { display: none; }
            .sheet { box-shadow: none !important; margin: 0 !important; }
        }

        body {
            background-color: #4A4E53;
            font-family: 'Quicksand', 'Noto Sans KR', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 0;
            color: var(--text-main);
        }

        .sheet {
            width: 210mm;
            height: 297mm;
            background-color: var(--brand-bg);
            position: relative;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin-bottom: 40px;
            padding: 12mm 14mm 14mm 14mm;
            border-radius: 4px; /* 화면 미리보기용 곡률 */
        }

        .bg-deco {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(var(--line-gray) 1.5px, transparent 1.5px);
            background-size: 28px 28px; z-index: 0; opacity: 0.7; pointer-events: none;
        }

        .z-content { position: relative; z-index: 10; height: 100%; display: flex; flex-direction: column; }

        /* ---------------------------------
           [상단 헤더 스타일]
        --------------------------------- */
        .main-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.5rem; padding-bottom: 0.8rem;
            border-bottom: 2px solid rgba(240, 228, 231, 0.8);
            min-height: 70px;
            gap: 10px; /* 헤더 요소 간 간격 */
        }

        .header-left { display: flex; align-items: center; }
        .day-badge {
            background: linear-gradient(135deg, #FF94A4 0%, #FFB6A0 100%);
            color: white; padding: 6px 20px; border-radius: 14px;
            font-family: 'Chewy', cursive; font-size: 1.35rem; display: inline-flex; align-items: center;
            box-shadow: 0 4px 10px rgba(255, 148, 164, 0.3); letter-spacing: 0.5px;
        }

        /* 제목이 한 줄에 다 들어오도록 설정 */
        .header-center {
            flex: 1; text-align: center;
            white-space: nowrap; /* 줄바꿈 방지 */
            overflow: visible;
        }
        .header-center h1 { font-family: 'Jua', sans-serif; font-size: 1.55rem; margin-bottom: 2px; }
        .sub-header-text { font-size: 0.8rem; font-weight: bold; color: var(--text-sub); opacity: 0.8; letter-spacing: 0.3px; }

        .header-right { display: flex; justify-content: flex-end; gap: 8px; align-items: center; }

        /* 앱 스타일 버튼 - 공간 효율화 */
        .app-mode-btn {
            background: white; border: 2px solid var(--line-gray); border-radius: 12px;
            padding: 6px 12px; display: flex; align-items: center; gap: 6px;
            font-family: 'Jua', sans-serif; font-size: 0.85rem; color: var(--text-sub);
            transition: all 0.2s; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .app-mode-btn.active { border-color: var(--primary); background: var(--primary-light); color: var(--primary); box-shadow: 0 4px 10px rgba(255, 126, 150, 0.15); }
        .app-mode-btn img { width: 22px; height: 22px; object-fit: contain; }

        /* Listen & Repeat 버튼 */
        .btn-listen-repeat {
            background: linear-gradient(270deg, #fff0f5, #ffffff, #ffeef2, #fff0f5);
            background-size: 400% 400%; border: 2px solid #ffc2d1; border-radius: 14px;
            padding: 6px 14px; font-family: 'Jua', sans-serif; font-size: 0.9rem;
            color: var(--listen-pink); box-shadow: 0 4px 0 #ff9db5;
            display: flex; align-items: center; gap: 8px; cursor: pointer;
            width: 220px; /* 클릭 시 너비 변화를 막기 위해 고정 너비 설정 */
            justify-content: center;
        }
        .btn-listen-repeat:active { transform: translateY(3px); box-shadow: none; }
        .btn-listen-repeat i { font-size: 1.1rem; }

        /* TTS 플레이어 바 */
        .tts-player-bar {
            display: none;
            background: white; border: 2px solid #ffdae3; border-radius: 14px;
            padding: 5px 12px; align-items: center; gap: 6px;
            box-shadow: var(--shadow-float);
            width: 220px; /* 버튼과 동일한 너비로 고정하여 밀림 방지 */
            justify-content: space-between;
        }
        .tts-player-bar.active { display: flex; }

        .player-ctrl-btn {
            width: 26px; height: 26px; border-radius: 50%; border: none;
            background: var(--primary-light); color: var(--listen-pink); cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 0.75rem;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .player-ctrl-btn:hover { background: var(--listen-pink); color: white; }

        .progress-container { flex: 1; display: flex; flex-direction: column; gap: 3px; min-width: 60px; }
        .progress-text { font-family: 'Jua', sans-serif; font-size: 0.6rem; color: var(--text-sub); text-align: center; }
        .progress-track { width: 100%; height: 4px; background: #F0E4E7; border-radius: 10px; overflow: hidden; }
        .progress-fill { width: 0%; height: 100%; background: var(--primary); transition: width 0.3s; }

        /* ---------------------------------
           [공통 타이포그래피]
        --------------------------------- */
        .font-red { color: var(--red-point); }
        .drop-shadow { filter: drop-shadow(0 2px 4px rgba(250, 66, 82, 0.2)); }

        /* ---------------------------------
           [페이지 1: 청킹 그리드 (디자인 향상)]
        --------------------------------- */
        .chunk-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; flex-grow: 1; }

        .chunk-card {
            background: var(--brand-white); padding: 12px; border-radius: 24px;
            border: 1px solid white; display: flex; flex-direction: column;
            box-shadow: var(--shadow-soft); transition: transform 0.3s ease;
        }
        .chunk-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-float); }
        .chunk-card.main-point { border: 2px solid rgba(255, 126, 150, 0.4); box-shadow: var(--shadow-float); }

        .img-container {
            width: 100%; aspect-ratio: 1/1; background: var(--brand-bg);
            border-radius: 16px; margin-bottom: 12px; overflow: hidden;
            border: 1px solid rgba(0,0,0,0.03);
        }
        .img-container img { width: 100%; height: 100%; object-fit: cover; }

        .note-area {
            position: relative; flex-grow: 1; display: flex; flex-direction: column;
            justify-content: center; align-items: center; border-radius: 14px;
            border: 1px solid rgba(255, 126, 150, 0.15); padding: 12px 0; overflow: hidden;
            box-shadow: var(--shadow-inner);
        }
        .note-area.dark { background: var(--primary-light); }
        .note-area.light { background: #FCFCFD; }

        .note-line {
            position: absolute; inset: 0;
            background-image: repeating-linear-gradient(transparent, transparent 23px, rgba(255, 126, 150, 0.2) 24px);
        }
        .note-margin { position: absolute; left: 16px; top: 0; bottom: 0; width: 1.5px; background: rgba(250, 66, 82, 0.25); }

        .note-text-wrap { position: relative; z-index: 10; text-align: center; background: rgba(255,255,255,0.6); padding: 2px 10px; border-radius: 8px; backdrop-filter: blur(2px); }
        .note-text-wrap h3 { font-size: 1.45rem; color: var(--text-main); font-family: 'Chewy', cursive; margin-bottom: 2px; letter-spacing: 0.5px; }
        .note-text-wrap span { font-size: 0.9rem; color: var(--text-sub); font-family: 'Jua', sans-serif; }

        /* ---------------------------------
           [페이지 2&3: 매직 카드 리스트 (레이아웃 최적화)]
        --------------------------------- */
        .magic-card-list { display: flex; flex-direction: column; flex-grow: 1; gap: 14px; padding-bottom: 5px; }

        /* height 고정 대신 flex:1을 주어 5개가 알아서 남은 공간을 균등하게 채우도록 함 */
        .magic-card {
            flex: 1; /* 핵심: 공간 균등 분할 */
            background: white; border: 1.5px solid #FFEDF1; border-radius: 22px;
            padding: 0 24px; display: flex; align-items: center; gap: 20px;
            box-shadow: var(--shadow-soft); position: relative;
            transition: all 0.3s;
        }
        .magic-card.reading { border-color: var(--primary); box-shadow: var(--shadow-float); transform: scale(1.01); z-index: 10; }

        .magic-number-tag {
            background: linear-gradient(135deg, #FF7E96 0%, #FFA8B9 100%); color: white; border-radius: 50%;
            width: 40px; height: 40px; font-family: 'Chewy', cursive; font-size: 1.4rem;
            display: flex; align-items: center; justify-content: center; border: 3px solid white;
            box-shadow: 0 4px 8px rgba(255, 126, 150, 0.35); flex-shrink: 0;
        }

        .magic-content { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; }

        .grammar-visual-box {
            display: flex; align-items: center; gap: 6px; padding: 4px 12px; background: #FCF6F8;
            border: 1px dashed #FFC9D4; border-radius: 10px; width: fit-content; margin-bottom: 8px;
        }
        .wizard-icon { width: 26px; height: 26px; object-fit: contain; }
        .magic-connector-tag {
            background: white; border: 1.5px solid #FFC9D4; color: #E84A65;
            font-family: 'Jua', sans-serif; font-size: 0.85rem; padding: 2px 10px; border-radius: 8px;
            box-shadow: 0 2px 0 #FFC9D4; letter-spacing: 0.5px;
        }
        .text-plus { color: var(--primary); font-size: 0.7rem; font-weight: bold; }

        .magic-text-box { padding: 12px 18px; border-radius: 14px; background: #FAFAFA; border: 1px solid #F2E8EB; box-shadow: var(--shadow-inner); }
        .eng-sentence { font-family: 'Chewy', cursive; font-size: 1.95rem; color: var(--text-main); line-height: 1.15; margin-bottom: 4px; letter-spacing: 0.5px; }
        .kor-sentence { font-family: 'Noto Sans KR', sans-serif; font-size: 1.05rem; color: var(--text-sub); font-weight: 500; }

        /* ---------------------------------
           [푸터]
        --------------------------------- */
        .page-footer {
            margin-top: 15px; padding-top: 12px; border-top: 2px solid var(--line-gray);
            display: flex; justify-content: space-between; font-size: 11px; color: #A0AAB2;
            text-transform: uppercase; font-family: 'Quicksand', sans-serif; font-weight: 600; letter-spacing: 0.5px;
        }
    </style>

    <script>
        /** ---------------------------------
         * [TTS 엔진 및 상태 관리]
         * --------------------------------- */
        let ttsState = {
            isPlaying: false,
            isPaused: false,
            currentSheet: null,
            sentences: [],
            currentSentenceIndex: 0,
            repeatCount: 0,
            maxRepeat: 7
        };

        const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));

        async function startTTSSequence(btn) {
            const sheet = btn.closest('.sheet');

            window.speechSynthesis.cancel();
            ttsState.isPlaying = true;
            ttsState.isPaused = false;
            ttsState.currentSheet = sheet;
            ttsState.sentences = Array.from(sheet.querySelectorAll('.magic-card'));
            ttsState.currentSentenceIndex = 0;
            ttsState.repeatCount = 0;

            btn.style.display = 'none';
            const playerBar = sheet.querySelector('.tts-player-bar');
            playerBar.classList.add('active');
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
            return new Promise((resolve) => {
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'en-US';
                utterance.rate = 0.85;
                utterance.onend = () => resolve();
                utterance.onerror = () => resolve();
                window.speechSynthesis.speak(utterance);
            });
        }

        function updatePlayerUI(sheet) {
            const countEl = sheet.querySelector('.current-count');
            const fillEl = sheet.querySelector('.progress-fill');
            const pauseIcon = sheet.querySelector('.pause-btn i');

            if (countEl) countEl.innerText = ttsState.repeatCount + 1;
            if (fillEl) {
                const percent = ((ttsState.repeatCount + 1) / ttsState.maxRepeat) * 100;
                fillEl.style.width = percent + '%';
            }
            if (pauseIcon) {
                pauseIcon.className = ttsState.isPaused ? 'fa-solid fa-play' : 'fa-solid fa-pause';
            }
        }

        function togglePause() {
            ttsState.isPaused = !ttsState.isPaused;
            if (ttsState.isPaused) window.speechSynthesis.pause();
            else window.speechSynthesis.resume();
            updatePlayerUI(ttsState.currentSheet);
        }

        function restartTTS() {
            ttsState.repeatCount = 0;
            ttsState.currentSentenceIndex = 0;
            window.speechSynthesis.cancel();
            updatePlayerUI(ttsState.currentSheet);
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

<div class="no-print" style="color:white; opacity:0.8; margin-bottom:20px; text-align:center; font-family:'Jua';">
    <p>✨ <strong>PDF 저장 방법:</strong> Ctrl+P (인쇄) > 대상: 'PDF로 저장' > 설정: '배경 그래픽' 체크</p>
</div>

<!-- ================= Page 1 ================= -->
<div class="sheet">
    <div class="bg-deco"></div>
    <div class="z-content">
        <header class="main-header">
            <div class="header-left"><div class="day-badge">Day 1</div></div>
            <div class="header-center">
                <h1 class="font-kor"><span class="font-red drop-shadow">청킹</span>으로 쉽게 영어말하기</h1>
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

        <section class="chunk-grid">
            <div class="chunk-card main-point">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+1"></div>
                <div class="note-area dark">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 01</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <script>
                for(let i=2; i<=9; i++) {
                    document.write(`
                    <div class="chunk-card">
                        <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+${i}"></div>
                        <div class="note-area light">
                            <div class="note-line"></div><div class="note-margin"></div>
                            <div class="note-text-wrap">
                                <h3>CHUNK 0${i}</h3>
                                <span>뜻 적기</span>
                            </div>
                        </div>
                    </div>`);
                }
            </script>
        </section>

        <footer class="page-footer"><span>© Wizard Chunking</span><span>Page 01</span></footer>
    </div>
</div>

<!-- ================= Page 2 ================= -->
<div class="sheet page-break">
    <div class="bg-deco"></div>
    <div class="z-content">
        <header class="main-header">
            <div class="header-left"><div class="day-badge">Day 1</div></div>
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
                    <i class="fa-solid fa-circle-play"></i>
                    <span>Listen & Repeat!</span>
                </button>
                <div class="tts-player-bar">
                    <button class="player-ctrl-btn pause-btn" onclick="togglePause()"><i class="fa-solid fa-pause"></i></button>
                    <button class="player-ctrl-btn" onclick="restartTTS()"><i class="fa-solid fa-arrow-rotate-left"></i></button>
                    <div class="progress-container">
                        <div class="progress-text"><span class="current-count">1</span> / 7</div>
                        <div class="progress-track"><div class="progress-fill"></div></div>
                    </div>
                    <button class="player-ctrl-btn" style="background:#f8d7da; color:#dc3545;" onclick="stopTTS()"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </header>

        <section class="magic-card-list">
            <script>
                const p2Data = [
                    { n: 1, eng: "I have a dream to change my life.", kor: "나는 내 삶을 바꿀 꿈을 가지고 있어요.", conn: "to(부정사)" },
                    { n: 2, eng: "Having a dream changes my future.", kor: "꿈을 갖는 것은 나의 미래를 바꿔요.", conn: "ing(동명사)" },
                    { n: 3, eng: "I start my trip with a big dream.", kor: "나는 큰 꿈을 가지고 여행을 시작해요.", conn: "전치사" },
                    { n: 4, eng: "If I have a goal, I will study hard.", kor: "내가 목표가 있다면, 열심히 공부할 거야.", conn: "부사절" },
                    { n: 5, eng: "She has a plan for the weekend.", kor: "그녀는 주말을 위한 계획이 있어요.", conn: "전치사" }
                ];
                p2Data.forEach(s => {
                    document.write(`
                    <div class="magic-card">
                        <div class="magic-number-tag">${s.n}</div>
                        <div class="magic-content">
                            ${s.conn ? `<div class="grammar-visual-box">
                                <img src="./img/wct01_n.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                                <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                                <span class="magic-connector-tag">${s.conn}</span>
                            </div>` : ''}
                            <div class="magic-text-box">
                                <div class="eng-sentence">${s.eng}</div>
                                <div class="kor-sentence">${s.kor}</div>
                            </div>
                        </div>
                    </div>`);
                });
            </script>
        </section>
        <footer class="page-footer"><span>© Wizard Chunking Together</span><span>Page 02</span></footer>
    </div>
</div>

<!-- ================= Page 3 ================= -->
<div class="sheet page-break">
    <div class="bg-deco"></div>
    <div class="z-content">
        <header class="main-header">
            <div class="header-left"><div class="day-badge">Day 1</div></div>
            <div class="header-center"></div>
            <div class="header-right">
                <div class="app-mode-btn">
                    <img src="./img/wct01_n.png" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                    <span>청킹기본</span>
                </div>
                <div class="app-mode-btn">
                    <img src="./img/wct02.png" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    <span>청킹변화</span>
                </div>
                <button class="btn-listen-repeat" onclick="startTTSSequence(this)">
                    <i class="fa-solid fa-circle-play"></i>
                    <span>Listen & Repeat!</span>
                </button>
                <div class="tts-player-bar">
                    <button class="player-ctrl-btn pause-btn" onclick="togglePause()"><i class="fa-solid fa-pause"></i></button>
                    <button class="player-ctrl-btn" onclick="restartTTS()"><i class="fa-solid fa-arrow-rotate-left"></i></button>
                    <div class="progress-container">
                        <div class="progress-text"><span class="current-count">1</span> / 7</div>
                        <div class="progress-track"><div class="progress-fill"></div></div>
                    </div>
                    <button class="player-ctrl-btn" style="background:#f8d7da; color:#dc3545;" onclick="stopTTS()"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </header>

        <section class="magic-card-list">
            <script>
                const p3Data = [
                    { n: 6, eng: "I like to have a chance to meet you.", kor: "당신을 만날 기회를 가져서 좋아요.", conn: "to(부정사)" },
                    { n: 7, eng: "Having a wish makes me happy.", kor: "소망을 가지는 것은 나를 행복하게 해요.", conn: "ing(동명사)" },
                    { n: 8, eng: "We have hope and love in our hearts.", kor: "우리는 마음속에 희망과 사랑을 가져요.", conn: "등위절" },
                    { n: 9, eng: "I want to have an idea for the project.", kor: "나는 프로젝트를 위한 아이디어를 갖고 싶어요.", conn: "to(부정사)" },
                    { n: 10, eng: "They have a dream together.", kor: "그들은 함께 꿈을 가져요.", conn: "" }
                ];
                p3Data.forEach(s => {
                    document.write(`
                    <div class="magic-card">
                        <div class="magic-number-tag">${s.n}</div>
                        <div class="magic-content">
                            ${s.conn ? `<div class="grammar-visual-box">
                                <img src="./img/wct01_n.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                                <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                                <span class="magic-connector-tag">${s.conn}</span>
                            </div>` : '<div style="height:10px;"></div>'}
                            <div class="magic-text-box">
                                <div class="eng-sentence">${s.eng}</div>
                                <div class="kor-sentence">${s.kor}</div>
                            </div>
                        </div>
                    </div>`);
                });
            </script>
        </section>
        <footer class="page-footer"><span>© Wizard Chunking Together</span><span>Page 03</span></footer>
    </div>
</div>

</body>
</html>