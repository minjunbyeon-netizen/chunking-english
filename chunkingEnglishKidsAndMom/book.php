<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Chunking English E-Book</title>
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
            background-color: #4A4E53;
            /* 글로벌 폰트 적용 */
            font-family: 'Poppins', 'Pretendard', sans-serif;
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
            padding: 12mm; /* 상하좌우 여백을 약간 줄여서 하단 짤림 방지 */
            border-radius: 8px;
        }

        .bg-deco {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(var(--line-gray) 1.5px, transparent 1.5px);
            background-size: 28px 28px; z-index: 0; opacity: 0.7; pointer-events: none;
        }

        .z-content {
            position: relative;
            z-index: 10;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* ---------------------------------
           [상단 기능 (PDF 다운로드)]
        --------------------------------- */
        .top-controls {
            width: 210mm;
            display: flex;
            justify-content: flex-end;
            margin-bottom: 12px;
        }
        .btn-pdf-download {
            background: #1A1A1A; /* 블랙으로 변경 */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-family: 'Pretendard', sans-serif;
            font-weight: 700;
            font-size: 1rem;
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
            width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            font-family: 'Pretendard', sans-serif;
        }
        .modal-content h2 { margin-bottom: 16px; font-size: 1.3rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px; }

        .day-select-wrapper { margin-bottom: 16px; }
        .day-select-wrapper label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem; color: var(--text-sub); }
        .day-select {
            width: 100%; padding: 10px; border-radius: 8px; border: 2px solid #F0E4E7;
            font-family: 'Poppins', 'Pretendard', sans-serif; font-weight: 600; font-size: 1rem;
            color: var(--text-main); outline: none; cursor: pointer;
        }
        .day-select:focus { border-color: #3B82F6; }

        .print-option-label {
            display: flex; align-items: center; gap: 12px; padding: 12px 14px;
            border: 2px solid #F0E4E7; border-radius: 12px; margin-bottom: 8px;
            cursor: pointer; transition: all 0.2s; font-weight: 600; color: var(--text-main); font-size: 0.95rem;
        }
        .print-option-label:hover { border-color: #3B82F6; background: #EFF6FF; }
        .print-option-label input[type="radio"] { accent-color: #3B82F6; transform: scale(1.2); }

        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; }
        .btn-modal { padding: 10px 18px; border-radius: 10px; border: none; cursor: pointer; font-weight: 700; font-family: 'Pretendard', sans-serif; font-size: 1rem; transition: all 0.2s; }
        .btn-cancel { background: #F0E4E7; color: #6E767B; }
        .btn-cancel:hover { background: #E2D5D8; }
        .btn-confirm { background: #3B82F6; color: white; }
        .btn-confirm:hover { background: #2563EB; }

        /* ---------------------------------
           [상단 헤더 스타일]
        --------------------------------- */
        .main-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 0.8rem; padding-bottom: 0.6rem; /* 여백 축소 */
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
            overflow: visible;
        }
        .header-center h1 { font-family: 'Pretendard', sans-serif; font-weight: 700; font-size: 1.55rem; margin-bottom: 0px; }
        .sub-header-text { font-family: 'Poppins', sans-serif; font-size: 0.85rem; font-weight: 600; color: var(--text-sub); opacity: 0.8; letter-spacing: 0.3px; }

        .header-right { display: flex; justify-content: flex-end; gap: 8px; align-items: center; }

        /* 🚀 앱 모드 버튼 */
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
        .app-mode-btn img {
            width: 28px; height: 28px;
            object-fit: contain;
            filter: none;
        }
        .app-mode-btn.active {
            font-weight: 700;
        }

        /* 🚀 Listen & Repeat 버튼 */
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
            width: auto;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all 0.2s ease-in-out;
        }
        .btn-listen-repeat:hover {
            background-color: #3B82F6 !important;
            color: #FFFFFF !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.25);
        }
        .btn-listen-repeat:hover i {
            color: #FFFFFF !important;
        }
        .btn-listen-repeat:active { transform: translateY(0); }
        .btn-listen-repeat i { font-size: 1.1rem; transition: color 0.2s; }

        /* TTS 플레이어 바 (깜빡임 없이 심플하게) */
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
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .player-ctrl-btn:hover { background: #3B82F6; color: white; transform: scale(1.05); }

        .progress-text { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 0.9rem; color: #3B82F6; text-align: center; min-width: 40px; }

        /* 공통 타이포그래피 */
        .font-red { color: var(--red-point); }
        .drop-shadow { filter: drop-shadow(0 2px 4px rgba(250, 66, 82, 0.2)); }

        /* ---------------------------------
           [청킹 그리드 (Page 1~3)]
        --------------------------------- */
        .chunk-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 12px; /* 간격 축소 */
            flex-grow: 1;
            margin-bottom: 5px;
        }

        .chunk-card {
            background: #FFFFFF; padding: 8px; /* 패딩 축소 */
            border-radius: 14px;
            border: 1px solid #FDF4F6; display: flex; flex-direction: column;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: all 0.3s ease;
        }
        .chunk-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(255,126,150,0.12); border-color: #FFC9D4; }
        .chunk-card.main-point { border: 2px solid rgba(255, 126, 150, 0.4); box-shadow: var(--shadow-float); }

        .img-container {
            width: 100%; height: 48%; /* 이미지 높이 축소 */
            background: #FAFAFA;
            border-radius: 10px; margin-bottom: 8px; overflow: hidden;
            border: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
            display: flex; justify-content: center; align-items: center;
        }
        .img-container img { width: 100%; height: 100%; object-fit: cover; }

        .note-area {
            position: relative; flex-grow: 1; display: flex; flex-direction: column;
            justify-content: center; align-items: center; border-radius: 10px;
            background: #FFFAFB;
            border: 1px solid #FFEFF2;
            overflow: hidden;
        }
        .note-line {
            position: absolute; inset: 0;
            background-image: repeating-linear-gradient(transparent, transparent 22px, rgba(255, 126, 150, 0.12) 23px);
            background-position: top;
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
        .note-text-wrap h3 { font-family: 'Poppins', sans-serif; font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin-bottom: 2px; letter-spacing: 0.5px; }
        .note-text-wrap span { font-family: 'Pretendard', sans-serif; font-size: 0.9rem; font-weight: 500; color: var(--text-sub); }

        /* ---------------------------------
           [10문장 매직 카드 리스트 (Page 4)]
        --------------------------------- */
        .magic-card-list {
            display: flex;
            flex-direction: column;
            gap: 8px; /* 간격 조절로 하단 짤림 방지 */
            flex-grow: 1;
            margin-bottom: 5px;
        }

        .magic-card {
            flex: 1;
            background: #FFFFFF; border: 1px solid rgba(255, 126, 150, 0.15); border-radius: 14px;
            padding: 4px 14px; display: flex; align-items: center; gap: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02); position: relative;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .magic-card:hover {
            box-shadow: 0 6px 16px rgba(255,126,150,0.1);
            border-color: #FFC9D4;
            transform: translateY(-1px);
        }
        /* 네온 푸른빛 효과 추가 & 깜빡임(애니메이션) 없음 */
        .magic-card.reading {
            border-color: #00F3FF;
            box-shadow: 0 0 12px rgba(0, 243, 255, 0.6), inset 0 0 8px rgba(0, 243, 255, 0.1);
            transform: scale(1.02);
            z-index: 10;
        }

        .magic-number-tag {
            background: var(--dark-box);
            color: #FFFFFF;
            border-radius: 8px;
            width: 32px; height: 32px;
            font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.05rem;
            display: flex; align-items: center; justify-content: center;
            border: none; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1); flex-shrink: 0;
        }

        .magic-content {
            flex: 1; min-width: 0; display: flex; flex-direction: row; align-items: center; gap: 14px; padding: 2px 0;
        }

        .grammar-visual-box {
            display: flex; align-items: center; gap: 6px; padding: 4px 12px; background: #FFF5F7;
            border: none; border-radius: 10px; flex-shrink: 0;
            box-shadow: inset 0 1px 3px rgba(255,126,150,0.06);
        }
        .wizard-icon { width: 22px; height: 22px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(255,126,150,0.2)); }

        .magic-connector-tag {
            background: #FFFFFF; border: none; color: #FF5A82;
            font-family: 'Pretendard', sans-serif; font-weight: 600; font-size: 0.9rem; padding: 2px 10px; border-radius: 6px;
            box-shadow: 0 2px 4px rgba(255,126,150,0.1); letter-spacing: 0.5px;
        }
        .text-plus { color: var(--primary); font-family: 'Poppins', sans-serif; font-size: 0.8rem; font-weight: 700; opacity: 0.8; }

        .magic-text-box {
            flex: 1;
            background: transparent; border: none; box-shadow: none;
            padding: 2px 12px;
            border-left: 2px solid #FFEFF2;
            border-radius: 0;
            display: flex; flex-direction: column; justify-content: center;
        }

        /* 🚀 영문장 폰트 Poppins & 크기 약간 조절 */
        .eng-sentence { font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 1.1rem; color: var(--text-main); line-height: 1.2; margin-bottom: 2px; }
        /* 한글 폰트 Pretendard */
        .kor-sentence { font-family: 'Pretendard', sans-serif; font-weight: 400; font-size: 0.9rem; color: var(--text-sub); }

        @media (max-width: 768px) {
            .magic-content { flex-direction: column; align-items: flex-start; gap: 8px; }
            .magic-text-box { border-left: none; border-top: 2px solid #FFEFF2; padding: 6px 0 0 0; }
        }

        /* ---------------------------------
           [푸터]
        --------------------------------- */
        .page-footer {
            margin-top: auto;
            padding-top: 10px; border-top: 2px solid var(--line-gray);
            display: flex; justify-content: space-between; font-size: 11px; color: #A0AAB2;
            font-family: 'Poppins', sans-serif; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;
        }
    </style>

    <script>
        /** ---------------------------------
         * [PDF 다운로드 (출력) 로직]
         * --------------------------------- */
        function openPdfModal() {
            // Day 1 ~ 250 옵션 동적 생성 (최초 1회만)
            const daySelect = document.getElementById('daySelect');
            if (daySelect.options.length === 0) {
                for (let i = 1; i <= 250; i++) {
                    let opt = document.createElement('option');
                    opt.value = i;
                    opt.innerHTML = 'Day ' + i;
                    daySelect.appendChild(opt);
                }
            }
            document.getElementById('pdfModal').classList.add('active');
        }

        function closePdfModal() {
            document.getElementById('pdfModal').classList.remove('active');
        }

        function printSelectedPages() {
            const selectedDay = parseInt(document.getElementById('daySelect').value);
            const choice = document.querySelector('input[name="printOption"]:checked').value;
            const sheets = document.querySelectorAll('.sheet');

            // 초기화
            sheets.forEach(sheet => sheet.classList.remove('no-print-temp'));

            if (choice !== 'all') {
                // 선택한 Day의 시작 인덱스와 끝 인덱스 계산 (1 Day = 4페이지 기준)
                const startIndex = (selectedDay - 1) * 4;
                const endIndex = startIndex + 3;

                sheets.forEach((sheet, index) => {
                    // 선택한 Day 범위에 포함되지 않는 페이지는 숨김
                    if (index < startIndex || index > endIndex) {
                        sheet.classList.add('no-print-temp');
                        return;
                    }

                    const relIndex = index % 4;

                    if (choice === 'list' && relIndex !== 3) {
                        sheet.classList.add('no-print-temp'); // 리스트만 선택시 앞 3페이지 숨김
                    }
                });
            }

            window.print();

            // 인쇄 창이 닫힌 후 다시 원상복구
            setTimeout(() => {
                sheets.forEach(sheet => sheet.classList.remove('no-print-temp'));
                closePdfModal();
            }, 500);
        }

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
            if(playerBar) playerBar.classList.add('active');
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
            const pauseIcon = sheet.querySelector('.pause-btn i');

            if (countEl) countEl.innerText = ttsState.repeatCount + 1;
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
                const playerBar = ttsState.currentSheet.querySelector('.tts-player-bar');
                const btnListen = ttsState.currentSheet.querySelector('.btn-listen-repeat');
                if(playerBar) playerBar.classList.remove('active');
                if(btnListen) btnListen.style.display = 'flex';
                ttsState.sentences.forEach(c => c.classList.remove('reading'));
            }
        }
    </script>
</head>
<body>

<div class="top-controls no-print">
    <button onclick="openPdfModal()" class="btn-pdf-download">
        <i class="fa-solid fa-file-pdf"></i> PDF 저장하기
    </button>
</div>

<div id="pdfModal" class="modal-overlay no-print">
    <div class="modal-content">
        <h2><i class="fa-solid fa-print"></i> PDF 저장 옵션</h2>
        <p style="margin-bottom: 16px; font-size: 0.9rem; color: #6E767B;">다운로드할 범위를 선택해주세요.</p>

        <label class="print-option-label" style="background: #F8FAFC; border-color: #BFDBFE;">
            <input type="radio" name="printOption" value="all">
            <span style="color: #2563EB;">전체 페이지 저장 (모든 Day 포함)</span>
        </label>

        <div class="day-select-wrapper" style="margin-top: 12px;">
            <label for="daySelect">특정 Day 선택 (1~250)</label>
            <select id="daySelect" class="day-select">
            </select>
        </div>

        <label class="print-option-label">
            <input type="radio" name="printOption" value="day1" checked>
            해당 Day 전체 (4페이지)
        </label>
        <label class="print-option-label">
            <input type="radio" name="printOption" value="list">
            10문장 리스트만 (마지막 1페이지)
        </label>

        <div class="modal-actions">
            <button class="btn-modal btn-cancel" onclick="closePdfModal()">취소</button>
            <button class="btn-modal btn-confirm" onclick="printSelectedPages()">저장하기</button>
        </div>
    </div>
</div>

<div class="sheet">
    <div class="bg-deco"></div>
    <div class="z-content">
        <header class="main-header">
            <div class="header-left"><div class="day-badge">Day 1</div></div>
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
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+2"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 02</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+3"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 03</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+4"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 04</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+5"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 05</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+6"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 06</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+7"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 07</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+8"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 08</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+9"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 09</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
        </section>

        <footer class="page-footer"><span>© Chunking English Kids&Mom</span><span>Page 01</span></footer>
    </div>
</div>

<div class="sheet page-break">
    <div class="bg-deco"></div>
    <div class="z-content">
        <header class="main-header">
            <div class="header-left"><div class="day-badge">Day 1</div></div>
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
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+2"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 02</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+3"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 03</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+4"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 04</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+5"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 05</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+6"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 06</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+7"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 07</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+8"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 08</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+9"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 09</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
        </section>

        <footer class="page-footer"><span>© Chunking English Kids&Mom</span><span>Page 02</span></footer>
    </div>
</div>

<div class="sheet page-break">
    <div class="bg-deco"></div>
    <div class="z-content">
        <header class="main-header">
            <div class="header-left"><div class="day-badge">Day 1</div></div>
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
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+2"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 02</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+3"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 03</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+4"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 04</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+5"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 05</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+6"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 06</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+7"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 07</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+8"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 08</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
            <div class="chunk-card">
                <div class="img-container"><img src="https://placehold.co/400x400/FF8FA3/FFF?text=Img+9"></div>
                <div class="note-area light">
                    <div class="note-line"></div><div class="note-margin"></div>
                    <div class="note-text-wrap">
                        <h3>CHUNK 09</h3>
                        <span>뜻 적기</span>
                    </div>
                </div>
            </div>
        </section>

        <footer class="page-footer"><span>© Chunking English Kids&Mom</span><span>Page 03</span></footer>
    </div>
</div>

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
                    <i class="fa-solid fa-volume-high"></i>
                    <span>Listen and Repeat!</span>
                </button>

                <div class="tts-player-bar">
                    <button class="player-ctrl-btn pause-btn" onclick="togglePause()"><i class="fa-solid fa-pause"></i></button>
                    <button class="player-ctrl-btn" onclick="restartTTS()"><i class="fa-solid fa-arrow-rotate-left"></i></button>
                    <div class="progress-text"><span class="current-count">1</span> / 7</div>
                    <button class="player-ctrl-btn" style="background:#f8d7da; color:#dc3545;" onclick="stopTTS()"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </header>

        <section class="magic-card-list">
            <div class="magic-card">
                <div class="magic-number-tag">1</div>
                <div class="magic-content">
                    <div class="grammar-visual-box">
                        <img src="./img/wct01_n.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <span class="magic-connector-tag">to(부정사)</span>
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <img src="./img/wct02.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    </div>
                    <div class="magic-text-box">
                        <div class="eng-sentence">I have a dream to change my life.</div>
                        <div class="kor-sentence">나는 내 삶을 바꿀 꿈을 가지고 있어요.</div>
                    </div>
                </div>
            </div>

            <div class="magic-card">
                <div class="magic-number-tag">2</div>
                <div class="magic-content">
                    <div class="grammar-visual-box">
                        <img src="./img/wct01_n.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <span class="magic-connector-tag">ing(동명사)</span>
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <img src="./img/wct02.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    </div>
                    <div class="magic-text-box">
                        <div class="eng-sentence">Having a dream changes my future.</div>
                        <div class="kor-sentence">꿈을 갖는 것은 나의 미래를 바꿔요.</div>
                    </div>
                </div>
            </div>

            <div class="magic-card">
                <div class="magic-number-tag">3</div>
                <div class="magic-content">
                    <div class="grammar-visual-box">
                        <img src="./img/wct01_n.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <span class="magic-connector-tag">전치사</span>
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <img src="./img/wct02.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    </div>
                    <div class="magic-text-box">
                        <div class="eng-sentence">I start my trip with a big dream.</div>
                        <div class="kor-sentence">나는 큰 꿈을 가지고 여행을 시작해요.</div>
                    </div>
                </div>
            </div>

            <div class="magic-card">
                <div class="magic-number-tag">4</div>
                <div class="magic-content">
                    <div class="grammar-visual-box">
                        <img src="./img/wct01_n.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <span class="magic-connector-tag">부사절</span>
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <img src="./img/wct02.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    </div>
                    <div class="magic-text-box">
                        <div class="eng-sentence">If I have a goal, I will study hard.</div>
                        <div class="kor-sentence">내가 목표가 있다면, 열심히 공부할 거야.</div>
                    </div>
                </div>
            </div>

            <div class="magic-card">
                <div class="magic-number-tag">5</div>
                <div class="magic-content">
                    <div class="grammar-visual-box">
                        <img src="./img/wct01_n.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <span class="magic-connector-tag">전치사</span>
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <img src="./img/wct02.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    </div>
                    <div class="magic-text-box">
                        <div class="eng-sentence">She has a plan for the weekend.</div>
                        <div class="kor-sentence">그녀는 주말을 위한 계획이 있어요.</div>
                    </div>
                </div>
            </div>

            <div class="magic-card">
                <div class="magic-number-tag">6</div>
                <div class="magic-content">
                    <div class="grammar-visual-box">
                        <img src="./img/wct01_n.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <span class="magic-connector-tag">to(부정사)</span>
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <img src="./img/wct02.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    </div>
                    <div class="magic-text-box">
                        <div class="eng-sentence">I like to have a chance to meet you.</div>
                        <div class="kor-sentence">당신을 만날 기회를 가져서 좋아요.</div>
                    </div>
                </div>
            </div>

            <div class="magic-card">
                <div class="magic-number-tag">7</div>
                <div class="magic-content">
                    <div class="grammar-visual-box">
                        <img src="./img/wct01_n.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <span class="magic-connector-tag">ing(동명사)</span>
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <img src="./img/wct02.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    </div>
                    <div class="magic-text-box">
                        <div class="eng-sentence">Having a wish makes me happy.</div>
                        <div class="kor-sentence">소망을 가지는 것은 나를 행복하게 해요.</div>
                    </div>
                </div>
            </div>

            <div class="magic-card">
                <div class="magic-number-tag">8</div>
                <div class="magic-content">
                    <div class="grammar-visual-box">
                        <img src="./img/wct01_n.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <span class="magic-connector-tag">등위절</span>
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <img src="./img/wct02.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    </div>
                    <div class="magic-text-box">
                        <div class="eng-sentence">We have hope and love in our hearts.</div>
                        <div class="kor-sentence">우리는 마음속에 희망과 사랑을 가져요.</div>
                    </div>
                </div>
            </div>

            <div class="magic-card">
                <div class="magic-number-tag">9</div>
                <div class="magic-content">
                    <div class="grammar-visual-box">
                        <img src="./img/wct01_n.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <span class="magic-connector-tag">to(부정사)</span>
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <img src="./img/wct02.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    </div>
                    <div class="magic-text-box">
                        <div class="eng-sentence">I want to have an idea for the project.</div>
                        <div class="kor-sentence">나는 프로젝트를 위한 아이디어를 갖고 싶어요.</div>
                    </div>
                </div>
            </div>

            <div class="magic-card">
                <div class="magic-number-tag">10</div>
                <div class="magic-content">
                    <div class="grammar-visual-box">
                        <img src="./img/wct01_n.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:magicwand.svg'">
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <span class="magic-connector-tag">명사절</span>
                        <span class="text-plus"><i class="fa-solid fa-plus"></i></span>
                        <img src="./img/wct02.png" class="wizard-icon" onerror="this.src='https://api.iconify.design/fxemoji:sparkles.svg'">
                    </div>
                    <div class="magic-text-box">
                        <div class="eng-sentence">They have a dream together.</div>
                        <div class="kor-sentence">그들은 함께 꿈을 가져요.</div>
                    </div>
                </div>
            </div>
        </section>
        <footer class="page-footer"><span>© Chunking English Kids&Mom</span><span>Page 04</span></footer>
    </div>
</div>

</body>
</html>