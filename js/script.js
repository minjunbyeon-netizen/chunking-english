/**
 * script.js
 * Chunking English Kids & Mom - Main Logic
 */





const levelData = window.SERVER_DATA.levelData;
const masterChunkData = window.SERVER_DATA.masterChunkData;
const iconMap = window.SERVER_DATA.iconMap;

let unlockedDays = window.SERVER_DATA.progress.unlockedDays;
let completedVerbs = new Set(window.SERVER_DATA.progress.completedVerbs);
let stationProgress = window.SERVER_DATA.progress.stationProgress;
let completedDays = new Set((window.SERVER_DATA.progress.completedDays || []).map(Number));

// ==========================================
// Global Variables & State
// ==========================================
let hasAutoTreeShown = false;
let hasShownDrillGuide = false; // 가이드 모달 체크

// DOM Elements (will be populated/used)
const mainLogo = document.getElementById('main-logo');
const wizardBadge = document.getElementById('wizard-badge');

// Game Board State
const totalTiles = 20;
let playerPos = 0;
const playerToken = document.getElementById('player-token');
const diceValue = document.getElementById('dice-value');
const diceEl = document.querySelector('.dice');

// Map State
const totalMapDays = 9;
const stationMaxDays = {1: 10, 2: 19, 3: 58, 4: 12, 5: 39, 6: 55, 7: 31, 8: 10, 9: 16};
let currentView = 'map';
let currentVerb = '';
let currentDay = 1;
let pendingTreeModal = false;
let pendingTogetherModal = false;
let collectedIndices = new Set();
let dayProgress = {};
let activeCardIndex = -1;
let totalStars = 0;
let totalTrees = 0;
let pendingDayUnlock = null;
const nodeCoordinates = [];
const nodeOffsets = [];

// Together Modal State
const totalTogetherDays = 250;
let currentReviewDay = 1;

// Audio Player State
let audioQueue = [];
let audioHighlightIndices = []; // ✨ 1번 추가 위치: 하이라이트 행 추적용
let audioIndex = 0;
let isAudioPlaying = false;
let currentAudioTarget = ''; // 'top', 'basic', 'applied'
let hasPlayedTop = false;
let hasPlayedBasic = false;

// Intro Reading State
let introLoopCount = 0;
let isIntroPlaying = false;
const MAX_INTRO_LOOPS = 7;
let introSentences = [];
let introCurrentIndex = 0;

// Tree Logic Variables
let treeCanvas, ctx;
let treeAnimationId;
const mouse = {x: 0, y: 0};
let isHovering = false;
let shakeCenter = {x: -9999, y: -9999};
let shakeIntensity = 0;
let centerX, centerY;
let branches = [];
let roots = [];
let ornaments = [];
let particles = [];
let availableSpots = {ROOT: [], MIDDLE: [], TOP: []};
let reservedPositions = [];
let currentDayAnim = 1;
const maxDays = 40;
const MIN_DISTANCE = 24;
const colorsLevel1 = ["#FFD700", "#FF8C00", "#FF4500"];
const colorsLevel2 = ["#00FFFF", "#00FF7F", "#7FFF00"];
const colorsLevel3 = ["#E0E0E0", "#C0C0C0", "#D3D3D3", "#B0C4DE", "#F5F5F5"];

// Train Variables
const trainImg = new Image();
trainImg.src = './img/ck_train.png';
let trainLoaded = false;
let trainState = {x: -150, y: 0, width: 100, height: 50, speed: 1.2, angle: 0};
let justCompletedDay = false; // 방금 완료 여부 체크

// Guide Bubble Data
const guideStepsData = [
    {
        step: 1,
        title: "Start Journey 🚩",
        desc: "기차는 <strong>1번 역(Station 1)</strong>부터 순서대로 시작해요.",
        icon: "fa-solid fa-dice",
        bg: "bg-blue-50",
        border: "border-blue-200",
        iconColor: "text-blue-400"
    },
    {
        step: 2,
        title: "Select Station 🚉",
        desc: "각 역마다 숨겨진 <strong>3가지 핵심 미션</strong>을 확인해요.",
        icon: "fa-solid fa-map-location-dot",
        bg: "bg-green-50",
        border: "border-green-200",
        iconColor: "text-green-500"
    },
    {
        step: 3,
        title: "Intro Listening 👂",
        desc: "원어민 소리를 듣고 <strong>큰 소리로</strong> 따라 말해보세요.",
        icon: "fa-solid fa-headphones-simple",
        bg: "bg-purple-50",
        border: "border-purple-200",
        iconColor: "text-purple-400"
    },
    {
        step: 4,
        title: "Pick & Drill 🃏",
        desc: "카드를 뒤집으며 학습하고 <strong>씨앗 7개</strong>를 모두 모으세요!",
        icon: "fa-solid fa-layer-group",
        bg: "bg-yellow-50",
        border: "border-yellow-200",
        iconColor: "text-yellow-500"
    },
    {
        step: 5,
        title: "Collect Seeds 🌱",
        desc: "마법사 청킹과 복습을 마치면 <strong>다음 역</strong>으로 갈 수 있어요.",
        icon: "fa-solid fa-seedling",
        bg: "bg-green-100",
        border: "border-green-300",
        iconColor: "text-green-600"
    },
    {
        step: 6,
        title: "Together Review 🧙‍♂️",
        desc: "차근차근 실력을 쌓아 마지막 <strong>9번 역</strong>까지 도전하세요!",
        icon: "fa-solid fa-wand-magic-sparkles",
        bg: "bg-indigo-50",
        border: "border-indigo-200",
        iconColor: "text-indigo-400"
    }
];
let currentGuideIndex = 0;
let guideInterval = null;




const togetherData = {
    1: [
        {eng: "I have a dream to change my life.", kor: "나는 가지다 꿈을 / 바꾸는 나의 삶을"},
        {eng: "I have a dream about startimg my English trip.", kor: "나는 가지다 꿈을 / 시작하는 것에 대한 나의 영어 여행을"},
        {eng: "I have a dream, so I start my English trip.", kor: "나는 가지다 꿈을 / 그래서 나는 시작하다 나의 영어 여행을"}
    ],
    2: [
        {eng: "Having a dream changes my life.", kor: "가지는 것 꿈을 / 바꾸다 나의 생활을 "},
        {eng: "Starting my English trip can change my life.", kor: "시작하는 것 나의 영어 여행을 / 바꿀수 있다 나의 생활을"},
        {eng: "I change my life, and I start my English trip.", kor: "나는 바꾸다 나의 생활을 / 그리고 나는 시작하다 나의 영어 여행을"}
    ],
    // ... 나머지 데이터 생략하지 않고 1~40까지 있어야 함. (분량상 여기서는 패턴만 유지)
    // 실제 사용시에는 원본의 1~40 데이터를 모두 넣어야 합니다.
    40: [
        {eng: "I discuss the topic.", kor: "나는 논의하다 주제를"},
        {eng: "I mention the problem.", kor: "나는 언급하다 문제를"},
        {eng: "I exchange ideas.", kor: "나는 교환하다 아이디어를"}
    ]
};

// Data Initialization
const colors_list = ["FBF595", "A3D8F4", "F4AEBD", "8AC9A6", "E2E8F0", "F48B50", "D8B4E2"];
for (const key in masterChunkData) {
    masterChunkData[key].forEach((item, idx) => {
        if (!item.icon) item.icon = iconMap[key.toLowerCase()] || "fa-solid fa-star";
        if (!item.color) item.color = colors_list[idx % colors_list.length];
        if (!item.image) item.image = "./img/exc_n1.png";
    });
}

trainImg.onload = () => {
    trainLoaded = true;
    const targetWidth = 100;
    const aspectRatio = trainImg.naturalHeight / trainImg.naturalWidth;
    trainState.width = targetWidth;
    trainState.height = targetWidth * aspectRatio;
    trainState.x = -trainState.width * 2;
};

// ==========================================
// 4. Helper Functions
// ==========================================
function getChunksForVerb(verbKey) {
    const key = String(verbKey || '').toLowerCase();

    // 1) 원본 데이터가 있으면 그대로
    if (masterChunkData && masterChunkData[key] && Array.isArray(masterChunkData[key]) && masterChunkData[key].length) {
        return masterChunkData[key];
    }

    // 2) 숫자 접미어 제거한 키로도 시도 (예: have1 -> have)
    const keyNoNum = key.replace(/[0-9]+$/g, '');
    if (masterChunkData && masterChunkData[keyNoNum] && Array.isArray(masterChunkData[keyNoNum]) && masterChunkData[keyNoNum].length) {
        return masterChunkData[keyNoNum];
    }

    // 3) 더미 데이터 생성 (카드 7장 기준)
    const displayVerb = keyNoNum || key || 'verb';
    const dummy = Array.from({ length: 7 }, (_, i) => {
        const n = i + 1;
        return {
            eng: `${displayVerb} sample ${n}`,
            kor: `${displayVerb} 더미 ${n}`,
            image: './img/exc_n1.png',
            color: 'E2E8F0',
            icon: (iconMap && iconMap[displayVerb]) ? iconMap[displayVerb] : 'fa-solid fa-star'
        };
    });

    console.warn(`데이터를 찾을 수 없어 더미를 사용합니다: ${key}`);
    return dummy;
}

function getVerbFromChunk(chunk) {
    return chunk.split(' ')[0].toLowerCase();
}

function getRestOfChunk(chunk) {
    return chunk.split(' ').slice(1).join(' ');
}

function conjugateThirdPerson(verb) {
    const v = verb.toLowerCase();
    if (v === 'have') return 'has';
    if (v === 'do') return 'does';
    if (v === 'go') return 'goes';
    if (v === 'try') return 'tries';
    if (v === 'study') return 'studies';
    if (v === 'wash') return 'washes';
    if (v === 'brush') return 'brushes';
    if (v.endsWith('s') || v.endsWith('sh') || v.endsWith('ch') || v.endsWith('x') || v.endsWith('z')) return v + 'es';
    return v + 's';
}

function getGerund(verb) {
    const v = verb.toLowerCase();
    const exceptions = {
        'have': 'having',
        'take': 'taking',
        'make': 'making',
        'come': 'coming',
        'give': 'giving',
        'use': 'using',
        'leave': 'leaving',
        'get': 'getting',
        'put': 'putting',
        'let': 'letting',
        'begin': 'beginning',
        'run': 'running',
        'swim': 'swimming'
    };
    if (exceptions[v]) return exceptions[v];
    if (v.endsWith('ie')) return v.slice(0, -2) + 'ying';
    if (v.endsWith('e')) return v.slice(0, -1) + 'ing';
    return v + 'ing';
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function getSeedDetail(chunkEng) {
    const verb = getVerbFromChunk(chunkEng);
    const rest = getRestOfChunk(chunkEng);
    const verb3rd = conjugateThirdPerson(verb);
    const verbIng = getGerund(verb);
    const chunkBase = chunkEng;

    const basic = [
        {type: "1인칭 단수", example: `I ${chunkBase}.`},
        {type: "2인칭 단수", example: `You ${chunkBase}.`},
        {type: "3인칭 단수 (He)", example: `He ${verb3rd} ${rest}.`},
        {type: "3인칭 단수 (She)", example: `She ${verb3rd} ${rest}.`},
        {type: "의문문", example: `Do you ${chunkBase}?`},
        {type: "부정문", example: `I don't ${chunkBase}.`},
        {type: "명령문", example: `${capitalize(chunkBase)}, please.`}
    ];

    const applied = [
        {group: "기본 청킹동사구", type: "기본형", example: chunkBase, kor: "기본"},
        {group: "구 (Phrase) 활용", type: "to부정사구", example: `to ${chunkBase}`, kor: "Phrase"},
        {group: "구 (Phrase) 활용", type: "동명사(ing)구", example: `${verbIng} ${rest}`, kor: "Phrase"},
        {group: "구 (Phrase) 활용", type: "전치사 + ing", example: `after/before ${verbIng} ${rest}`, kor: "Phrase"},
        {group: "절 (Clause) 활용", type: "명사절 (that)", example: `that I ${chunkBase}`, kor: "Clause"},
        {group: "절 (Clause) 활용", type: "형용사절 (who)", example: `who ${verb3rd} ${rest}`, kor: "Clause"},
        {group: "절 (Clause) 활용", type: "부사절 (시간)", example: `when I ${chunkBase}`, kor: "Clause"},
        {group: "절 (Clause) 활용", type: "부사절 (이유)", example: `because I ${chunkBase}`, kor: "Clause"},
        {group: "절 (Clause) 활용", type: "부사절 (조건)", example: `if I ${chunkBase}`, kor: "Clause"},
    ];
    return {basic, applied};
}

function generateBasicUsageHTML(basicExamples, chunkEng) {
    let html = `<table class="usage-table w-full"><tbody>`;
    const verb = getVerbFromChunk(chunkEng);
    const verb3rd = conjugateThirdPerson(verb);
    const rest = getRestOfChunk(chunkEng);
    const escapedRest = rest.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    let pattern = `\\b(${verb}|${verb3rd})\\b`;
    if (rest) pattern = `\\b(${verb}|${verb3rd})\\s+${escapedRest}`;
    const re = new RegExp(pattern, 'gi');

    basicExamples.forEach((item, index) => {
        const highlighted = item.example.replace(re, match => `<span class="font-bold text-map-pink-dark">${match}</span>`);
        // tr에 id 부여 및 td에 transition 클래스 추가
        html += `<tr id="basic-row-${index}"><th class="text-xs whitespace-nowrap">${item.type}</th><td class="text-sm transition-colors duration-300 rounded-md px-2 py-1">${highlighted}</td></tr>`;
    });
    html += `</tbody></table>`;
    return html;
}

function generateAppliedUsageHTML(appliedExamples, chunkEng) {
    let html = `<table class="w-full text-left border-separate border-spacing-y-2"><tbody>`;
    const groups = {};
    const order = [];
    appliedExamples.forEach(item => {
        if (!groups[item.group]) {
            groups[item.group] = [];
            order.push(item.group);
        }
        groups[item.group].push(item);
    });
    const verb = getVerbFromChunk(chunkEng);
    const verbIng = getGerund(verb);
    const verb3rd = conjugateThirdPerson(verb);
    const rest = getRestOfChunk(chunkEng);
    const escapedRest = rest.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    let pattern = `\\b(${verb}|${verb3rd}|${verbIng})\\b`;
    if (rest) pattern = `\\b(${verb}|${verb3rd}|${verbIng})\\s+${escapedRest}`;
    const re = new RegExp(pattern, 'gi');

    order.forEach((groupName) => {
        const items = groups[groupName];
        let groupBg = "bg-gray-50";
        let groupText = "text-gray-600";
        const firstItemKor = items[0].kor;
        if (firstItemKor === "기본") {
            groupBg = "bg-pink-50";
            groupText = "text-map-pink-dark";
        } else if (firstItemKor === "Phrase") {
            groupBg = "bg-blue-50";
            groupText = "text-blue-500";
        } else if (firstItemKor === "Clause") {
            groupBg = "bg-green-50";
            groupText = "text-emerald-500";
        }

        items.forEach((item, index) => {
            const highlighted = item.example.replace(re, match => `<span class="font-bold text-map-pink-dark">${match}</span>`);
            html += `<tr class="bg-white">`;
            if (index === 0) html += `<th rowspan="${items.length}" class="${groupBg} ${groupText} p-2 text-xs md:text-sm font-bold align-middle text-center border border-transparent rounded-l-xl shadow-sm" style="width: 25%;">${groupName.replace(' ', '<br>')}</th>`;
            const radiusClass = (index === 0 && items.length === 1) ? 'rounded-r-xl' : (index === 0 ? 'rounded-tr-xl' : (index === items.length - 1 ? 'rounded-br-xl' : ''));
            html += `<td class="p-2 text-xs md:text-sm font-bold text-gray-500 align-middle border-t border-b ${index === 0 ? 'border-t-transparent' : 'border-t-gray-50'} ${index === items.length - 1 ? 'border-b-transparent' : 'border-b-gray-50'} bg-white pl-4" style="width: 30%;">${item.type}</td>`;
            html += `<td class="p-2 text-sm text-brand-text align-middle border-t border-b ${index === 0 ? 'border-t-transparent' : 'border-t-gray-50'} ${index === items.length - 1 ? 'border-b-transparent' : 'border-b-gray-50'} bg-white ${radiusClass} shadow-sm border-r border-transparent pr-4">${highlighted}</td>`;
            html += `</tr>`;
        });
    });
    html += `</tbody></table>`;
    return html;
}

// ==========================================
// 5. Core Logic (Hero, Map, Game)
// ==========================================

function updateLogo() {
    const scrollY = window.scrollY;
    const heroHeight = window.innerHeight;
    const range = window.innerWidth < 768 ? heroHeight * 0.4 : heroHeight * 0.6;
    let progress = Math.min(scrollY / range, 1);
    const moveDistanceY = (window.innerHeight / 2);
    const currentY = -(moveDistanceY * progress);
    const targetScale = window.innerWidth < 768 ? 0.35 : 0.25;
    const currentScale = 1 + (targetScale - 1) * progress;
    let currentX = 0;

    if (mainLogo) mainLogo.style.transform = `translate(${currentX}px, ${currentY}px) scale(${currentScale})`;

    const excImg = document.getElementById('main-exc-img');
    if (excImg) {
        let opacity = 1 - (scrollY / (heroHeight * 0.3));
        if (opacity < 0) opacity = 0;
        excImg.style.opacity = opacity;
        excImg.style.visibility = opacity === 0 ? 'hidden' : 'visible';
    }

    if (wizardBadge) {
        let badgeOpacity = 1 - (scrollY / 100);
        if (badgeOpacity < 0) badgeOpacity = 0;
        wizardBadge.style.opacity = badgeOpacity;
        wizardBadge.style.visibility = badgeOpacity === 0 ? 'hidden' : 'visible';
    }

    const gameBoard = document.querySelector('.game-board');
    if (gameBoard && scrollY < heroHeight) {
        const rotation = 20 + (scrollY * 0.05);
        gameBoard.style.transform = `rotateX(${Math.min(rotation, 45)}deg) scale(${0.9 - (scrollY * 0.0005)})`;
    }

    const topBtn = document.getElementById('scroll-to-top');
    if (topBtn) {
        if (scrollY > 500) topBtn.classList.add('show');
        else topBtn.classList.remove('show');
    }

    const boardBtn = document.getElementById('floating-board-btn');
    if (boardBtn) {
        if (scrollY > 500) boardBtn.classList.add('show');
        else boardBtn.classList.remove('show');
    }
}

document.getElementById('scroll-to-top').addEventListener('click', () => {
    window.scrollTo({top: 0, behavior: 'smooth'});
});

function getTileElement(index) {
    return document.getElementById(`tile-${index}`);
}

function updatePlayerPosition(index) {
    const tile = getTileElement(index);
    if (!tile) return;
    const width = tile.offsetWidth;
    const height = tile.offsetHeight;
    if (playerToken) {
        playerToken.style.left = `${tile.offsetLeft + (width / 2) - 20}px`;
        playerToken.style.top = `${tile.offsetTop + (height / 2) - 20}px`;
        playerToken.classList.remove('player-hop');
        void playerToken.offsetWidth;
        playerToken.classList.add('player-hop');
    }
}

function rollDice() {
    return Math.floor(Math.random() * 3) + 1;
}

function createConfettiGame() {
    const board = document.querySelector('.game-board');
    if (!board) return;
    const colors = ['#FFD1DA', '#FF8FA3', '#7ED321', '#4A90E2', '#F5A623'];
    for (let i = 0; i < 30; i++) {
        const conf = document.createElement('div');
        conf.classList.add('confetti');
        conf.style.left = Math.random() * 100 + '%';
        conf.style.top = '50%';
        conf.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        conf.style.animationDuration = (Math.random() * 2 + 1) + 's';
        board.appendChild(conf);
        setTimeout(() => conf.remove(), 3000);
    }
}

async function gameLoop() {
    while (true) {
        await new Promise(r => setTimeout(r, 1500));
        if (diceEl) diceEl.classList.add('shake-dice');
        await new Promise(r => setTimeout(r, 500));
        if (diceEl) diceEl.classList.remove('shake-dice');
        const steps = rollDice();
        if (diceValue) diceValue.innerText = steps;
        for (let i = 0; i < steps; i++) {
            if (playerPos >= totalTiles - 1) break;
            playerPos++;
            updatePlayerPosition(playerPos);
            await new Promise(r => setTimeout(r, 400));
        }
        if (playerPos >= totalTiles - 1) {
            createConfettiGame();
            await new Promise(r => setTimeout(r, 2000));
            playerPos = 0;
            updatePlayerPosition(playerPos);
        }
    }
}

function initNodes() {
    const isMobile = window.innerWidth < 1200;
    const container = document.getElementById('map-nodes-container');
    const scrollContainer = document.getElementById('map-scroll-container');

    nodeCoordinates.length = 0;
    nodeOffsets.length = 0;

    if (isMobile) {
        scrollContainer.style.overflowX = 'hidden';
        scrollContainer.style.overflowY = 'auto';
        const startY = 450;
        const spacingY = 400;
        const centerX = window.innerWidth / 2;
        container.style.width = '100%';
        container.style.height = `${startY + (totalMapDays * spacingY) + 300}px`;
        for (let i = 0; i <= totalMapDays; i++) {
            nodeCoordinates.push({x: centerX, y: startY + (i * spacingY)});
            nodeOffsets.push({x: 0, y: -240});
        }
    } else {
        scrollContainer.style.overflow = 'hidden';
        scrollContainer.style.overflowY = 'hidden';
        container.style.width = '100%';
        container.style.height = '100vh';
        const centerY = window.innerHeight / 2 + 250;
        const paddingX = 130;
        const availableWidth = window.innerWidth - (paddingX * 2);
        const spacingX = totalMapDays > 1 ? availableWidth / (totalMapDays - 1) : 0;
        for (let i = 0; i <= totalMapDays; i++) {
            let x;
            if (i === 0) x = paddingX - spacingX;
            else x = paddingX + ((i - 1) * spacingX);
            nodeCoordinates.push({x: x, y: centerY});
            nodeOffsets.push({x: 0, y: -240});
        }
    }
}

function createStars() {
    const container = document.getElementById('stars-container');
    container.innerHTML = '';
    for (let i = 0; i < 50; i++) {
        const star = document.createElement('div');
        star.className = 'star-particle';
        const size = Math.random() * 3 + 1;
        star.style.width = `${size}px`;
        star.style.height = `${size}px`;
        star.style.left = `${Math.random() * 100}%`;
        star.style.top = `${Math.random() * 70}%`;
        star.style.animationDelay = `${Math.random() * 2}s`;
        container.appendChild(star);
    }
}

function highlightCurrentStation(day) {
    for (let i = 1; i <= totalMapDays; i++) {
        const btn = document.getElementById(`node-day-${i}`);
        if (btn) btn.classList.remove('active-station-glow');
    }
    const targetBtn = document.getElementById(`node-day-${day}`);
    if (targetBtn) {
        targetBtn.classList.add('active-station-glow');
    }
}

function showDragHint() {
    const hint = document.getElementById('drag-hint');
    if (hint) {
        hint.classList.remove('opacity-0');
        hint.style.opacity = '1';
    }
}

function hideDragHint() {
    const hint = document.getElementById('drag-hint');
    if (hint) {
        hint.style.opacity = '0';
        setTimeout(() => hint.remove(), 500);
    }
}

function updateBackgroundTheme(mode) {
    const bgDay = document.getElementById('bg-layer-day');
    const bgSunset = document.getElementById('bg-layer-sunset');
    const bgNight = document.getElementById('bg-layer-night');
    const ground1 = document.getElementById('ground-path-1');
    const ground2 = document.getElementById('ground-path-2');
    const sun = document.getElementById('celestial-body');
    const stars = document.getElementById('stars-container');

    bgDay.style.opacity = 0;
    bgSunset.style.opacity = 0;
    bgNight.style.opacity = 0;

    if (mode === 0) {
        bgDay.style.opacity = 1;
        if (ground1) ground1.setAttribute('fill', '#8AC9A6');
        if (ground2) ground2.setAttribute('fill', '#A5D6A7');
        sun.className = "absolute -top-10 -right-10 w-[300px] h-[300px] bg-[radial-gradient(circle,_rgba(255,253,150,0.8)_0%,_rgba(255,230,100,0)_70%)] blur-2xl animate-sun-glow transition-all duration-1000";
        sun.style = "";
        stars.style.opacity = 0;
    } else if (mode === 1) {
        bgSunset.style.opacity = 1;
        if (ground1) ground1.setAttribute('fill', '#D8BFA6');
        if (ground2) ground2.setAttribute('fill', '#E0C097');
        sun.className = "absolute top-20 right-20 w-[200px] h-[200px] bg-[radial-gradient(circle,_rgba(255,100,0,0.6)_0%,_rgba(255,50,0,0)_70%)] blur-2xl animate-pulse-slow transition-all duration-1000";
        sun.style = "";
        stars.style.opacity = 0.2;
    } else if (mode === 2) {
        bgNight.style.opacity = 1;
        if (ground1) ground1.setAttribute('fill', '#3E2723');
        if (ground2) ground2.setAttribute('fill', '#4E342E');
        sun.className = "absolute top-10 right-10 transition-all duration-1000";
        sun.style.width = "80px";
        sun.style.height = "80px";
        sun.style.borderRadius = "50%";
        sun.style.backgroundColor = "transparent";
        sun.style.boxShadow = "-15px 8px 0px 0px #FCD34D";
        sun.style.filter = "drop-shadow(0 0 10px rgba(253, 224, 71, 0.6))";
        stars.style.opacity = 1;
    }
}

function initDragScroll() {
    const slider = document.querySelector('#map-view');
    const container = document.querySelector('#map-scroll-container');
    let isDown = false;
    let startX, startY, scrollLeft, scrollTop;
    const removeHint = () => hideDragHint();

    slider.addEventListener('mousedown', (e) => {
        removeHint();
        isDown = true;
        slider.classList.add('cursor-grabbing');
        slider.classList.remove('cursor-grab');
        startX = e.pageX - container.offsetLeft;
        startY = e.pageY - container.offsetTop;
        scrollLeft = container.scrollLeft;
        scrollTop = container.scrollTop;
    });
    slider.addEventListener('mouseleave', () => {
        isDown = false;
        slider.classList.remove('cursor-grabbing');
        slider.classList.add('cursor-grab');
    });
    slider.addEventListener('mouseup', () => {
        isDown = false;
        slider.classList.remove('cursor-grabbing');
        slider.classList.add('cursor-grab');
    });
    slider.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const isMobile = window.innerWidth < 1200;
        if (isMobile) {
            const y = e.pageY - container.offsetTop;
            const walkY = (y - startY) * 1.5;
            container.scrollTop = scrollTop - walkY;
        } else {
            const x = e.pageX - container.offsetLeft;
            const walkX = (x - startX) * 1.5;
            container.scrollLeft = scrollLeft - walkX;
        }
    });
    slider.addEventListener('touchstart', (e) => {
        removeHint();
        isDown = true;
        const touch = e.touches[0];
        startX = touch.pageX - container.offsetLeft;
        startY = touch.pageY - container.offsetTop;
        scrollLeft = container.scrollLeft;
        scrollTop = container.scrollTop;
    });
    slider.addEventListener('touchend', () => {
        isDown = false;
    });
    slider.addEventListener('touchmove', (e) => {
        if (!isDown) return;
        const touch = e.touches[0];
        const isMobile = window.innerWidth < 1200;
        if (isMobile) {
            const y = touch.pageY - container.offsetTop;
            const walkY = (y - startY) * 1.5;
            container.scrollTop = scrollTop - walkY;
            if (Math.abs(y - startY) > 5) e.preventDefault();
        } else {
            const x = touch.pageX - container.offsetLeft;
            const walkX = (x - startX) * 1.5;
            container.scrollLeft = scrollLeft - walkX;
            if (Math.abs(x - startX) > 5) e.preventDefault();
        }
    }, {passive: false});
}

function createAlphabets() {
    const container = document.getElementById('alphabet-container');
    const alphabetCount = 35;
    const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const colors = ['#FF9EAA', '#8AC9A6', '#A3D8F4', '#FBF595', '#fff'];
    for (let i = 0; i < alphabetCount; i++) {
        const letter = document.createElement('div');
        letter.className = 'alphabet-particle';
        const char = letters.charAt(Math.floor(Math.random() * letters.length));
        const size = Math.random() * 40 + 40;
        const left = Math.random() * 100;
        const duration = Math.random() * 15 + 10;
        const delay = Math.random() * 15;
        const color = colors[Math.floor(Math.random() * colors.length)];
        letter.innerText = char;
        letter.style.fontSize = `${size}px`;
        letter.style.color = color;
        letter.style.left = `${left}vw`;
        const initialTop = Math.random() * -200 - 150;
        letter.style.top = `${initialTop}px`;
        letter.style.animation = `fallRotate ${duration}s linear ${delay}s infinite`;
        container.appendChild(letter);
    }
}

function renderMap() {
    // 1) 좌표 배치 (기존 로직 유지)
    applyStationPositionsOnly();

    // 2) 값 주입 + 이벤트 바인딩
    hydrateStationsFromLevelData();

    setTimeout(renderMapPath, 100);
    setTimeout(observeVideos, 200);
}

function applyStationPositionsOnly() {
    const container = document.getElementById('map-nodes-container');
    if (!container) return;

    const winW = window.innerWidth;
    let boxWidth, boxHeight;
    if (winW >= 1500) { boxWidth = 160; boxHeight = 240; }
    else if (winW >= 1000) { boxWidth = 110; boxHeight = 180; }
    else { boxWidth = 96; boxHeight = 145; }

    const halfW = boxWidth / 2;
    const halfH = boxHeight / 2;

    for (let i = 1; i <= totalMapDays; i++) {
        const trackCoords = nodeCoordinates[i];
        const offset = nodeOffsets[i];

        const anchor = document.getElementById(`track-anchor-${i}`);
        const wrap = document.getElementById(`station-wrap-${i}`);

        if (anchor && trackCoords) {
            /*anchor.style.left = `${trackCoords.x}px`;
            anchor.style.top = `${trackCoords.y}px`;*/
        }
        if (wrap && trackCoords && offset) {
            wrap.style.width = `${boxWidth}px`;
            wrap.style.height = `${boxHeight}px`;
            /*wrap.style.left = `${trackCoords.x + offset.x - halfW}px`;
            wrap.style.top = `${trackCoords.y + offset.y - halfH}px`;*/
        }
    }
}

function hydrateStationsFromLevelData() {
    for (let i = 1; i <= totalMapDays; i++) {
        const data = levelData[i];
        const btn = document.getElementById(`node-day-${i}`);
        const wrap = document.getElementById(`station-wrap-${i}`);
        if (!btn || !wrap || !data) continue;

        const isLocked = i > unlockedDays;

        // 텍스트 주입
        const rideEl = btn.querySelector('.station-ride');
        const titleEl = btn.querySelector('.station-title');
        const labelEl = btn.querySelector('.station-label');
        if (rideEl) rideEl.textContent = data.ride || '';
        if (titleEl) titleEl.textContent = data.title || '';
        if (labelEl) labelEl.textContent = `Station ${i}`;

        // video src/poster 주입
        const video = btn.querySelector('video.lazy-video');
        if (video) {
            if (data.video) video.src = data.video;
            if (data.image) video.setAttribute('poster', data.image);
        }

        // stars 주입 (기존 starsHtml 로직 그대로)
        const completedCount = dayProgress[i] ? dayProgress[i].length : 0;
        const starsWrap = btn.querySelector('.station-stars');
        if (starsWrap) {
            let starsHtml = '';
            for (let s = 0; s < 3; s++) {
                const color = s < completedCount ? 'text-yellow-400 drop-shadow-sm' : 'text-gray-300';
                starsHtml += `<i class="fa-solid fa-star text-[8px] ${color}"></i>`;
            }
            starsWrap.innerHTML = starsHtml;
        }

        // locked 처리(기존에 하던 class만 토글)
        if (isLocked) {
            btn.style.cursor = 'not-allowed';
            btn.classList.add('grayscale', 'opacity-70');
            btn.onclick = () => {
                wrap.classList.add('animate-wiggle');
                setTimeout(() => wrap.classList.remove('animate-wiggle'), 500);
            };

            // 잠금이면 play hover 레이어 숨김(원래 JS에서 `${!isLocked ? ... : ''}` 했던 부분)
            const playLayer = btn.querySelector('.station-play-layer');
            if (playLayer) playLayer.remove();
            const badgeIcon = btn.querySelector('.station-badge i');
            if (badgeIcon) badgeIcon.className = 'fa-solid fa-lock text-gray-500';
            btn.querySelector('.station-badge')?.classList.remove('bg-retro-yellow');
            btn.querySelector('.station-badge')?.classList.add('bg-gray-300', 'border-gray-400');
        } else {
            btn.style.cursor = 'pointer';
            btn.classList.remove('grayscale', 'opacity-70');
            btn.onclick = () => openDayIntro(i);
            // unlocked이면 badge를 ticket로 되돌리기 등(필요시)
        }
    }
}



function applyStationLayout() {
    const container = document.getElementById('map-nodes-container');
    if (!container) return;

    const winW = window.innerWidth;
    let boxWidth, boxHeight, halfWidth, halfHeight;

    if (winW >= 1500) { boxWidth = 160; boxHeight = 240; }
    else if (winW >= 1000) { boxWidth = 110; boxHeight = 180; }
    else { boxWidth = 96; boxHeight = 145; }

    halfWidth = boxWidth / 2;
    halfHeight = boxHeight / 2;

    // HTML에 미리 깔아둔 anchor + wrapper를 찾아서 좌표만 반영
    for (let i = 1; i <= totalMapDays; i++) {
        const trackCoords = nodeCoordinates[i];
        const offset = nodeOffsets[i];

        const anchor = document.getElementById(`track-anchor-${i}`);
        const wrap = container.querySelector(`[data-station][data-day="${i}"]`);

        if (anchor && trackCoords) {
            anchor.style.left = `${trackCoords.x}px`;
            anchor.style.top = `${trackCoords.y}px`;
        }

        if (wrap && trackCoords && offset) {
            wrap.style.width = `${boxWidth}px`;
            wrap.style.height = `${boxHeight}px`;
            wrap.style.left = `${trackCoords.x + offset.x - halfWidth}px`;
            wrap.style.top = `${trackCoords.y + offset.y - halfHeight}px`;
        }
    }
}

function bindStations() {
    const stations = document.querySelectorAll('[data-station]');
    stations.forEach((wrap) => {
        const day = Number(wrap.dataset.day);
        const locked = String(wrap.dataset.locked) === '1';
        const btn = wrap.querySelector(`#node-day-${day}`);
        if (!btn) return;

        if (locked) {
            btn.onclick = () => {
                wrap.classList.add('animate-wiggle');
                setTimeout(() => wrap.classList.remove('animate-wiggle'), 500);
            };
            btn.style.cursor = 'not-allowed';
            btn.classList.add('grayscale', 'opacity-70');
        } else {
            btn.onclick = () => openDayIntro(day);
            btn.style.cursor = 'pointer';
            btn.classList.remove('grayscale', 'opacity-70');
        }

        // video src/poster를 dataset에서 적용
        const video = btn.querySelector('video.lazy-video');
        if (video) {
            const src = video.dataset.video;
            const poster = video.dataset.poster;
            if (src) video.src = src;
            if (poster) video.setAttribute('poster', poster);
        }
    });
}



function renderMapPath() {
    const svgTiesShadow = document.getElementById('path-ties-shadow');
    const svgTies = document.getElementById('path-ties');
    const svgRailBase = document.getElementById('path-rail-base');
    const svgRailInner = document.getElementById('path-rail-inner');
    const svgRailCenter = document.getElementById('path-rail-center');
    let d = "";

    if (nodeCoordinates.length > 0) {
        const first = nodeCoordinates[0];
        const last = nodeCoordinates[nodeCoordinates.length - 1];
        const isMobile = window.innerWidth < 1200;

        if (isMobile) {
            d += `M ${first.x} ${first.y - 1000} L ${first.x} ${first.y}`;
        } else {
            d += `M ${first.x - 2000} ${first.y} L ${first.x} ${first.y}`;
        }
        for (let i = 1; i < nodeCoordinates.length; i++) {
            const curr = nodeCoordinates[i];
            d += ` L ${curr.x} ${curr.y}`;
        }
        if (isMobile) {
            d += ` L ${last.x} ${last.y + 1000}`;
        } else {
            d += ` L ${last.x + 2000} ${last.y}`;
        }
    }
    if (svgTiesShadow) svgTiesShadow.setAttribute('d', d);
    if (svgTies) svgTies.setAttribute('d', d);
    if (svgRailBase) svgRailBase.setAttribute('d', d);
    if (svgRailInner) svgRailInner.setAttribute('d', d);
    if (svgRailCenter) svgRailCenter.setAttribute('d', d);
}

// ==========================================
// 6. View Switching & Modals
// ==========================================

function switchView(viewName) {
    const mapView = document.getElementById('map-view');
    mapView.classList.remove('hidden');
    document.getElementById('day-intro-view').classList.add('hidden');
    document.getElementById('drill-view').classList.add('hidden');
    document.getElementById('summary-view').classList.add('hidden');

    if (viewName === 'map') {
        mapView.classList.remove('blur-md', 'brightness-50');
        renderMap();
        if (!pendingDayUnlock) {
            setTimeout(() => {
                const targetDay = currentDay || 1;
                placeTrainAtDay(targetDay);

                const coords = nodeCoordinates[targetDay];
                const offset = nodeOffsets[targetDay] || {x: 0, y: -240};
                const scrollContainer = document.getElementById('map-scroll-container');
                const isMobile = window.innerWidth < 1200;

                if (coords) {
                    if (isMobile) {
                        // 모바일: 여기도 250으로 여백 넉넉히 적용
                        scrollContainer.scrollTop = (coords.y + offset.y) - (scrollContainer.clientHeight / 2) - 250;
                    } else {
                        scrollContainer.scrollLeft = (coords.x + offset.x) - (scrollContainer.clientWidth / 2);
                    }
                }
            }, 200);
        }
    }
    else {
        mapView.classList.add('blur-md', 'brightness-50');
        if (viewName === 'intro') {
            document.getElementById('day-intro-view').classList.remove('hidden');
            renderDayIntro(currentDay);
        } else if (viewName === 'drill') {
            document.getElementById('drill-view').classList.remove('hidden');
        } else if (viewName === 'summary') {
            document.getElementById('summary-view').classList.remove('hidden');
        }
    }
    currentView = viewName;
}


document.addEventListener('click', (e) => {
    const btn = e.target.closest('button[id^="node-day-"]');
    if (!btn) return;

    // 잠김카드는 무시
    if (btn.disabled || btn.getAttribute('aria-disabled') === 'true') return;

    const day = parseInt(btn.id.replace('node-day-', ''), 10);
    if (!Number.isFinite(day)) return;

    currentDay = day;                 // ✅ 핵심: 클릭한 day를 전역에 세팅
    highlightDestination(day);         // (선택) 하이라이트 유지
    switchView('intro');              // ✅ 핵심: 인트로 “모달” 화면으로 전환
});


function openDayIntro(day) {
    currentDay = day;
    const currentInternalDay = stationProgress[day];
    const data = levelData[day];
    document.getElementById('intro-day-number').textContent = currentInternalDay;
    document.getElementById('intro-title').textContent = data.ride;
    document.getElementById('intro-subtitle').textContent = data.title;
    const grid = document.getElementById('mission-grid');

// day 컨테이너만 보여주기
    document.querySelectorAll('#mission-grid .mission-day')
        .forEach(el => el.classList.add('hidden'));

    const dayWrap = document.querySelector(`#mission-grid .mission-day[data-day="${day}"]`);
    if (!dayWrap) return;

    dayWrap.classList.remove('hidden');

// 카드 업데이트만 수행
    dayWrap.querySelectorAll('.mission-card[data-verb-key]').forEach(cardEl => {
        const verbKey = cardEl.getAttribute('data-verb-key');
        applyMissionStyle(cardEl, verbKey);
    });
    updateMissionStamps();
    switchView('intro');
}

function renderDayIntro(day) {
    const data = levelData[day];
    document.getElementById('intro-day-number').textContent = day;
    document.getElementById('intro-title').textContent = data.ride;
    document.getElementById('intro-subtitle').textContent = data.title;
    const grid = document.getElementById('mission-grid');
    grid.innerHTML = '';
    data.verbs.forEach(verbKey => {
        const displayVerb = verbKey.replace(/[0-9]/g, '');
        const isDone = completedVerbs.has(verbKey) || completedDays.has(day);
        let colorClass = "bg-map-pink/10";
        let iconColor = "text-map-pink-dark";
        const v = displayVerb.toLowerCase();
        if (['have', 'love', 'like', 'feel', 'hope', 'dream', 'wish', 'care', 'heal'].some(x => v.includes(x))) {
            colorClass = "bg-red-50";
            iconColor = "text-red-300";
        } else if (['go', 'run', 'walk', 'fly', 'ride', 'swim', 'play', 'travel', 'visit', 'close', 'turn', 'move', 'start', 'leave', 'arrive'].some(x => v.includes(x))) {
            colorClass = "bg-blue-50";
            iconColor = "text-blue-300";
        } else if (['make', 'cook', 'eat', 'drink', 'wash', 'clean', 'brush', 'taste', 'use', 'open', 'fix', 'build'].some(x => v.includes(x))) {
            colorClass = "bg-green-50";
            iconColor = "text-green-300";
        } else {
            colorClass = "bg-yellow-50";
            iconColor = "text-yellow-300";
        }
        let icon = iconMap[v] || iconMap[displayVerb] || "fa-solid fa-star";
        const stampClass = isDone ? 'stamp-completed' : 'hidden';
        const stampHTML = `<div id="stamp-${verbKey}" class="stamp-overlay ${stampClass}"><i class="fa-solid fa-check text-2xl"></i><span class="text-2xl font-bold">Great!</span></div>`;
        const card = document.createElement('div');
        card.className = "relative cursor-pointer bg-white rounded-3xl border border-gray-100 p-4 md:p-6 shadow-lg hover:-translate-y-2 hover:shadow-2xl transition-all flex flex-col items-center group";
        card.onclick = () => startDrill(verbKey);
        card.innerHTML = `
            ${stampHTML}
            <div class="w-full h-24 md:h-32 ${colorClass} rounded-2xl mb-3 md:mb-4 flex items-center justify-center overflow-hidden group-hover:scale-105 transition-transform">
                <i class="${icon} text-4xl md:text-6xl ${iconColor} opacity-70"></i>
            </div>
            <h3 class="font-display text-xl md:text-3xl text-brand-text">${displayVerb.toUpperCase()}</h3>
            <p class="font-bold text-gray-400 mt-1 md:mt-2 text-xs md:text-sm">Mission: ${displayVerb}</p>
        `;
        grid.appendChild(card);
    });
    updateMissionStamps();
}




function backToMap() {
    switchView('map');
}

function exitDrillConfirmation() {
    isAudioPlaying = false;
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
    }
    collectedIndices.clear();
    renderSeedPocket();
    switchView('intro');
}

function updateMissionStamps() {
    completedVerbs.forEach(verb => {
        const stamp = document.getElementById(`stamp-${verb}`);
        if (stamp && stamp.classList.contains('stamp-completed') && !stamp.classList.contains('stamp-visible')) {
            stamp.classList.add('stamp-visible');
            setTimeout(() => {
                stamp.classList.remove('stamp-visible');
            }, 600);
        }
    });
}

// ==========================================
// 7. Drill Logic (Intro, Audio, Cards)
// ==========================================

function startDrill(verbKey) {
    currentVerb = verbKey;
    const displayTitle = String(verbKey || '').replace(/[0-9]/g, '');

    const titleEl = document.getElementById('current-verb-title');
    if (titleEl) titleEl.textContent = displayTitle.toUpperCase();

    collectedIndices.clear();

    // 더미 포함 현재 verb 카드 데이터 확보
    const chunks = getChunksForVerb(currentVerb);

    renderTable();
    renderSeedPocket(chunks.length); // 길이 전달
    updateFoundCount();

    // intro modal이 없거나, index 0 데이터가 없어도 죽지 않게
    if (chunks && chunks.length) {
        openIntroReadingModal(0);
    } else {
        switchView('drill');
    }
}

function openIntroReadingModal(index) {    const modal = document.getElementById("intro-reading-modal");    if (!modal) { switchView("drill"); return; }    const chunks = getChunksForVerb(currentVerb);    if (!chunks || !chunks.length) { switchView("drill"); return; }    introSentences = [];    chunks.forEach(function(data) {        if (!data || !data.eng) return;        var details = getSeedDetail(data.eng);        var sentence = (details && details.basic && details.basic[0] && details.basic[0].example)            ? details.basic[0].example : "I " + data.eng + ".";        introSentences.push({            text: sentence,            eng: data.eng,            kor: data.kor || "",            image: data.image || "./img/exc_n1.png",            audio: data.audio || null        });    });    if (!introSentences.length) { switchView("drill"); return; }    introCurrentIndex = 0;    isIntroPlaying = true;    showIntroCard(0);    updateIntroUI(0);    modal.classList.remove("is-hidden");    setTimeout(function() { playIntroSequence(); }, 500);}function showIntroCard(idx) {    var item = introSentences[idx];    if (!item) return;    var imgEl = document.getElementById("intro-big-img");    var engEl = document.getElementById("intro-big-eng");    var korEl = document.getElementById("intro-big-kor");    if (imgEl) imgEl.src = item.image;    if (engEl) engEl.textContent = item.text;    if (korEl) korEl.textContent = item.kor;}function playIntroSequence() {    if (!isIntroPlaying) return;    if (introCurrentIndex >= introSentences.length) { finishIntroReading(); return; }    var item = introSentences[introCurrentIndex];    showIntroCard(introCurrentIndex);    updateIntroUI(introCurrentIndex + 1);    if (item.audio) {        var audio = new Audio(item.audio);        audio.onended = function() { introCurrentIndex++; if (isIntroPlaying) setTimeout(function() { playIntroSequence(); }, 400); };        audio.onerror = function() { speakIntroTTS(item.text); };        audio.play().catch(function() { speakIntroTTS(item.text); });    } else { speakIntroTTS(item.text); }}function speakIntroTTS(text) {    var ut = new SpeechSynthesisUtterance(text);    ut.lang = "en-US"; ut.rate = 0.85;    ut.onend = function() { introCurrentIndex++; if (isIntroPlaying) setTimeout(function() { playIntroSequence(); }, 400); };    window.speechSynthesis.speak(ut);}function updateIntroUI(current) {    var total = introSentences.length;    document.getElementById("intro-read-count").textContent = current + " / " + total;    var percent = (current / total) * 100;    document.getElementById("intro-progress-bar").style.width = percent + "%";}function skipIntroReading() { window.speechSynthesis.cancel(); finishIntroReading(); }function finishIntroReading() {    isIntroPlaying = false;    document.getElementById("intro-reading-modal").classList.add("is-hidden");    switchView("drill");}
function closeDrillGuide() {
    document.getElementById('drill-guide-modal').classList.add('hidden');
}

function renderTable() {
    const grid = document.getElementById('card-grid');
    if (!grid) return;

    const currentChunks = getChunksForVerb(currentVerb);

    // ✅ Backend-friendly mode:
    // - 카드 DOM을 JS에서 "생성"하지 않습니다.
    // - HTML에 미리 만들어둔 .ui-mini-card(더미)들을 찾아서
    //   데이터(이미지/수집상태/인덱스)만 업데이트합니다.
    //
    // (예외) HTML에 카드가 하나도 없으면, 기존 방식(생성)을 최소한으로 fallback 합니다.
    const cards = Array.from(grid.querySelectorAll('.ui-mini-card'));
    if (cards.length === 0) {
        // fallback: 기존 구현 유지(최소)
        grid.innerHTML = '';
        const rowTop = document.createElement('div');
        rowTop.className = 'flex justify-center gap-4 w-full max-w-lg md:max-w-4xl';
        const rowBottom = document.createElement('div');
        rowBottom.className = 'flex justify-center gap-4 w-full max-w-lg md:max-w-4xl';

        const topRowCount = Math.min(4, currentChunks.length);
        currentChunks.forEach((data, i) => {
            const card = document.createElement('div');
            card.className = 'ui-mini-card mini-card w-[22%] max-w-[140px] aspect-[3/4] card-perspective';
            card.dataset.index = String(i);

            card.addEventListener('click', () => openCard(i));

            const imgSrc = data.image || './img/exc_n1.png';
            const isCollected = collectedIndices.has(i);

            card.innerHTML = `
              <div class="mini-card-inner relative w-full h-full border-2 border-seed-green rounded-2xl shadow-md overflow-hidden bg-white flex flex-col items-center justify-center">
                <div class="mini-card-back absolute inset-0 card-back ${isCollected ? 'hidden' : ''}">
                  <i class="fa-solid fa-star text-white opacity-60 text-3xl"></i>
                </div>
                <div class="mini-card-front absolute inset-0 ${isCollected ? '' : 'hidden'}">
                  <div class="bg-seed-green/10 absolute inset-0"></div>
                  <img src="${imgSrc}" alt="${data.eng || 'Collected'}" class="mini-card-img object-cover w-full h-full z-10 p-1 rounded-2xl" />
                </div>
              </div>
            `;

            if (i < topRowCount) rowTop.appendChild(card);
            else rowBottom.appendChild(card);
        });

        grid.appendChild(rowTop);
        if (currentChunks.length > 4) grid.appendChild(rowBottom);
        renderSeedPocket(currentChunks.length);
        return;
    }

    // 기존 카드(더미) 업데이트
    cards.forEach((card, i) => {
        const data = currentChunks[i];
        const isActive = i < currentChunks.length;

        // 카드 개수가 더 많으면 숨김 처리
        card.classList.toggle('hidden', !isActive);
        if (!isActive) return;

        card.id = `mini-card-${i}`;
        card.dataset.index = String(i);

        // 클릭 바인딩(중복 방지)
        if (!card.dataset.bound) {
            card.addEventListener('click', () => {
                const idx = Number(card.dataset.index || '0');
                openCard(idx);
            });
            card.dataset.bound = '1';
        }

        const isCollected = collectedIndices.has(i);
        card.classList.toggle('collected', isCollected);

        const back = card.querySelector('.mini-card-back');
        const front = card.querySelector('.mini-card-front');
        const img = card.querySelector('.mini-card-img');

        if (back) back.classList.toggle('hidden', isCollected);
        if (front) front.classList.toggle('hidden', !isCollected);

        if (img && data) {
            img.src = data.image || './img/exc_n1.png';
            img.alt = data.eng || 'Collected';
        }
    });

    // 씨앗 슬롯도 카드 수와 맞추기
    renderSeedPocket(currentChunks.length);
}

function renderSeedPocket(totalSlots) {
    const pocket = document.getElementById('seed-pocket');
    if (!pocket) return;

    const slots = Number.isFinite(totalSlots) && totalSlots > 0 ? totalSlots : 7;

    pocket.innerHTML = '';
    for (let i = 0; i < slots; i++) {
        const slot = document.createElement('div');
        slot.id = `seed-slot-${i}`;
        slot.className = collectedIndices.has(i)
            ? 'seed-slot w-10 h-10 border border-seed-green rounded-full flex items-center justify-center bg-seed-green shadow-md scale-110'
            : 'seed-slot w-10 h-10 border border-gray-200 bg-gray-50 rounded-full flex items-center justify-center shadow-inner';
        slot.innerHTML = collectedIndices.has(i)
            ? '<i class="fa-solid fa-seedling text-white text-lg animate-wiggle"></i>'
            : '<i class="fa-solid fa-circle text-gray-200 text-xs"></i>';
        pocket.appendChild(slot);
    }
}

function showUsageTab(tabName) {
    // applied 관련 로직 삭제 (배열에 'basic'만 남김)
    ['basic'].forEach(tab => {
        const tabBtn = document.getElementById(`tab-${tab}`);
        if (tabBtn) tabBtn.classList.toggle('active', tab === tabName);

        const tabContent = document.getElementById(`tab-content-${tab}`);
        if (tabContent) tabContent.classList.toggle('hidden', tab !== tabName);
    });
}

function openCard(index) {
    activeCardIndex = index;
    hasPlayedTop = false;
    hasPlayedBasic = false;
    const data = getChunksForVerb(currentVerb)[index];
    const isCollected = collectedIndices.has(index);

    const adjSelect = document.getElementById('adj-select');
    const adjInput = document.getElementById('adj-custom-input');
    if (adjSelect) adjSelect.value = "";
    if (adjInput) {
        adjInput.value = "";
        adjInput.classList.add('hidden');
    }

    // 1. 텍스트 및 이미지 설정
    document.getElementById('focus-eng').textContent = data.eng;
    document.getElementById('focus-kor').textContent = data.kor;
    document.getElementById('focus-img').src = data.image || `https://placehold.co/600x600/${data.color}/4a4a4a?text=${encodeURIComponent(data.eng.toUpperCase())}`;

    // 2. 데이터 생성
    const detailData = getSeedDetail(data.eng);

    // 3. 테이블 데이터 주입 (★Basic 청크 내용은 그대로 유지★)
    const basicTable = document.getElementById('basic-usage-table');
    if (basicTable) basicTable.innerHTML = generateBasicUsageHTML(detailData.basic, data.eng);

    // 4. 버튼 영역 초기화 (Applied 제거, Basic만 빈 값으로 유지)
    const basicBtnWrapper = document.getElementById('btn-wrapper-basic');
    if (basicBtnWrapper) basicBtnWrapper.innerHTML = '';

    // 5. 탭 초기화
    showUsageTab('basic');


    // -----------------------------

    // 6. 상단(모바일 제외)/좌측 버튼 설정
    const btnContainer = document.getElementById('action-buttons-row');
    const audioBtnHTML = `
        <div id="btn-wrapper-top" class="flex-1 h-full">
            <button id="btn-top-audio" onclick="playFocusAudio('top')" class="btn-guide-effect btn-blue-outline w-full h-full rounded-xl font-bold shadow-sm flex items-center justify-center gap-2">
                <i class="fa-solid fa-volume-high"></i> <span class="text-xs md:text-sm">Listen and Repeat!</span>
            </button>
        </div>
    `;

    let keepBtnHTML = '';
    if (isCollected) {
        keepBtnHTML = `
            <div class="flex-1 h-full">
                <button class="w-full h-full bg-gray-100 text-gray-400 rounded-xl font-bold cursor-not-allowed flex items-center justify-center gap-1 border border-gray-200">
                    <i class="fa-solid fa-check"></i> <span class="text-xs md:text-sm">Done</span>
                </button>
            </div>
        `;
    } else {
        keepBtnHTML = `
            <div class="flex-1 h-full" id="keep-btn-wrapper">
                <button onclick="alert('🎧 Listen and Repeat 버튼을 눌러 소리를 끝까지 들어주세요!')" class="w-full h-full bg-gray-200 text-gray-500 rounded-xl font-bold shadow-inner flex items-center justify-center gap-2 cursor-pointer transition-all hover:bg-gray-300">
                    <i class="fa-solid fa-lock"></i>
                    <span class="text-xs md:text-sm">Listen and Repeat!</span>
                </button>
            </div>
        `;
    }
    btnContainer.innerHTML = audioBtnHTML + keepBtnHTML;

    // 모바일(768px 미만)이면 프리뷰 모달을 띄우고, PC면 바로 기존 학습 모달 띄우기
    if (window.innerWidth < 768) {
        showMobilePreview(data);
    } else {
        document.getElementById('focus-overlay').classList.remove('hidden');
    }
} // ❗ 여기서 openCard 함수를 확실하게 닫아줍니다!


// ==========================================
// 모바일 미리보기 모달 및 TTS 기능 (openCard 함수 밖으로 분리 완료)
// ==========================================

function showMobilePreview(data) {
    const modal = document.getElementById('mobile-card-preview-modal');
    if (!modal) {
        document.getElementById('focus-overlay').classList.remove('hidden');
        return;
    }

    document.getElementById('preview-img').src = data.image || './img/exc_n1.png';
    document.getElementById('preview-eng').textContent = data.eng;
    document.getElementById('preview-kor').textContent = data.kor;

    modal.classList.remove('is-hidden');
}

function closeMobilePreview() {
    // 1. 창을 닫으면 음성 읽어주기도 즉시 정지
    stopPreviewAudio();

    // 2. 프리뷰 모달 숨기기
    const previewModal = document.getElementById('mobile-card-preview-modal');
    if (previewModal) {
        previewModal.classList.add('is-hidden');
    }

    // 3. 본 학습 모달(focus-overlay) 확실하게 띄우기
    const focusOverlay = document.getElementById('focus-overlay');
    if (focusOverlay) {
        focusOverlay.classList.remove('hidden');
        focusOverlay.classList.remove('is-hidden');
        focusOverlay.style.display = 'flex'; // 강제 표시 안전장치
    }
}

// --- 7번 듣기 기능 전역 변수 ---
let previewAudioCount = 0;
let isPreviewAudioPlaying = false;
const MAX_PREVIEW_AUDIO = 7;

// 듣기 버튼 클릭 시 실행
function playPreviewAudio7Times() {
    const text = document.getElementById('preview-eng').textContent;
    if (!text || !('speechSynthesis' in window)) return;

    // 이미 재생 중인데 또 누르면 정지
    if (isPreviewAudioPlaying) {
        stopPreviewAudio();
        return;
    }

    previewAudioCount = 0;
    isPreviewAudioPlaying = true;

    // 버튼 상태 UI 변경
    const btn = document.querySelector('.ui-mobile-preview-listen-btn');
    const countSpan = document.getElementById('preview-listen-count');
    if (btn) btn.classList.add('is-playing');
    if (countSpan) {
        countSpan.classList.remove('is-hidden');
        countSpan.textContent = `1/7`;
    }

    speakPreviewNext(text);
}

// 순차적으로 읽어주는 재귀 함수
function speakPreviewNext(text) {
    if (!isPreviewAudioPlaying || previewAudioCount >= MAX_PREVIEW_AUDIO) {
        stopPreviewAudio();
        return;
    }

    previewAudioCount++;

    const countSpan = document.getElementById('preview-listen-count');
    if (countSpan) countSpan.textContent = `${previewAudioCount}/7`;

    const ut = new SpeechSynthesisUtterance(text);
    ut.lang = 'en-US';
    ut.rate = 0.85;

    ut.onend = function () {
        if (isPreviewAudioPlaying) {
            setTimeout(() => speakPreviewNext(text), 800);
        }
    };

    ut.onerror = function() {
        stopPreviewAudio();
    };

    window.speechSynthesis.speak(ut);
}

// 오디오 강제 종료 및 UI 복구
function stopPreviewAudio() {
    isPreviewAudioPlaying = false;
    previewAudioCount = 0;
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
    }

    const btn = document.querySelector('.ui-mobile-preview-listen-btn');
    const countSpan = document.getElementById('preview-listen-count');
    if (btn) btn.classList.remove('is-playing');
    if (countSpan) countSpan.classList.add('is-hidden');
}


// 여기서부터는 원래 있던 함수들입니다 (수정 안 함)
function closeFocusOverlay() {
    const overlay = document.getElementById('focus-overlay');
    if (overlay) {
        overlay.classList.add('hidden');
        overlay.style.display = '';
    }
    // 오디오 플래그 먼저 꺼서 재귀 콜백 차단
    isAudioPlaying = false;
    isPreviewAudioPlaying = false;
    previewAudioCount = 0;
    if ('speechSynthesis' in window) window.speechSynthesis.cancel();
    // UI 복구
    const audioBtn = document.querySelector('.audio-ui-active');
    if (audioBtn) audioBtn.remove();
    const listenBtn = document.querySelector('.ui-mobile-preview-listen-btn');
    if (listenBtn) listenBtn.classList.remove('is-playing');
    const countSpan = document.getElementById('preview-listen-count');
    if (countSpan) countSpan.classList.add('is-hidden');
    currentAudioTarget = '';
    audioIndex = 0;
}

// Audio Player Logic
function collectCardWithoutClosing() {
    if (activeCardIndex === -1 || collectedIndices.has(activeCardIndex)) return;

    const currentData = getChunksForVerb(currentVerb)[activeCardIndex];
    const imgSrc = currentData.image || "./img/exc_n1.png";
    const gridCard = document.getElementById(`mini-card-${activeCardIndex}`);
    if (!gridCard) return;

    const rect = gridCard.getBoundingClientRect();
    const startX = rect.left + rect.width / 2 - 20;
    const startY = rect.top + rect.height / 2 - 20;

    gridCard.classList.add('collected', 'flipping');
    setTimeout(() => {
        const back = gridCard.querySelector('.mini-card-back');
        const front = gridCard.querySelector('.mini-card-front');
        const img = gridCard.querySelector('.mini-card-img');
        if (back) back.classList.add('hidden');
        if (img) img.src = imgSrc;
        if (front) front.classList.remove('hidden');
    }, 200);
    setTimeout(() => gridCard.classList.remove('flipping'), 500);

    const totalRequired = getChunksForVerb(currentVerb).length;
    const nextSlotIndex = Math.min(collectedIndices.size, Math.max(0, totalRequired - 1));
    flySeedAnimation(startX, startY, nextSlotIndex);
    collectedIndices.add(activeCardIndex);
    updateFoundCount();

    // 모든 카드 수집 완료 시 win 처리 (오디오 종료 대기 없이)
    const totalRequired2 = getChunksForVerb(currentVerb).length;
    if (collectedIndices.size >= totalRequired2) {
        if (!completedVerbs.has(currentVerb)) {
            completedVerbs.add(currentVerb);
            totalStars++;
            totalTrees++;
            if (!dayProgress[currentDay]) dayProgress[currentDay] = [];
            if (!dayProgress[currentDay].includes(currentVerb)) dayProgress[currentDay].push(currentVerb);
        }
        setTimeout(showWinModal, 1200);
    }
}

function playFocusAudio(target) {
    const topBtn = document.getElementById('btn-top-audio');
    if (topBtn) topBtn.classList.remove('btn-guide-effect');
    if (activeCardIndex === -1 || !('speechSynthesis' in window)) return;

    // 클릭 즉시 카드 뒤집기 (모달은 유지)
    collectCardWithoutClosing();

    stopAudioPlayer();

    const chunkEng = getChunksForVerb(currentVerb)[activeCardIndex].eng;
    const detailData = getSeedDetail(chunkEng);

    audioQueue = [];
    audioHighlightIndices = [];

    // 해당 카드 인덱스에 맞는 문장 (I/You/He/She/Do/Don't/Please)을 7번 반복 재생
    var sentence = (detailData.basic[activeCardIndex] || detailData.basic[0]).example;
    for (var i = 0; i < 7; i++) {
        audioQueue.push(sentence);
        audioHighlightIndices.push(activeCardIndex);
    }

    currentAudioTarget = 'top';
    initAudioPlayer();
}

function playTableAudio(type) {
    const topBtn = document.getElementById('btn-top-audio');
    if (topBtn) topBtn.classList.remove('btn-guide-effect');

    const myBtnContainer = document.getElementById(`btn-wrapper-${type}`);
    if (myBtnContainer) {
        const btn = myBtnContainer.querySelector('button');
        if (btn) btn.classList.remove('btn-guide-effect');
    }

    if (activeCardIndex === -1) return;

    stopAudioPlayer();

    setTimeout(() => {
        const chunkEng = getChunksForVerb(currentVerb)[activeCardIndex].eng;
        const detailData = getSeedDetail(chunkEng);
        audioQueue = [];

        if (type === 'basic') {
            detailData.basic.forEach(item => audioQueue.push(item.example));
        }

        currentAudioTarget = type;
        initAudioPlayer();
    }, 50);
}

function renderAudioStartButton(target) {
    const container = document.getElementById(`btn-wrapper-${target}`);
    if (!container) return;

    if (target === 'top') {
        container.innerHTML = `
            <button onclick="playFocusAudio('top')" class="btn-guide-effect btn-blue-outline w-full h-full rounded-xl font-bold shadow-sm flex items-center justify-center gap-2 animate-fade">
                <i class="fa-solid fa-volume-high"></i> <span class="text-xs md:text-sm">Listen and Repeat!</span>
            </button>
        `;
    } else {
        container.innerHTML = `
            <button onclick="playTableAudio('${target}')" class="btn-blue-outline w-full h-12 rounded-xl font-bold shadow-sm flex items-center justify-center gap-2 animate-fade">
                <i class="fa-solid fa-play"></i>
                <span>Listen and Repeat!</span>
            </button>
        `;
    }
}

function initAudioPlayer() {
    window.speechSynthesis.cancel();
    audioIndex = 0;
    isAudioPlaying = true;
    updateAudioUI();
    speakNextChunk();
}

function speakNextChunk() {
    if (!isAudioPlaying) return;
    if (audioIndex >= audioQueue.length) {
        if (currentAudioTarget === 'top') hasPlayedTop = true;
        else if (currentAudioTarget === 'basic') hasPlayedBasic = true;
        stopAudioPlayer();
        return;
    }
    updateAudioUI();
    const text = audioQueue[audioIndex];
    const ut = new SpeechSynthesisUtterance(text);
    ut.lang = 'en-US';
    ut.rate = 0.85;
    ut.onend = function () {
        audioIndex++;
        if (isAudioPlaying) speakNextChunk();
    };
    ut.onerror = function (e) {
        console.error('TTS Error', e);
        stopAudioPlayer();
    };
    window.speechSynthesis.speak(ut);
}

function stopAudioPlayer() {
    isAudioPlaying = false;
    window.speechSynthesis.cancel();

    // 오디오 정지 시 하이라이트 초기화
    const allRows = document.querySelectorAll('[id^="basic-row-"] td');
    allRows.forEach(td => td.classList.remove('text-blue-600', 'font-bold', 'bg-blue-50'));

    // 하단 버튼이 사라졌으므로, 상단 오디오만 끝까지 들으면 Keep 버튼 잠금 해제
    if (hasPlayedTop) {
        unlockKeepButton();
    }

    // 모달이 열려있을 때만 버튼 재렌더링
    const overlay = document.getElementById('focus-overlay');
    if (currentAudioTarget && overlay && !overlay.classList.contains('hidden')) {
        renderAudioStartButton(currentAudioTarget);
    }
    currentAudioTarget = '';
    audioIndex = 0;
}

function unlockKeepButton() {
    if (activeCardIndex === -1) return;
    closeFocusOverlay();

    const totalRequired = getChunksForVerb(currentVerb).length;
    if (collectedIndices.size >= totalRequired) {
        // 모든 카드 완료 → win 처리 (이미 collectCardWithoutClosing에서 처리된 경우 중복 방지)
        if (!completedVerbs.has(currentVerb)) {
            completedVerbs.add(currentVerb);
            totalStars++;
            totalTrees++;
            if (!dayProgress[currentDay]) dayProgress[currentDay] = [];
            if (!dayProgress[currentDay].includes(currentVerb)) dayProgress[currentDay].push(currentVerb);
            setTimeout(showWinModal, 300);
        }
    }
}

function restartAudioPlayer() {
    window.speechSynthesis.cancel();
    audioIndex = 0;
    isAudioPlaying = true;
    updateAudioUI();
    speakNextChunk();
}

function updateAudioUI() {
    const container = document.getElementById(`btn-wrapper-${currentAudioTarget}`);
    if (!container) return;

    // 전체 문장 개수
var totalSentences = audioQueue.length;    var currentSentenceNum = Math.min(audioIndex + 1, totalSentences);
    // 진행 바 게이지 퍼센트
    const percent = ((audioIndex) / audioQueue.length) * 100;

    // 1. 이미 재생 UI가 화면에 렌더링되어 있는지 확인
    const existingUI = container.querySelector('.audio-ui-active');

    if (existingUI) {
        // 이미 렌더링 되어 있다면 HTML을 새로 덮어쓰지 않고 값만 변경 (CSS 애니메이션 작동!)
        const progressFill = existingUI.querySelector('.progress-fill');
        const statusText = existingUI.querySelector('.status-text');

        if (progressFill) progressFill.style.width = `${percent}%`;
        if (statusText) statusText.innerHTML = `<i class="fa-solid fa-volume-high"></i> ${currentSentenceNum} / ${totalSentences}`;
    } else {
        // 처음에만 전체 HTML을 렌더링
        container.innerHTML = `
            <div class="audio-ui-active w-full h-full bg-slate-50 border-2 border-blue-400 rounded-xl px-3 py-1 flex flex-col justify-center gap-1 shadow-[0_0_15px_rgba(59,130,246,0.6)] transition-all duration-300">
                <div class="flex justify-between items-center">
                    <span class="status-text text-xs font-bold text-blue-500 flex items-center gap-1">
                        <i class="fa-solid fa-volume-high"></i>
                        ${currentSentenceNum} / ${totalSentences}
                    </span>
                    <div class="flex gap-2">
                        <button onclick="restartAudioPlayer()" class="w-6 h-6 rounded-full bg-white text-blue-400 hover:text-blue-600 border border-blue-100 flex items-center justify-center transition-colors">
                            <i class="fa-solid fa-rotate-right text-[10px]"></i>
                        </button>
                        <button onclick="stopAudioPlayer()" class="w-6 h-6 rounded-full bg-white text-red-400 hover:text-red-600 border border-red-100 flex items-center justify-center transition-colors">
                            <i class="fa-solid fa-stop text-[10px]"></i>
                        </button>
                    </div>
                </div>
                <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div class="progress-fill h-full bg-blue-500 transition-all duration-500 ease-out" style="width: ${percent}%"></div>
                </div>
            </div>
        `;
    }

    // ---- 하이라이트 적용 로직 ----
    const allRows = document.querySelectorAll('[id^="basic-row-"] td');
    allRows.forEach(td => {
        td.classList.remove('text-blue-600', 'font-bold', 'bg-blue-50');
    });

    if (isAudioPlaying && audioIndex < audioHighlightIndices.length) {
        const activeRowIndex = audioHighlightIndices[audioIndex];
        const activeRow = document.getElementById(`basic-row-${activeRowIndex}`);
        if (activeRow) {
            const td = activeRow.querySelector('td');
            if (td) {
                td.classList.add('text-blue-600', 'font-bold', 'bg-blue-50');
            }
        }
    }
}

function collectCurrentCard(event) {
    if (event) event.stopPropagation();
    const currentData = getChunksForVerb(currentVerb)[activeCardIndex];
    const imgSrc = currentData.image || "./img/exc_n1.png";
    const gridCard = document.getElementById(`mini-card-${activeCardIndex}`);
    const rect = gridCard.getBoundingClientRect();
    const startX = rect.left + rect.width / 2 - 20;
    const startY = rect.top + rect.height / 2 - 20;

    closeFocusOverlay();
    gridCard.classList.add('collected', 'flipping');

    // 애니메이션 중간(200ms)에 앞/뒷면 교체
    setTimeout(() => {
        const back = gridCard.querySelector('.mini-card-back');
        const front = gridCard.querySelector('.mini-card-front');
        const img = gridCard.querySelector('.mini-card-img');
        if (back) back.classList.add('hidden');
        if (img) img.src = imgSrc;
        if (front) front.classList.remove('hidden');
    }, 200);

    setTimeout(() => gridCard.classList.remove('flipping'), 500);

    const totalRequired = getChunksForVerb(currentVerb).length;
    const nextSlotIndex = Math.min(collectedIndices.size, Math.max(0, totalRequired - 1));
    flySeedAnimation(startX, startY, nextSlotIndex);
    collectedIndices.add(activeCardIndex);
    updateFoundCount();

    if (collectedIndices.size >= totalRequired) {

        if (!completedVerbs.has(currentVerb)) {
            completedVerbs.add(currentVerb);
            totalStars++;
            totalTrees++;
            if (!dayProgress[currentDay]) dayProgress[currentDay] = [];
            if (!dayProgress[currentDay].includes(currentVerb)) dayProgress[currentDay].push(currentVerb);
        }
        setTimeout(showWinModal, 1200);
    }
}

function flySeedAnimation(startX, startY, slotIndex) {
    const slot = document.getElementById(`seed-slot-${slotIndex}`);
    if (!slot) return;
    const slotRect = slot.getBoundingClientRect();
    const seed = document.createElement('div');
    seed.className = 'flying-seed w-10 h-10 bg-seed-green rounded-full border border-white flex items-center justify-center text-white shadow-xl z-[100]';
    seed.innerHTML = '<i class="fa-solid fa-seedling"></i>';
    seed.style.left = `${startX}px`;
    seed.style.top = `${startY}px`;
    document.body.appendChild(seed);
    requestAnimationFrame(() => {
        seed.style.left = `${slotRect.left}px`;
        seed.style.top = `${slotRect.top}px`;
        seed.style.transform = 'scale(0.8)';
        seed.style.opacity = '0';
    });
    setTimeout(() => {
        seed.remove();
        slot.className = 'seed-slot w-10 h-10 border border-seed-green rounded-full flex items-center justify-center bg-seed-green shadow-md scale-110';
        slot.innerHTML = `<i class="fa-solid fa-seedling text-white text-lg animate-wiggle"></i>`;
    }, 1000);
}

function updateFoundCount() {
    document.getElementById('found-count').textContent = collectedIndices.size;
}

function showWinModal() {
    const modal = document.getElementById('clear-modal');
    const tree = document.getElementById('success-tree');
    const bird = document.getElementById('success-bird');

    // Tailwind hidden -> 커스텀 클래스
    modal.classList.remove('is-hidden');

    tree.classList.add('animate-grow-tree');
    bird.classList.add('animate-fly-bird');

    confetti({ particleCount: 200, spread: 100, origin: { y: 0.6 } });
}

function finishVerb() {
    document.getElementById('clear-modal').classList.add('is-hidden');
    // 오디오 완전 종료 (unlockKeepButton 재호출 방지)
    isAudioPlaying = false;
    window.speechSynthesis.cancel();
    const verbsForDay = levelData[currentDay].verbs;
    const completedCount = verbsForDay.filter(v => completedVerbs.has(v)).length;
    const totalCount = verbsForDay.length;
    renderDayIntro(currentDay);
    updateMissionStamps();
    switchView('intro');

    if (completedCount >= totalCount) {
        pendingTreeModal = true;
        justCompletedDay = true;

        // Day 완료 DB 저장 + 로컬 Set 업데이트
        completedDays.add(currentDay);
        fetch('/chunking-english/api/progress/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ day_number: currentDay })
        }).catch(() => {}); // 비로그인 시 무시

        setTimeout(() => {
            openTogetherModal();
            setTimeout(() => {
                const tFrame = document.getElementById('together-frame');
                if (tFrame && tFrame.contentWindow && typeof tFrame.contentWindow.openDetail === 'function') {
                    tFrame.contentWindow.openDetail(currentDay);
                }
            }, 300);
        }, 1500);
    }
}

function toggleAdjInput(selectElement) {
    const customInput = document.getElementById('adj-custom-input');
    if (selectElement.value === 'custom') {
        customInput.classList.remove('hidden');
        customInput.focus();
    } else {
        customInput.classList.add('hidden');
        customInput.value = '';
    }
}

// ==========================================
// 8. Together Modal & Grid Logic
// ==========================================

function openTogetherModal() {
    const modal = document.getElementById('together-modal');
    const wrapper = document.getElementById('together-wrapper');
    const frame = document.getElementById('together-frame');
    if (frame) frame.src = "./together.html";
    modal.classList.remove('hidden');
    modal.classList.remove('pointer-events-none');
    modal.classList.add('pointer-events-auto');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        if (wrapper) {
            wrapper.classList.remove('scale-90');
            wrapper.classList.add('scale-100');
        }
    }, 50);
}

function renderTogetherGrid() {
    const grid = document.getElementById('together-grid');
    grid.innerHTML = '';

    const completedDaysArray = Array.from(completedDays);
    const completedDaysSet = completedDays;
    const maxCompletedDay = completedDaysArray.length > 0 ? Math.max(...completedDaysArray) : 0;
    const accessibleUpTo = Math.max(maxCompletedDay + 1, 1); // 완료된 다음 day(현재 진행중)까지 접근 가능

    for (let i = 1; i <= 250; i++) {
        const btn = document.createElement('button');
        const isCompleted = completedDaysSet.has(i);
        const isAccessible = i <= accessibleUpTo;

        if (isCompleted) {
            // 완료된 day: 컬러풀 + 별 3개
            btn.className = "relative group bg-gradient-to-br from-pink-50 to-rose-100 border-2 border-pink-300 rounded-2xl p-3 h-28 flex flex-col justify-between hover:border-pink-400 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer";
            btn.onclick = () => openTogetherDetail(i);
            btn.innerHTML = `
                <div class="w-full flex justify-between items-start">
                    <span class="text-[10px] font-bold text-pink-400 uppercase tracking-wider">Day</span>
                    <div class="flex gap-0.5">
                        <i class="fa-solid fa-star text-yellow-400 text-[10px] drop-shadow-sm"></i>
                        <i class="fa-solid fa-star text-yellow-400 text-[10px] drop-shadow-sm"></i>
                        <i class="fa-solid fa-star text-yellow-400 text-[10px] drop-shadow-sm"></i>
                    </div>
                </div>
                <span class="text-3xl font-display text-pink-500 self-center group-hover:scale-110 transition-transform">${i}</span>
                <div class="w-full h-1.5 bg-pink-100 rounded-full overflow-hidden mt-1">
                    <div class="h-full bg-gradient-to-r from-pink-400 to-rose-400 w-full rounded-full"></div>
                </div>
            `;
        } else if (isAccessible) {
            // 접근 가능하지만 미완료 (현재 진행중인 day)
            btn.className = "relative group bg-white border-2 border-gray-200 rounded-2xl p-3 h-28 flex flex-col justify-between hover:border-pink-300 hover:shadow-md hover:-translate-y-1 transition-all duration-300 cursor-pointer";
            btn.onclick = () => openTogetherDetail(i);
            btn.innerHTML = `
                <div class="w-full flex justify-between items-start">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Day</span>
                    <div class="flex gap-0.5">
                        <i class="fa-regular fa-star text-gray-300 text-[10px]"></i>
                        <i class="fa-regular fa-star text-gray-300 text-[10px]"></i>
                        <i class="fa-regular fa-star text-gray-300 text-[10px]"></i>
                    </div>
                </div>
                <span class="text-3xl font-display text-gray-400 self-center">${i}</span>
                <div class="w-full h-1 bg-gray-100 rounded-full mt-1"></div>
            `;
        } else {
            // 잠긴 day: 흑백 + 클릭 불가
            btn.className = "bg-gray-50 border-2 border-gray-100 rounded-2xl p-3 h-28 flex flex-col justify-center items-center opacity-50 cursor-not-allowed grayscale";
            btn.disabled = true;
            btn.innerHTML = `
                <i class="fa-solid fa-lock text-gray-300 text-xl mb-1"></i>
                <span class="text-lg font-display text-gray-300">${i}</span>
            `;
        }
        grid.appendChild(btn);
    }
}

function showTogetherGrid() {
    document.getElementById('together-detail-view').classList.add('hidden');
    document.getElementById('together-grid-view').classList.remove('hidden');
}

function openTogetherDetail(day) {
    currentReviewDay = day;
    document.getElementById('together-grid-view').classList.add('hidden');
    document.getElementById('together-detail-view').classList.remove('hidden');
    document.getElementById('together-detail-title').textContent = `Day ${day} Review`;
    generateTogetherSentences(day);
}

function generateTogetherSentences(day) {
    const container = document.getElementById('together-sentences-container');
    container.innerHTML = '';
    const sentences = togetherData[day] || [
        {eng: "Coming Soon...", kor: "준비 중입니다..."},
        {eng: "Keep Learning!", kor: "계속 학습하세요!"},
        {eng: "You can do it!", kor: "당신은 할 수 있어요!"}
    ];
    sentences.forEach((item, index) => {
        const card = document.createElement('div');
        card.className = "together-sentence-card group";
        card.innerHTML = `
            <div class="w-12 h-12 rounded-full bg-map-pink-dark text-white flex items-center justify-center font-bold text-xl shadow-md shrink-0">
                ${index + 1}
            </div>
            <div class="flex-1">
                <div class="font-display text-2xl text-brand-text mb-1 group-hover:text-map-pink-dark transition-colors">
                    ${item.eng}
                </div>
                <div class="font-body text-gray-500 font-bold text-sm">
                    ${item.kor}
                </div>
            </div>
            <button onclick="playText('${item.eng.replace(/'/g, "\\'")}')" class="w-10 h-10 rounded-full bg-gray-100 hover:bg-map-pink hover:text-white transition-colors flex items-center justify-center text-gray-400">
                <i class="fa-solid fa-volume-high"></i>
            </button>
        `;
        container.appendChild(card);
    });
}

function playText(text) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        const ut = new SpeechSynthesisUtterance(text);
        ut.lang = 'en-US';
        ut.rate = 0.8;
        window.speechSynthesis.speak(ut);
    }
}

function closeTogetherModal() {
    const modal = document.getElementById('together-modal');
    const wrapper = document.getElementById('together-wrapper');
    const frame = document.getElementById('together-frame');
    modal.classList.remove('pointer-events-auto');
    modal.classList.add('pointer-events-none');
    modal.classList.add('opacity-0');
    if (wrapper) {
        wrapper.classList.remove('scale-100');
        wrapper.classList.add('scale-90');
    }
    setTimeout(() => {
        modal.classList.add('hidden');
        if (frame) frame.src = "";
        if (pendingTreeModal) {
            openTreeModal();
        }
    }, 500);
}

function initTogetherModal() {
    renderTogetherGrid();
    document.getElementById('together-modal').classList.remove('hidden');
    confetti({particleCount: 150, spread: 100, origin: {y: 0.3}, colors: ['#FF9EAA', '#8AC9A6', '#FBF595']});
}

// ==========================================
// 9. Tree (PHP) Modal Logic
// ==========================================

// 기존 로직 유지 + tailwind용 클래스(scale-90/100, opacity-0, hidden) 제거 버전
// 전제: tree-php-modal는 기본적으로 "is-hidden is-faded"를 가지고 시작
//       (CSS에서 .is-hidden{display:none} .is-faded{opacity:0;pointer-events:none} .is-visible{opacity:1;pointer-events:auto})
//       tree-php-wrapper는 CSS에서 기본 transform:scale(.9) / .is-visible일 때 scale(1) 처리

function openTreeModal() {
    const modal = document.getElementById('tree-php-modal');
    const frame = document.getElementById('tree-php-frame');

    if (frame) frame.src = "./tree.php";

    // 표시
    modal.classList.remove('is-hidden');
    modal.setAttribute('aria-hidden', 'false');

    // 페이드/스케일 인
    requestAnimationFrame(() => {
        modal.classList.remove('is-faded');
        modal.classList.add('is-visible');
    });
}

function closeTreeModal() {
    const modal = document.getElementById('tree-php-modal');
    const frame = document.getElementById('tree-php-frame');

    // 페이드/스케일 아웃
    modal.classList.remove('is-visible');
    modal.classList.add('is-faded');
    modal.setAttribute('aria-hidden', 'true');

    setTimeout(() => {
        modal.classList.add('is-hidden');
        if (frame) frame.src = "";

        // ===== 아래 로직은 원본 그대로 유지 =====
        if (justCompletedDay) {
            justCompletedDay = false;
            pendingTreeModal = false;
            const nextDay = currentDay + 1;

            if (nextDay <= totalMapDays) {
                if (unlockedDays < nextDay) {
                    unlockedDays = nextDay;
                    renderMap();
                }
                switchView('map');
                animateTrain(currentDay, nextDay, false, () => {
                    currentDay = nextDay;
                    setTimeout(() => {
                        openDayIntro(nextDay);
                    }, 500);
                });
            } else {
                alert("축하합니다! 모든 영어 여행을 마쳤습니다! 🎉");
                switchView('map');
            }
        } else {
            switchView('map');
        }
    }, 500);
}

// ==========================================
// 10. Tree Canvas Animation Logic
// ==========================================
function initTitle() {
    const titleText = "My English Tree";
    const container = document.getElementById("tree-ui-layer");
    if (!container) return;
    container.innerHTML = '';
    [...titleText].forEach((char, index) => {
        const span = document.createElement("span");
        span.textContent = char === " " ? "\u00A0" : char;
        span.className = "title-letter";
        span.style.animationDelay = `${index * 0.15}s`;
        container.appendChild(span);
    });
}

function resize() {
    if (!treeCanvas) return;
    treeCanvas.width = window.innerWidth;
    treeCanvas.height = window.innerHeight;
    centerX = treeCanvas.width / 2;
    centerY = treeCanvas.height * (window.innerWidth < 768 ? 0.75 : 0.8);
    trainState.x = -trainState.width * 2;
    initWorld();
}

function onTreeMouseMove(e) {
    const rect = treeCanvas.getBoundingClientRect();
    mouse.x = e.clientX - rect.left;
    mouse.y = e.clientY - rect.top;
}

function onTreeTouchMove(e) {
    const rect = treeCanvas.getBoundingClientRect();
    if (e.touches.length > 0) {
        mouse.x = e.touches[0].clientX - rect.left;
        mouse.y = e.touches[0].clientY - rect.top;
    }
}

function onTreeClick(e) {
    const rect = treeCanvas.getBoundingClientRect();
    const clickX = e.clientX - rect.left;
    const clickY = e.clientY - rect.top;

    for (let i = ornaments.length - 1; i >= 0; i--) {
        const o = ornaments[i];
        if (dist(clickX, clickY, o.x, o.y) < o.size + 15) {
            closeTreeModal();
            currentDay = o.day;
            switchView('map');
            placeTrainAtDay(currentDay);

            const coords = nodeCoordinates[currentDay];
            const offset = nodeOffsets[currentDay] || {x: 0, y: -240};
            const scrollContainer = document.getElementById('map-scroll-container');
            const isMobile = window.innerWidth < 1200;

            if (coords) {
                if (isMobile) {
                    scrollContainer.scrollTo({
                        top: (coords.y + offset.y) - (scrollContainer.clientHeight / 2),
                        behavior: 'smooth'
                    });
                } else {
                    scrollContainer.scrollTo({
                        left: (coords.x + offset.x) - (scrollContainer.clientWidth / 2),
                        behavior: 'smooth'
                    });
                }
            }
            return;
        }
    }
}

class Particle {
    constructor() {
        this.reset();
    }

    reset() {
        this.x = Math.random() * treeCanvas.width;
        this.y = Math.random() * treeCanvas.height;
        this.vx = rand(-0.2, 0.2);
        this.vy = rand(-0.2, 0.2);
        this.size = rand(0.5, 2.5);
        this.alpha = rand(0.1, 0.7);
    }

    update() {
        this.x += this.vx;
        this.y += this.vy;
        if (this.x < 0 || this.x > treeCanvas.width || this.y < 0 || this.y > treeCanvas.height) this.reset();
    }

    draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(255, 255, 255, ${this.alpha})`;
        ctx.shadowBlur = 5;
        ctx.shadowColor = "white";
        ctx.fill();
        ctx.shadowBlur = 0;
    }
}

function drawGrass() {
    ctx.save();
    const grassGradient = ctx.createLinearGradient(0, centerY - 50, 0, treeCanvas.height);
    grassGradient.addColorStop(0, "rgba(164, 224, 104, 0.8)");
    grassGradient.addColorStop(1, "rgba(108, 171, 69, 0.8)");
    ctx.fillStyle = grassGradient;
    ctx.beginPath();
    ctx.moveTo(0, treeCanvas.height);
    ctx.lineTo(0, centerY + 30);
    ctx.bezierCurveTo(treeCanvas.width * 0.25, centerY - 60, treeCanvas.width * 0.75, centerY - 20, treeCanvas.width, centerY + 40);
    ctx.lineTo(treeCanvas.width, treeCanvas.height);
    ctx.closePath();
    ctx.fill();
    ctx.restore();
}

function getHillPoints() {
    return {
        p0: {x: 0, y: centerY + 30},
        cp1: {x: treeCanvas.width * 0.25, y: centerY - 60},
        cp2: {x: treeCanvas.width * 0.75, y: centerY - 20},
        p3: {x: treeCanvas.width, y: centerY + 40}
    };
}

function getHillY(x) {
    let t = Math.max(0, Math.min(1, x / treeCanvas.width));
    let invT = 1 - t;
    const p = getHillPoints();
    let y = Math.pow(invT, 3) * p.p0.y + 3 * Math.pow(invT, 2) * t * p.cp1.y + 3 * invT * Math.pow(t, 2) * p.cp2.y + Math.pow(t, 3) * p.p3.y;
    return y;
}

function updateTrain() {
    if (!trainLoaded) return;
    trainState.x += trainState.speed;
    if (trainState.x > treeCanvas.width + trainState.width) {
        trainState.x = -trainState.width;
    }
    const trainCenterX = trainState.x + trainState.width / 2;
    const groundY = getHillY(trainCenterX);
    trainState.y = groundY - trainState.height * 0.85;
    const nextX = trainCenterX + 5;
    const nextGroundY = getHillY(nextX);
    const dx = nextX - trainCenterX;
    const dy = nextGroundY - groundY;
    trainState.angle = Math.atan2(dy, dx);
}

function drawTrain() {
    if (!trainLoaded) return;
    ctx.save();
    const centerX = trainState.x + trainState.width / 2;
    const centerY = trainState.y + trainState.height / 2;
    ctx.translate(centerX, centerY);
    ctx.rotate(trainState.angle);
    ctx.shadowColor = "rgba(0,0,0,0.3)";
    ctx.shadowBlur = 10;
    ctx.shadowOffsetY = 5;
    ctx.drawImage(trainImg, -trainState.width / 2, -trainState.height / 2, trainState.width, trainState.height);
    ctx.restore();
}

class Ornament {
    constructor(x, y, day) {
        this.x = x;
        this.y = y;
        this.day = day;
        this.isLastDay = false;
        this.showBadge = false;
        if (day <= 15) {
            this.color = colorsLevel1[Math.floor(Math.random() * colorsLevel1.length)];
            this.type = 0;
        } else if (day <= 30) {
            this.color = colorsLevel2[Math.floor(Math.random() * colorsLevel2.length)];
            this.type = 3;
        } else {
            this.color = colorsLevel3[Math.floor(Math.random() * colorsLevel3.length)];
            this.type = 2;
        }
        this.baseSize = rand(14, 18);
        this.size = this.baseSize;
        this.alpha = 0;
        this.maxAlpha = rand(0.9, 1.0);
        this.fadeInSpeed = 0.04;
        this.blinkOffset = rand(0, Math.PI * 2);
        this.blinkSpeed = rand(0.02, 0.05);
        this.hoverScale = 1.0;
        this.pulse = 0;
    }

    update() {
        if (this.alpha < this.maxAlpha) {
            this.alpha += this.fadeInSpeed;
        }
        const d = dist(mouse.x, mouse.y, this.x, this.y);
        if (d < this.size + 15) {
            isHovering = true;
            this.hoverScale += (1.4 - this.hoverScale) * 0.1;
            shakeCenter.x = this.x;
            shakeCenter.y = this.y;
            shakeIntensity = 1.0;
        } else {
            this.hoverScale += (1.0 - this.hoverScale) * 0.1;
        }
        if (this.showBadge) {
            this.pulse += 0.05;
        }
    }

    draw() {
        const twinkle = 0.85 + 0.15 * Math.sin(Date.now() * this.blinkSpeed * 0.1 + this.blinkOffset);
        const currentAlpha = Math.min(this.alpha, this.maxAlpha) * twinkle;
        if (currentAlpha <= 0) return;
        ctx.save();
        ctx.translate(this.x, this.y);
        ctx.scale(this.hoverScale, this.hoverScale);
        ctx.shadowBlur = 20;
        ctx.shadowColor = this.color;
        ctx.globalAlpha = currentAlpha;
        const grad = ctx.createRadialGradient(-this.size * 0.3, -this.size * 0.3, this.size * 0.1, 0, 0, this.size);
        grad.addColorStop(0, "rgba(255, 255, 255, 0.9)");
        grad.addColorStop(0.2, "rgba(255, 255, 255, 0.4)");
        grad.addColorStop(1, this.color);
        ctx.fillStyle = grad;
        ctx.beginPath();
        if (this.type === 0) {
            ctx.arc(0, 0, this.size, 0, Math.PI * 2);
        } else if (this.type === 2) {
            for (let i = 0; i < 5; i++) {
                ctx.lineTo(Math.cos((18 + i * 72) * Math.PI / 180) * (this.size * 1.2), -Math.sin((18 + i * 72) * Math.PI / 180) * (this.size * 1.2));
                ctx.lineTo(Math.cos((54 + i * 72) * Math.PI / 180) * (this.size * 0.5), -Math.sin((54 + i * 72) * Math.PI / 180) * (this.size * 0.5));
            }
        } else {
            for (let i = 0; i < 6; i++) {
                ctx.lineTo(this.size * Math.cos(i * 2 * Math.PI / 6), this.size * Math.sin(i * 2 * Math.PI / 6));
            }
        }
        ctx.closePath();
        ctx.fill();
        ctx.shadowBlur = 0;
        ctx.fillStyle = "rgba(255,255,255,0.1)";
        ctx.fill();
        ctx.globalAlpha = 1.0;
        ctx.fillStyle = "#1a1a2e";
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.font = "bold 8px sans-serif";
        ctx.fillText("DAY", 0, -5);
        ctx.font = "800 11px sans-serif";
        ctx.fillText(this.day, 0, 6);
        ctx.restore();
    }
}

class Branch {
    constructor(x, y, angle, thickness, depth, isRoot = false) {
        this.x = x;
        this.y = y;
        this.angle = angle;
        this.baseAngle = angle;
        this.thickness = thickness;
        this.depth = depth;
        this.isRoot = isRoot;
        this.maxLength = isRoot ? rand(30, 50) : rand(40, 70);
        this.length = this.maxLength;
        this.children = [];
        if (this.isRoot) {
            this.color = `rgb(${40 + rand(-5, 5)}, ${25 + rand(-5, 5)}, ${20 + rand(-5, 5)})`;
        } else {
            const baseR = 60 + (6 - depth) * 15;
            const baseG = 40 + (6 - depth) * 10;
            const baseB = 30 + (6 - depth) * 5;
            this.color = `rgb(${baseR}, ${baseG}, ${baseB})`;
        }
        this.spawnChildren();
    }

    tryAddSpot() {
        let zone = '';
        const groundLevel = centerY;
        const ty = this.endY();
        const tx = this.endX();
        const boundaryLow = groundLevel - 150;
        const boundaryHigh = groundLevel - 300;
        if (ty > boundaryLow) {
            zone = 'ROOT';
        } else if (ty > boundaryHigh) {
            zone = 'MIDDLE';
        } else {
            zone = 'TOP';
        }
        if (ty < 0 || tx < 0 || tx > treeCanvas.width) return;
        let collision = false;
        for (let p of reservedPositions) {
            if (dist(tx, ty, p.x, p.y) < MIN_DISTANCE) {
                collision = true;
                break;
            }
        }
        if (!collision) {
            const spot = {x: tx, y: ty};
            availableSpots[zone].push(spot);
            reservedPositions.push(spot);
        }
    }

    spawnChildren() {
        this.tryAddSpot();
        if (this.depth > 0) {
            let childrenAngles = [];
            if (this.isRoot) {
                childrenAngles = [rand(-0.4, 0.4)];
            } else if (this.depth >= 5) {
                childrenAngles = [-0.5, 0.5];
            } else if (this.depth >= 3) {
                childrenAngles = Math.random() > 0.3 ? [-0.4, 0.4] : [-0.4, 0.1, 0.5];
            } else {
                childrenAngles = Math.random() < 0.6 ? [rand(-0.4, 0.4)] : [rand(-0.5, 0.5), rand(-0.3, 0.3)];
            }
            childrenAngles.forEach(spread => {
                const nextThickness = this.thickness * 0.7;
                this.children.push(new Branch(this.endX(), this.endY(), this.angle + spread, nextThickness, this.depth - 1, this.isRoot));
            });
        }
    }

    endX(angleOverride) {
        return this.x + Math.cos(angleOverride ?? this.angle) * this.length;
    }

    endY(angleOverride) {
        return this.y + Math.sin(angleOverride ?? this.angle) * this.length;
    }

    draw() {
        let sway = 0;
        if (shakeIntensity > 0.01) {
            const d = dist(this.x, this.y, shakeCenter.x, shakeCenter.y);
            const radius = 250;
            if (d < radius && !this.isRoot) {
                const power = (1 - d / radius) * shakeIntensity;
                sway = Math.sin(Date.now() * 0.008 + this.depth) * 0.15 * power;
            }
        }
        const visualAngle = this.angle + sway;
        const ex = this.endX(visualAngle);
        const ey = this.endY(visualAngle);
        ctx.beginPath();
        ctx.moveTo(this.x, this.y);
        ctx.lineTo(ex, ey);
        ctx.strokeStyle = this.color;
        ctx.lineWidth = this.thickness;
        ctx.lineCap = "round";
        ctx.lineJoin = "round";
        ctx.stroke();
        this.children.forEach(c => c.draw());
    }

    update() {
        this.children.forEach(c => c.update());
    }
}

function initWorld() {
    branches = [];
    roots = [];
    ornaments = [];
    availableSpots = {ROOT: [], MIDDLE: [], TOP: []};
    reservedPositions = [];
    currentDayAnim = 1;
    particles = Array.from({length: 80}, () => new Particle());
    branches.push(new Branch(centerX, centerY, -Math.PI / 2, 60, 7, false));
    for (let i = 0; i < 3; i++) roots.push(new Branch(centerX, centerY, Math.PI / 2 + rand(0.5, 1.2), rand(25, 35), 3, true));
    for (let i = 0; i < 3; i++) roots.push(new Branch(centerX, centerY, Math.PI / 2 - rand(0.5, 1.2), rand(25, 35), 3, true));
    roots.push(new Branch(centerX, centerY, Math.PI / 2, rand(35, 50), 4, true));
}

let spawnTimer = 0;

function spawnManager() {
    if (currentDayAnim > maxDays) return;
    spawnTimer++;
    if (spawnTimer < 2) return;
    spawnTimer = 0;
    let targetZone = '';
    if (currentDayAnim <= 15) targetZone = 'ROOT';
    else if (currentDayAnim <= 30) targetZone = 'MIDDLE';
    else targetZone = 'TOP';
    let spots = availableSpots[targetZone];
    if (spots && spots.length > 0) {
        const idx = Math.floor(Math.random() * spots.length);
        const spot = spots.splice(idx, 1)[0];
        ornaments.push(new Ornament(spot.x, spot.y, currentDayAnim));
        currentDayAnim++;
    }
}

function animate() {
    if (!ctx) return;
    ctx.clearRect(0, 0, treeCanvas.width, treeCanvas.height);
    isHovering = false;
    shakeIntensity *= 0.95;
    if (shakeIntensity < 0.01) shakeIntensity = 0;
    particles.forEach(p => {
        p.update();
        p.draw();
    });
    drawGrass();
    updateTrain();
    drawTrain();
    roots.forEach(r => {
        r.update();
        r.draw();
    });
    branches.forEach(b => {
        b.update();
        b.draw();
    });
    spawnManager();
    ornaments.forEach(o => {
        o.update();
        o.draw();
    });
    treeCanvas.style.cursor = isHovering ? "pointer" : "default";
    treeAnimationId = requestAnimationFrame(animate);
}

// Utility
const rand = (min, max) => Math.random() * (max - min) + min;
const dist = (x1, y1, x2, y2) => Math.sqrt((x2 - x1) ** 2 + (y2 - y1) ** 2);


// ==========================================
// 11. Custom Smooth Scroll & Train Animation
// ==========================================

function customSmoothScroll(element, target, duration, isHorizontal) {
    const start = isHorizontal ? element.scrollLeft : element.scrollTop;
    const change = target - start;
    const startTime = performance.now();

    function animate(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const ease = progress < 0.5 ? 4 * progress * progress * progress : 1 - Math.pow(-2 * progress + 2, 3) / 2;
        const position = start + (change * ease);
        if (isHorizontal) element.scrollLeft = position;
        else element.scrollTop = position;
        if (elapsed < duration) requestAnimationFrame(animate);
    }

    requestAnimationFrame(animate);
}

function centerMapOnStation(day, isInstant = false) {
    const coords = nodeCoordinates[day];
    const offset = nodeOffsets[day] || {x: 0, y: -240};
    if (!coords) return;
    const scrollContainer = document.getElementById('map-scroll-container');
    const isMobile = window.innerWidth < 1200;

    if (isMobile) {
        const actualY = coords.y + offset.y;
        // 여백 보정값을 120 -> 250으로 넉넉하게 늘려줍니다.
        let targetScrollTop = actualY - (scrollContainer.clientHeight / 2) - 250;

        if (isInstant) scrollContainer.scrollTo({top: targetScrollTop, behavior: 'auto'});
        else customSmoothScroll(scrollContainer, targetScrollTop, 2500, false);
    } else {
        const actualX = coords.x + offset.x;
        let targetScrollLeft = actualX - (scrollContainer.clientWidth / 2);

        if (isInstant) scrollContainer.scrollTo({left: targetScrollLeft, behavior: 'auto'});
        else customSmoothScroll(scrollContainer, targetScrollLeft, 2000, true);
    }
}

function completeDayAndReturnToMap() {
    switchView('map');
    const currentMax = stationMaxDays[currentDay];
    if (stationProgress[currentDay] < currentMax) stationProgress[currentDay]++;
    else console.log(`Station ${currentDay} Completed!`);
    placeTrainAtDay(currentDay);
    highlightDestination(currentDay);
    pendingDayUnlock = false;
}

function placeTrainAtDay(day) {
    const train = document.getElementById('park-train');
    const coords = nodeCoordinates[day];
    if (!coords) return;
    if (window.innerWidth < 768) {
        train.classList.add('hidden');
        train.classList.remove('flex');
    } else {
        train.classList.remove('hidden');
        train.classList.add('flex');
    }
    train.style.transform = `translate(${coords.x}px, ${coords.y}px) translate(-50%, -65%)`;
}

function highlightDestination(day) {
    for (let i = 1; i <= totalMapDays; i++) {
        const nodeBtn = document.getElementById(`node-day-${i}`);
        if (nodeBtn) {
            const boxFrame = nodeBtn.querySelector('.station-box-frame');
            if (boxFrame) boxFrame.classList.remove('active-destination-box');
        }
    }
    if (day > 0 && day <= totalMapDays) {
        const targetBtn = document.getElementById(`node-day-${day}`);
        if (targetBtn) {
            const boxFrame = targetBtn.querySelector('.station-box-frame');
            if (boxFrame) boxFrame.classList.add('active-destination-box');
        }
    }
}

function animateTrain(fromDay, toDay, isInitialLoad = false, onComplete) {
    const train = document.getElementById('park-train');
    const trainBody = document.getElementById('train-body');
    const trainImg = train.querySelector('img');
    if (window.innerWidth < 768) {
        train.classList.add('hidden');
        train.classList.remove('flex');
    } else {
        train.classList.remove('hidden');
        train.classList.add('flex');
    }
    highlightDestination(0);
    if (trainBody) trainBody.classList.remove('train-glowing');
    let startTime = null;
    const duration = isInitialLoad ? 2000 : 4000;
    const linearEase = t => t;

    function step(timestamp) {
        if (!startTime) startTime = timestamp;
        const rawProgress = Math.min((timestamp - startTime) / duration, 1);
        const progress = linearEase(rawProgress);
        const totalSegments = toDay - fromDay;
        const currentGlobalT = progress * totalSegments;
        const segmentIdx = Math.floor(currentGlobalT);
        const segmentT = currentGlobalT - segmentIdx;
        const startNodeIdx = fromDay + segmentIdx;
        const endNodeIdx = startNodeIdx + 1;
        if (startNodeIdx >= nodeCoordinates.length - 1) return;
        let p0 = nodeCoordinates[startNodeIdx];
        let p1 = nodeCoordinates[endNodeIdx];
        if (!p1) return;
        const x = p0.x + (p1.x - p0.x) * segmentT;
        const y = p0.y + (p1.y - p0.y) * segmentT;
        train.style.transform = `translate(${x}px, ${y}px) translate(-50%, -65%)`;
        if (p1.x < p0.x) trainImg.style.transform = 'scaleX(-1)';
        else trainImg.style.transform = 'scaleX(1)';
        if (rawProgress < 1) {
            requestAnimationFrame(step);
        } else {
            if (!isInitialLoad) renderMap();
            highlightDestination(toDay);
            if (trainBody) trainBody.classList.add('train-glowing');
            setTimeout(() => {
                centerMapOnStation(toDay);
            }, 100);
            if (onComplete) onComplete();
        }
    }

    requestAnimationFrame(step);
}

const videoObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        const video = entry.target;
        if (entry.isIntersecting) {
            const playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    console.log("Auto-play prevented");
                });
            }
        } else {
            video.pause();
        }
    });
}, {root: document.getElementById('map-scroll-container'), threshold: 0.5});

function observeVideos() {
    const videos = document.querySelectorAll('.lazy-video');
    videos.forEach(video => {
        videoObserver.observe(video);
    });
}

// ==========================================
// 12. Guide Bubble Logic
// ==========================================

function initGuideBubble() {
    const bubble = document.getElementById('guide-bubble');
    const dotsContainer = document.getElementById('guide-dots');
    if (!bubble || !dotsContainer) return;
    dotsContainer.innerHTML = '';
    guideStepsData.forEach((_, idx) => {
        const dot = document.createElement('div');
        dot.className = `guide-dot ${idx === 0 ? 'active' : ''}`;
        dotsContainer.appendChild(dot);
    });
    renderGuideStep(0);
    setTimeout(() => {
        bubble.classList.add('bubble-slide-up');
        setTimeout(() => {
            bubble.style.transform = 'scale(1) translateY(0)';
            startGuideAutoPlay();
        }, 800);
    }, 1000);
}

function startGuideAutoPlay() {
    if (guideInterval) return;
    guideInterval = setInterval(() => {
        let nextIndex = currentGuideIndex + 1;
        if (nextIndex >= guideStepsData.length) nextIndex = 0;
        changeGuideStep(nextIndex);
    }, 4000);
}

function stopGuideAutoPlay() {
    if (guideInterval) {
        clearInterval(guideInterval);
        guideInterval = null;
    }
}

function nextGuideStep() {
    stopGuideAutoPlay();
    let nextIndex = currentGuideIndex + 1;
    if (nextIndex >= guideStepsData.length) nextIndex = 0;
    changeGuideStep(nextIndex);
}

function prevGuideStep() {
    stopGuideAutoPlay();
    let prevIndex = currentGuideIndex - 1;
    if (prevIndex < 0) prevIndex = guideStepsData.length - 1;
    changeGuideStep(prevIndex);
}

function changeGuideStep(index) {
    const textArea = document.getElementById('guide-text-area');
    textArea.classList.add('text-fade-out');
    setTimeout(() => {
        renderGuideStep(index);
        textArea.classList.remove('text-fade-out');
    }, 200);
    currentGuideIndex = index;
}

function renderGuideStep(index) {
    const data = guideStepsData[index];
    document.getElementById('guide-step-num').textContent = data.step;
    document.getElementById('guide-title').innerHTML = data.title;
    document.getElementById('guide-desc').innerHTML = data.desc;
    const icon = document.getElementById('guide-icon');
    const iconBg = document.getElementById('guide-icon-bg');
    icon.className = `${data.icon} ${data.iconColor}`;
    iconBg.className = `w-14 h-14 rounded-2xl bg-white border-2 flex items-center justify-center text-2xl transition-all duration-300 shadow-[3px_3px_0_rgba(0,0,0,0.05)] ${data.border} ${data.bg}`;
    const dots = document.querySelectorAll('.guide-dot');
    dots.forEach((dot, idx) => {
        dot.classList.toggle('active', idx === index);
    });
}

// ==========================================
// 13. Initialization (window.onload)
// ==========================================

window.onload = function () {
    updateLogo();
    setTimeout(() => {
        updatePlayerPosition(0);
        gameLoop();
    }, 500);

    window.addEventListener('resize', () => {
        updateLogo();
        updatePlayerPosition(playerPos);
        initNodes();
        renderMap();
        renderMapPath();
        highlightCurrentStation(currentDay);
        highlightDestination(currentDay);
        placeTrainAtDay(currentDay);
        if (window.innerWidth < 1400) centerMapOnStation(currentDay);
    });

    window.addEventListener('scroll', () => {
        requestAnimationFrame(updateLogo);
        const bubble = document.getElementById('guide-bubble');
        if (!bubble) return;
        if (window.scrollY > 50) {
            bubble.style.transition = 'none';
            bubble.style.opacity = '0';
            bubble.style.visibility = 'hidden';
        } else {
            requestAnimationFrame(() => {
                bubble.style.transition = 'all 0.5s ease';
                bubble.style.visibility = 'visible';
                bubble.style.opacity = '1';
                bubble.style.transform = 'translateY(0) scale(1)';
            });
        }
    });

    initNodes();
    renderMap();
    initDragScroll();
    createAlphabets();
    createStars();
    updateBackgroundTheme(0);
    initGuideBubble();

    // Cheat Key Logic (Developer Mode)
    const dayLabelTarget = document.getElementById('intro-day-number').parentElement;
    let devCheatCount = 0;
    let devCheatTimer;
    if (dayLabelTarget) {
        dayLabelTarget.style.cursor = 'default';
        dayLabelTarget.style.userSelect = 'none';
        dayLabelTarget.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            devCheatCount++;
            clearTimeout(devCheatTimer);
            devCheatTimer = setTimeout(() => {
                devCheatCount = 0;
            }, 400);
            if (devCheatCount >= 5) {
                console.log(`⚡ Cheat Activated: Day ${currentDay} Instant Clear!`);
                if (levelData[currentDay] && levelData[currentDay].verbs) {
                    levelData[currentDay].verbs.forEach(verb => {
                        completedVerbs.add(verb);
                    });
                }
                updateMissionStamps();
                setTimeout(() => {
                    finishVerb();
                }, 300);
                devCheatCount = 0;
            }
        });
    }

    setTimeout(() => {
        centerMapOnStation(currentDay);
        animateTrain(0, currentDay, true, () => {
            highlightDestination(currentDay);
        });
    }, 100);
};


// ==========================================
// Main Menu Logic
// ==========================================

(function () {
    const btn = document.getElementById('main-menu-btn');
    const dropdown = document.getElementById('main-menu-dropdown');

    if (!btn || !dropdown) return;

    function openMenu() {
        dropdown.classList.add('is-open');

        // 열릴 때 애니메이션 (display:block 된 다음에 animate)
        dropdown.animate(
            [
                { opacity: 0, transform: 'translateY(-10px) scale(0.95)' },
                { opacity: 1, transform: 'translateY(0) scale(1)' }
            ],
            { duration: 300, easing: 'cubic-bezier(0.34, 1.56, 0.64, 1)' }
        );
    }

    function closeMenu() {
        const anim = dropdown.animate(
            [
                { opacity: 1, transform: 'translateY(0) scale(1)' },
                { opacity: 0, transform: 'translateY(-10px) scale(0.95)' }
            ],
            { duration: 200, easing: 'ease-in' }
        );

        anim.onfinish = () => dropdown.classList.remove('is-open');
    }

    function toggleMenu(e) {
        if (e) e.stopPropagation();

        if (dropdown.classList.contains('is-open')) closeMenu();
        else openMenu();
    }

    btn.addEventListener('click', toggleMenu);

    // 드롭다운 내부 클릭은 닫히지 않게
    dropdown.addEventListener('click', (e) => e.stopPropagation());

    // 바깥 클릭 시 닫기
    document.addEventListener('click', () => {
        if (dropdown.classList.contains('is-open')) closeMenu();
    });

    // ESC로 닫기
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && dropdown.classList.contains('is-open')) closeMenu();
    });
})();




// 스크롤 시 로고 바뀜
    window.addEventListener('scroll', () => {
    const originalLogo = document.getElementById('logo-original');
    const scrolledLogo = document.getElementById('logo-scrolled');

    // 스크롤을 50px 이상 내렸을 때 로고 변경
    if (window.scrollY > 50) {
    originalLogo.style.opacity = '0';
    scrolledLogo.style.opacity = '1';
} else {
    // 다시 최상단으로 올라왔을 때 기존 로고로 복귀
    originalLogo.style.opacity = '1';
    scrolledLogo.style.opacity = '0';
}
});

// ✅ 햄버거 메뉴 드롭다운 토글 (없으면 추가)
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.querySelector('[data-action="toggleMenu"], #menu-btn, .ui-menu-btn');
    const dropdown = document.querySelector('#menu-dropdown, .ui-menu-dropdown, .dropdown-menu');

    if (!btn || !dropdown) return;

    btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropdown.classList.toggle('is-open'); // <- CSS와 이름 맞추세요
    });

    // 바깥 클릭 시 닫기
    document.addEventListener('click', () => dropdown.classList.remove('is-open'));
});










function toggleMainMenu(e){
    e.stopPropagation();
    const dropdown = document.getElementById('main-menu-dropdown');
    dropdown.classList.toggle('is-open');
}



















// --- Legacy inline handler aliases (for backend-friendly HTML) ---
// Some HTML templates may call openVerb(...) or openCard(...). Keep these as thin wrappers.
function openVerb(verbKey){
  // verbKey: 'have' | 'change' | 'start' ...
  if (typeof startDrill === 'function') return startDrill(verbKey);
  console.warn('startDrill is not defined');
}
window.openVerb = openVerb;
if (typeof openCard === 'function') window.openCard = openCard;

/** 로그아웃 */
function doLogout() {
    if (confirm('로그아웃 하시겠습니까?')) {
        location.href = './api/auth/logout.php';
    }
}
