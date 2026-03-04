<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My English Tree</title>
    <style>
        /* [기본 설정 및 폰트] */
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&family=Quicksand:wght@500;700;900&display=swap');

        :root {
            /* 깊고 푸른 밤하늘 그라데이션 */
            --bg-top: #0b1026;
            --bg-mid: #162447;
            --bg-bottom: #1b3a5f;

            /* 트리 색상 */
            --tree-leaf-top: #2ea043;
            --tree-leaf-mid: #238636;
            --tree-leaf-bottom: #196c2e;
            --tree-shadow: rgba(0, 0, 0, 0.35);
            --tree-trunk: #5c4033;

            /* UI 요소 색상 */
            --text-color: #f8fafc;
            --bulb-glow: rgba(255, 243, 133, 0.9);
            --ui-bg: rgba(255, 255, 255, 0.15);
            --star-color: #fbbf24;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Quicksand', 'Nunito', sans-serif;
            background: linear-gradient(180deg, var(--bg-top) 0%, var(--bg-mid) 50%, var(--bg-bottom) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            color: var(--text-color);
        }

        /* [로딩 애니메이션] */
        #loader {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: var(--bg-top);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.8s ease-out;
        }
        .spinner {
            width: 50px; height: 50px;
            border: 5px solid rgba(255,255,255,0.2);
            border-top-color: var(--star-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* [배경 요소: 별, 달, 오로라, 캔버스] */
        #background-scene {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }

        /* 초승달 */
        .moon {
            position: absolute;
            top: 60px; right: 8vw;
            width: 60px; height: 60px;
            border-radius: 50%;
            box-shadow: 12px 12px 0 0 #fef08a; /* 그림자를 이용해 초승달 모양 구현 */
            transform: rotate(-15deg);
            filter: drop-shadow(0 0 20px rgba(254, 240, 138, 0.6));
            z-index: 2;
        }

        /* 반짝이는 작은 별들 */
        .stars {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            box-shadow:
                    5vw 10vh 1px 0px #fff, 15vw 25vh 2px 0px #fff, 25vw 5vh 1px 0px #fff, 35vw 35vh 1px 0px #fff, 45vw 15vh 2px 0px #fff,
                    55vw 45vh 1px 0px #fff, 65vw 10vh 1px 0px #fff, 75vw 30vh 2px 0px #fff, 85vw 15vh 1px 0px #fff, 95vw 40vh 1px 0px #fff,
                    10vw 60vh 2px 0px #fff, 20vw 80vh 1px 0px #fff, 30vw 55vh 1px 0px #fff, 40vw 75vh 2px 0px #fff, 50vw 90vh 1px 0px #fff,
                    60vw 65vh 1px 0px #fff, 70vw 85vh 2px 0px #fff, 80vw 50vh 1px 0px #fff, 90vw 70vh 1px 0px #fff, 95vw 90vh 2px 0px #fff,
                    8vw 30vh 1px 0px #fff, 28vw 40vh 1px 0px #fff, 48vw 20vh 1px 0px #fff, 68vw 50vh 1px 0px #fff, 88vw 5vh 1px 0px #fff;
            opacity: 0.5;
            animation: twinkle 8s infinite alternate ease-in-out;
            z-index: 1;
        }

        /* 추가적인 별 레이어 (더 작은 별들) */
        .stars2 {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            box-shadow:
                    12vw 8vh 1px 0px #fff, 22vw 38vh 1px 0px #fff, 32vw 18vh 2px 0px #fff, 42vw 48vh 1px 0px #fff, 52vw 8vh 1px 0px #fff,
                    62vw 38vh 2px 0px #fff, 72vw 18vh 1px 0px #fff, 82vw 48vh 1px 0px #fff, 92vw 28vh 1px 0px #fff, 3vw 78vh 2px 0px #fff,
                    18vw 68vh 1px 0px #fff, 38vw 88vh 1px 0px #fff, 58vw 58vh 2px 0px #fff, 78vw 78vh 1px 0px #fff, 98vw 68vh 1px 0px #fff;
            opacity: 0.3;
            animation: twinkle 6s infinite alternate-reverse ease-in-out;
            z-index: 1;
        }

        @keyframes twinkle {
            0% { opacity: 0.2; }
            100% { opacity: 0.9; }
        }

        /* 몽환적인 오로라 효과 */
        .aurora {
            position: absolute;
            top: -50%; left: -50%; width: 200%; height: 200%;
            filter: blur(80px);
            opacity: 0.3;
            mix-blend-mode: screen;
            z-index: 1;
        }
        .aurora.type1 {
            background: radial-gradient(ellipse at 30% 30%, rgba(255, 250, 205, 0.5), transparent 50%),
            radial-gradient(ellipse at 70% 60%, rgba(173, 216, 230, 0.4), transparent 50%);
        }
        .aurora.type2 {
            background: radial-gradient(ellipse at 60% 40%, rgba(130, 180, 255, 0.4), transparent 50%),
            radial-gradient(ellipse at 40% 80%, rgba(160, 130, 255, 0.4), transparent 50%);
            opacity: 0.25;
        }

        /* 동적 파티클을 위한 캔버스 (불꽃놀이, 별똥별) */
        #sky-canvas {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 3;
        }

        /* [메인 레이아웃] */
        header {
            text-align: center;
            padding: 4vh 0 1vh;
            z-index: 10;
        }
        h1 {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            font-weight: 900;
            text-shadow: 0 4px 15px rgba(0,0,0,0.5);
            letter-spacing: 2px;
            color: var(--star-color);
            animation: fadeInDown 1s ease-out;
        }

        main {
            flex: 1;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            padding: 10px 20px 30px;
        }

        /* [크리스마스 트리 그래픽 영역] */
        .tree-container {
            position: relative;
            width: 100%;
            max-width: 700px;
            aspect-ratio: 8 / 7;
            transform-origin: bottom center;
        }

        .tree-svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 25px 40px rgba(0,0,0,0.4));
        }

        .tree-star {
            transform-origin: 400px 50px;
            animation: starPulse 2s ease-in-out infinite alternate;
        }
        @keyframes starPulse {
            0% { filter: drop-shadow(0 0 15px rgba(251, 191, 36, 0.6)); }
            100% { filter: drop-shadow(0 0 35px rgba(251, 191, 36, 1)); }
        }

        /* [오너먼트 (전구) 스타일] */
        #ornaments-layer {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
        }

        .string-svg {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            overflow: visible;
            transition: opacity 0.4s;
        }
        .string-line {
            fill: none;
            stroke: rgba(255, 215, 0, 0.5);
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
            stroke-dasharray: 4000;
            stroke-dashoffset: 4000;
            animation: drawString 1.5s ease-in-out forwards;
        }
        @keyframes drawString {
            to { stroke-dashoffset: 0; }
        }

        .bulb {
            position: absolute;
            width: clamp(26px, 4.5%, 40px);
            aspect-ratio: 1;
            border-radius: 50%;
            box-shadow: 0 0 10px var(--bulb-glow), inset 0 0 5px rgba(255,255,255,0.8);
            cursor: pointer;
            pointer-events: auto;
            transform: translate(-50%, -50%) scale(0);
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s, background 0.3s, filter 0.3s;
            animation: blinkBulb 3s infinite alternate;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #1e293b;
            font-weight: 900;
            font-size: clamp(0.7rem, 1.8vw, 1rem);
            text-shadow: 0 1px 1px rgba(255,255,255,0.6);
            border: 1px solid rgba(255,255,255,0.4);
            will-change: box-shadow, filter, opacity;
        }

        .bulb.show {
            transform: translate(-50%, -50%) scale(1);
        }

        .bulb.completed {
            background: radial-gradient(circle at 30% 30%, #fff, #fbbf24) !important;
            color: #78350f;
            text-shadow: 0 1px 2px rgba(255,255,255,0.8);
            border: 2px solid #fff;
            transform: translate(-50%, -50%) scale(1.15);
            animation: goldenPulse 2s infinite alternate;
        }
        .bulb.completed.show {
            transform: translate(-50%, -50%) scale(1.15);
        }

        .bulb:hover {
            transform: translate(-50%, -50%) scale(1.3) !important;
            box-shadow: 0 0 30px rgba(255,255,255,1), inset 0 0 15px rgba(255,255,255,1);
            filter: brightness(1.3);
            animation-play-state: paused;
            z-index: 20;
        }

        .bulb.clicked::after {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(1);
            animation: popSound 0.5s ease-out forwards;
        }
        @keyframes popSound {
            0% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
            100% { transform: translate(-50%, -50%) scale(3); opacity: 0; }
        }

        @keyframes blinkBulb {
            0% {
                opacity: 0.7;
                box-shadow: 0 0 5px rgba(255, 255, 255, 0.2), inset 0 0 3px rgba(255, 255, 255, 0.4);
                filter: brightness(0.85);
            }
            100% {
                opacity: 1;
                box-shadow: 0 0 25px rgba(255, 255, 255, 0.9), 0 0 45px var(--bulb-glow), inset 0 0 15px rgba(255, 255, 255, 1);
                filter: brightness(1.3);
            }
        }

        @keyframes goldenPulse {
            0% {
                box-shadow: 0 0 15px #fbbf24, inset 0 0 10px rgba(255,255,255,0.8);
                filter: brightness(1);
            }
            100% {
                box-shadow: 0 0 35px #fbbf24, 0 0 25px rgba(255,255,255,0.7), inset 0 0 15px rgba(255,255,255,1);
                filter: brightness(1.2);
            }
        }

        /* [하단 UI 컨트롤 영역] */
        footer {
            padding: 1vh 20px 5vh;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }
        .control-panel {
            background: var(--ui-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 12px 30px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-nav {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            color: var(--text-color);
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-nav:hover:not(:disabled) {
            background: rgba(255,255,255,0.25);
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(255,255,255,0.2);
        }
        .btn-nav:active:not(:disabled) {
            transform: scale(0.95);
        }
        .btn-nav:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        .page-indicator {
            font-size: 1.2rem;
            font-weight: 700;
            min-width: 60px;
            text-align: center;
            letter-spacing: 2px;
            color: var(--text-color);
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div id="loader">
    <div class="spinner"></div>
</div>

<div id="background-scene">
    <div class="stars"></div>
    <div class="stars2"></div>
    <div class="aurora type1"></div>
    <div class="aurora type2"></div>
    <div class="moon"></div>
    <canvas id="sky-canvas"></canvas>
</div>

<header>
    <h1>My English Tree</h1>
</header>

<main>
    <div class="tree-container">
        <svg class="tree-svg" viewBox="0 0 800 700" xmlns="http://www.w3.org/2000/svg">
            <rect x="350" y="640" width="100" height="60" fill="var(--tree-trunk)" rx="5" />
            <rect x="400" y="640" width="50" height="60" fill="rgba(0,0,0,0.3)" />

            <polygon points="400,280 40,660 760,660" fill="var(--tree-leaf-bottom)" />
            <polygon points="400,280 400,660 760,660" fill="var(--tree-shadow)" />

            <polygon points="400,160 100,500 700,500" fill="var(--tree-leaf-mid)" />
            <polygon points="400,160 400,500 700,500" fill="var(--tree-shadow)" />
            <polygon points="400,500 200,500 400,560" fill="rgba(0,0,0,0.15)" />

            <polygon points="400,60 180,300 620,300" fill="var(--tree-leaf-top)" />
            <polygon points="400,60 400,300 620,300" fill="var(--tree-shadow)" />
            <polygon points="400,300 280,300 400,350" fill="rgba(0,0,0,0.15)" />

            <g class="tree-star">
                <polygon points="400,15 411,38 436,42 418,60 422,85 400,73 378,85 382,60 364,42 389,38" fill="var(--star-color)" />
            </g>
        </svg>

        <div id="ornaments-layer"></div>
    </div>
</main>

<footer>
    <div class="control-panel">
        <button class="btn-nav" id="btn-prev" aria-label="이전 페이지">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <div class="page-indicator" id="page-text" aria-live="polite">1 / 5</div>
        <button class="btn-nav" id="btn-next" aria-label="다음 페이지">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // [1. 기존 트리 및 오너먼트 로직]
        const totalOrnaments = 250;
        const itemsPerPage = 50;
        const totalPages = Math.ceil(totalOrnaments / itemsPerPage);
        let currentPage = 1;

        const layer = document.getElementById('ornaments-layer');
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');
        const pageText = document.getElementById('page-text');

        // 로딩 제거
        setTimeout(() => {
            const loader = document.getElementById('loader');
            loader.style.opacity = '0';
            setTimeout(() => loader.style.display = 'none', 800);
        }, 800);

        // 가랜드 곡선 계산
        const curvePoints = [];
        const numSteps = 300;

        for(let i = 0; i <= numSteps; i++) {
            const t = i / numSteps;
            const y = 110 + t * 510;
            const amplitude = 40 + t * 240;
            const angle = t * Math.PI * 8;
            const x = 400 + Math.sin(angle) * amplitude;
            curvePoints.push({x, y});
        }

        const segments = [];
        let totalLength = 0;
        for(let i = 0; i < curvePoints.length - 1; i++) {
            const dx = curvePoints[i+1].x - curvePoints[i].x;
            const dy = curvePoints[i+1].y - curvePoints[i].y;
            const len = Math.sqrt(dx*dx + dy*dy);
            segments.push({ dx, dy, len, startX: curvePoints[i].x, startY: curvePoints[i].y });
            totalLength += len;
        }

        const ornamentPositions = [];
        const step = totalLength / (itemsPerPage - 1);

        for (let i = 0; i < itemsPerPage; i++) {
            const targetDist = i * step;
            let currentDist = 0;
            let segIdx = 0;

            while (segIdx < segments.length - 1 && currentDist + segments[segIdx].len < targetDist) {
                currentDist += segments[segIdx].len;
                segIdx++;
            }

            const distInSeg = targetDist - currentDist;
            const ratio = segments[segIdx].len > 0 ? distInSeg / segments[segIdx].len : 0;
            const px = segments[segIdx].startX + segments[segIdx].dx * ratio;
            const py = segments[segIdx].startY + segments[segIdx].dy * ratio;

            const xPercent = (px / 800) * 100;
            const yPercent = (py / 700) * 100;

            const colors = ['#ff4d4d', '#ff9f1c', '#3b82f6', '#a855f7', '#fbbf24', '#ec4899'];
            const color = colors[i % colors.length];

            ornamentPositions.push({ x: xPercent, y: yPercent, color });
        }

        let completedSet = new Set();

        function renderPage(page) {
            const oldElements = document.querySelectorAll('.bulb, .string-svg');
            oldElements.forEach(el => {
                el.classList.remove('show');
                if (el.tagName.toLowerCase() !== 'svg') {
                    el.style.transform = 'translate(-50%, -50%) scale(0)';
                }
                el.style.opacity = '0';
            });

            setTimeout(() => {
                layer.innerHTML = '';
                const startIndex = (page - 1) * itemsPerPage;

                // 가랜드 그리기
                const svgString = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                svgString.setAttribute("viewBox", "0 0 800 700");
                svgString.setAttribute("class", "string-svg show");

                const polyline = document.createElementNS("http://www.w3.org/2000/svg", "polyline");
                let pointsStr = "";
                curvePoints.forEach(pos => pointsStr += `${pos.x},${pos.y} `);
                polyline.setAttribute("points", pointsStr.trim());
                polyline.setAttribute("class", "string-line");
                svgString.appendChild(polyline);
                layer.appendChild(svgString);

                // 오너먼트 배치
                ornamentPositions.forEach((pos, index) => {
                    const globalIndex = startIndex + index + 1;
                    if (globalIndex > totalOrnaments) return;

                    const bulb = document.createElement('div');
                    bulb.className = 'bulb';

                    const numSpan = document.createElement('span');
                    numSpan.innerText = globalIndex;
                    bulb.appendChild(numSpan);

                    bulb.style.left = `${pos.x}%`;
                    bulb.style.top = `${pos.y}%`;
                    bulb.style.background = `radial-gradient(circle at 30% 30%, #fff, ${pos.color})`;

                    if (completedSet.has(globalIndex)) {
                        bulb.classList.add('completed');
                    } else {
                        bulb.style.animationDelay = `${Math.random() * 2}s`;
                        bulb.style.animationDuration = `${Math.random() * 1.5 + 1}s`;
                    }

                    bulb.addEventListener('click', function() {
                        if(!this.classList.contains('clicked')) {
                            this.classList.add('clicked');
                            setTimeout(() => this.classList.remove('clicked'), 500);
                        }
                    });

                    layer.appendChild(bulb);

                    setTimeout(() => {
                        bulb.classList.add('show');
                    }, 400 + (index * 20));
                });

                pageText.innerText = `${page} / ${totalPages}`;
                btnPrev.disabled = page === 1;
                btnNext.disabled = page === totalPages;

            }, 400);
        }

        btnPrev.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderPage(currentPage);
            }
        });

        btnNext.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderPage(currentPage);
            }
        });

        // API에서 완료 Day 로드 후 렌더링
        fetch('api/progress/my_tree.php', {credentials: 'same-origin'})
            .then(r => r.json())
            .then(data => {
                if (data.completed_days && data.completed_days.length) {
                    completedSet = new Set(data.completed_days);
                }
                renderPage(currentPage);
            })
            .catch(() => renderPage(currentPage));

        // ==========================================
        // [2. 밤하늘 동적 오브젝트 (별똥별, 불꽃놀이)]
        // ==========================================
        const canvas = document.getElementById('sky-canvas');
        const ctx = canvas.getContext('2d');
        let width, height;

        function resizeCanvas() {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        const fireworks = [];
        const particles = [];
        const shootingStars = [];

        // 불꽃놀이 파티클 클래스
        class Particle {
            constructor(x, y, color) {
                this.x = x;
                this.y = y;
                this.prevX = x;
                this.prevY = y;
                this.color = color;
                const angle = Math.random() * Math.PI * 2;
                // [수정] 폭죽 상승 속도가 빨라짐에 따라, 터지는 속도도 약간 증가시켜 자연스럽게 함
                const speed = Math.random() * 3 + 1.0;
                this.vx = Math.cos(angle) * speed;
                this.vy = Math.sin(angle) * speed;
                this.life = 1.0;
                this.decay = Math.random() * 0.01 + 0.008;
            }
            update() {
                this.prevX = this.x;
                this.prevY = this.y;
                this.vy += 0.015; // 중력
                this.x += this.vx;
                this.y += this.vy;
                this.life -= this.decay;
            }
            draw(ctx) {
                ctx.globalAlpha = Math.max(0, this.life);
                ctx.strokeStyle = this.color;
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.beginPath();
                ctx.moveTo(this.prevX, this.prevY);
                ctx.lineTo(this.x, this.y);
                ctx.stroke();
            }
        }

        // 쏘아올려지는 불꽃 클래스
        class Firework {
            constructor() {
                this.x = Math.random() * width;
                this.y = height;
                this.targetY = height * 0.1 + Math.random() * height * 0.4; // 화면 상단 10~50% 부근
                // [수정] 속도를 4~7 정도로 대폭 상향 (기존 2~3.5)
                this.speed = Math.random() * 3 + 4;
                this.vx = (Math.random() - 0.5) * 0.5; // 살짝 좌우로 퍼지게
                this.color = `hsl(${Math.random() * 360}, 100%, 70%)`;
                this.dead = false;
            }
            update() {
                this.x += this.vx;
                this.y -= this.speed;
                if (this.y <= this.targetY) {
                    this.dead = true;
                    // 불꽃 터짐 효과
                    for (let i = 0; i < 50; i++) { // 파티클 개수도 살짝 증가
                        particles.push(new Particle(this.x, this.y, this.color));
                    }
                }
            }
            draw(ctx) {
                ctx.globalAlpha = 1;
                ctx.fillStyle = '#fff';
                ctx.beginPath();
                ctx.arc(this.x, this.y, 2, 0, Math.PI * 2); // 올라가는 불꽃 크기 약간 확대
                ctx.fill();
            }
        }

        // 별똥별 클래스
        class ShootingStar {
            constructor() {
                this.x = Math.random() * width * 1.5;
                this.y = 0;
                this.length = Math.random() * 80 + 40;
                this.speed = Math.random() * 4 + 4;
                this.angle = (Math.random() * 20 + 30) * (Math.PI / 180); // 30~50도 각도 하강
                this.vx = -Math.cos(this.angle) * this.speed;
                this.vy = Math.sin(this.angle) * this.speed;
                this.dead = false;
            }
            update() {
                this.x += this.vx;
                this.y += this.vy;
                if (this.x < -this.length || this.y > height + this.length) {
                    this.dead = true;
                }
            }
            draw(ctx) {
                const tailX = this.x + Math.cos(this.angle) * this.length;
                const tailY = this.y - Math.sin(this.angle) * this.length;

                const grad = ctx.createLinearGradient(this.x, this.y, tailX, tailY);
                grad.addColorStop(0, 'rgba(255, 255, 255, 1)');
                grad.addColorStop(1, 'rgba(255, 255, 255, 0)');

                ctx.globalAlpha = 1;
                ctx.beginPath();
                ctx.moveTo(this.x, this.y);
                ctx.lineTo(tailX, tailY);
                ctx.strokeStyle = grad;
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.stroke();

                // 별똥별 머리 부분 반짝임
                ctx.beginPath();
                ctx.arc(this.x, this.y, 1.5, 0, Math.PI * 2);
                ctx.fillStyle = '#fff';
                ctx.fill();
            }
        }

        // 애니메이션 루프
        function animateSky() {
            ctx.clearRect(0, 0, width, height);

            // [수정] 불꽃놀이 생성 로직 변경
            // 확률을 0.003 -> 0.02로 높임 & 당첨 시 여러 개 동시 생성
            if (Math.random() < 0.02) {
                // 한 번에 3개 ~ 5개의 불꽃을 생성
                const count = Math.floor(Math.random() * 3) + 3;
                for(let k=0; k<count; k++) {
                    fireworks.push(new Firework());
                }
            }

            // 간헐적으로 별똥별 생성
            if (Math.random() < 0.001) {
                shootingStars.push(new ShootingStar());
            }

            // 업데이트 및 그리기
            for (let i = fireworks.length - 1; i >= 0; i--) {
                fireworks[i].update();
                fireworks[i].draw(ctx);
                if (fireworks[i].dead) fireworks.splice(i, 1);
            }

            for (let i = particles.length - 1; i >= 0; i--) {
                particles[i].update();
                particles[i].draw(ctx);
                if (particles[i].life <= 0) particles.splice(i, 1);
            }

            for (let i = shootingStars.length - 1; i >= 0; i--) {
                shootingStars[i].update();
                shootingStars[i].draw(ctx);
                if (shootingStars[i].dead) shootingStars.splice(i, 1);
            }

            requestAnimationFrame(animateSky);
        }

        animateSky();
    });
</script>
</body>
</html>