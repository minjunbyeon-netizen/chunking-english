<?php
require_once '_auth.php';
require_once '../config/db.php';

// ── AJAX 액션 처리 ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'set_day') {
        $day  = max(1, min(250, (int)($_POST['day'] ?? 1)));
        $mode = $_POST['mode'] ?? 'start'; // start | complete

        if ($mode === 'start') {
            // 해당 Day를 현재 Day로 설정, 이전 Day들 모두 완료 처리
            $_SESSION['debug_override'] = [
                'currentDay'    => $day,
                'completedDays' => $day > 1 ? range(1, $day - 1) : [],
                'unlockedDays'  => $day,
            ];
        } elseif ($mode === 'complete') {
            // Day N까지 완료된 상태 (currentDay = N+1)
            $_SESSION['debug_override'] = [
                'currentDay'    => $day + 1,
                'completedDays' => range(1, $day),
                'unlockedDays'  => $day + 1,
            ];
        } elseif ($mode === 'fresh') {
            // 완전 초기 상태 (아무것도 완료 안 함)
            $_SESSION['debug_override'] = [
                'currentDay'    => 1,
                'completedDays' => [],
                'unlockedDays'  => 1,
            ];
        } elseif ($mode === 'clear') {
            unset($_SESSION['debug_override']);
        }
        echo json_encode(['ok' => true, 'override' => $_SESSION['debug_override'] ?? null]);
        exit;
    }

    if ($action === 'reset_progress') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id > 0) {
            $pdo->prepare("DELETE FROM progress WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM expression_progress WHERE user_id = ?")->execute([$user_id]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'get_day_data') {
        $day = max(1, min(250, (int)($_POST['day'] ?? 1)));
        $rows = $pdo->prepare("
            SELECT v.verb_en, v.verb_kr, e.expression_en, e.expression_kr
            FROM days d
            JOIN verbs v ON v.day_id = d.id
            JOIN expressions e ON e.verb_id = v.id
            WHERE d.day_number = ?
            ORDER BY v.order_num, e.order_num
        ");
        $rows->execute([$day]);
        echo json_encode(['ok' => true, 'data' => $rows->fetchAll()]);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'unknown action']);
    exit;
}

// ── 화면용 데이터 ────────────────────────────────────────────────
$users = $pdo->query("SELECT id, email, nickname FROM users ORDER BY id DESC LIMIT 50")->fetchAll();
$dayMax = (int)$pdo->query("SELECT MAX(day_number) FROM days")->fetchColumn();
$currentOverride = $_SESSION['debug_override'] ?? null;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>디버그 · 청킹잉글리시 관리자</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
<style>
:root {
    --pink:    #FF7E96;
    --pink-lt: #FFD0D9;
    --pink-dk: #C75A6F;
    --bg:      #F9F5F6;
    --border:  #EDE0E3;
    --green:   #16a34a;
    --red:     #dc2626;
    --blue:    #1d4ed8;
    --text:    #1a1a2e;
    --muted:   #78716c;
    --white:   #ffffff;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Noto Sans KR', sans-serif; background: var(--bg); color: var(--text); font-size: 14px; }

header {
    background: var(--pink-dk); color: #fff; padding: 0 28px;
    display: flex; align-items: stretch; border-bottom: 1px solid rgba(0,0,0,.1);
}
.hd-title { padding: 14px 0; flex: 1; }
.hd-title h1 { font-size: 1rem; font-weight: 700; }
.hd-title .sub { font-size: .75rem; opacity: .7; margin-top: 2px; }
nav { display: flex; align-items: center; }
nav a {
    display: inline-flex; align-items: center; height: 100%;
    padding: 0 16px; font-size: .82rem; font-weight: 500;
    color: rgba(255,255,255,.75); text-decoration: none;
    border-bottom: 2px solid transparent; transition: color .12s, border-color .12s;
}
nav a:hover { color: #fff; }
nav a.active { color: #fff; border-bottom-color: #fff; }

.container { max-width: 960px; margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; gap: 20px; }

/* 카드 */
.card {
    background: var(--white); border: 1px solid var(--border);
    border-radius: 10px; overflow: hidden;
}
.card-head {
    padding: 12px 18px; border-bottom: 1px solid var(--border);
    background: #fafafa; display: flex; align-items: center; gap: 10px;
}
.card-head h2 { font-size: .9rem; font-weight: 700; flex: 1; }
.card-body { padding: 18px; }

/* 상태 배지 */
.badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px;
    font-size: .75rem; font-weight: 600;
}
.badge-on  { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.badge-off { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }

/* 버튼 */
.btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: 6px; font-size: .82rem;
    font-family: inherit; font-weight: 600; cursor: pointer;
    border: 1px solid transparent; transition: opacity .12s;
}
.btn:hover { opacity: .85; }
.btn-primary { background: #111; color: #fff; }
.btn-blue    { background: #1d4ed8; color: #fff; }
.btn-green   { background: #16a34a; color: #fff; }
.btn-red     { background: #dc2626; color: #fff; }
.btn-ghost   { background: var(--white); color: var(--text); border-color: var(--border); }
.btn-sm { padding: 5px 11px; font-size: .76rem; }

/* 슬라이더 영역 */
.day-picker { display: flex; flex-direction: column; gap: 12px; }
.slider-row { display: flex; align-items: center; gap: 12px; }
.slider-row input[type=range] { flex: 1; accent-color: var(--pink-dk); }
.day-num-badge {
    min-width: 60px; text-align: center;
    font-size: 1.4rem; font-weight: 700; color: var(--pink-dk);
}
.btn-group { display: flex; gap: 8px; flex-wrap: wrap; }

/* 현재 오버라이드 상태 */
.override-box {
    background: #fffbeb; border: 1px solid #fde68a;
    border-radius: 8px; padding: 12px 16px;
    font-size: .82rem; line-height: 1.7;
}
.override-box .label { font-weight: 700; color: #92400e; margin-bottom: 4px; }

/* TTS 테스트 */
.tts-row { display: flex; gap: 8px; }
.tts-row input {
    flex: 1; padding: 8px 12px; border: 1px solid var(--border);
    border-radius: 6px; font-family: inherit; font-size: .9rem;
    outline: none;
}
.tts-row input:focus { border-color: #000; box-shadow: 0 0 0 2px rgba(0,0,0,.08); }

/* Day 데이터 미리보기 */
#day-preview { font-size: .82rem; }
#day-preview table { width: 100%; border-collapse: collapse; }
#day-preview th { background: #fafafa; border-bottom: 1px solid var(--border); padding: 6px 10px; text-align: left; font-size: .75rem; color: var(--muted); }
#day-preview td { padding: 5px 10px; border-bottom: 1px solid #f0eced; }
#day-preview tr:last-child td { border-bottom: none; }

/* 사용자 선택 */
select {
    padding: 7px 10px; border: 1px solid var(--border); border-radius: 6px;
    font-family: inherit; font-size: .82rem; background: var(--white);
    outline: none; min-width: 240px;
}
select:focus { border-color: #000; box-shadow: 0 0 0 2px rgba(0,0,0,.08); }

/* 빠른 링크 */
.link-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.quick-link {
    padding: 6px 14px; background: #eff6ff; color: #1d4ed8;
    border: 1px solid #bfdbfe; border-radius: 6px; font-size: .8rem;
    font-weight: 500; text-decoration: none; transition: background .12s;
}
.quick-link:hover { background: #dbeafe; }

.toast {
    position: fixed; bottom: 24px; right: 24px;
    background: #111; color: #fff; padding: 10px 18px;
    border-radius: 8px; font-size: .82rem;
    opacity: 0; transition: opacity .2s;
    pointer-events: none; z-index: 9999;
}
.toast.show { opacity: 1; }
</style>
</head>
<body>

<header>
    <div class="hd-title">
        <h1>청킹잉글리시 관리자</h1>
        <div class="sub">디버그 도구</div>
    </div>
    <nav>
        <a href="dashboard.php">대시보드</a>
        <a href="index.php">Day 목록</a>
        <a href="overview.php">전체 현황</a>
        <a href="organizations.php">지자체 관리</a>
        <a href="users.php">사용자 관리</a>
        <a href="debug.php" class="active">디버그</a>
    </nav>
</header>

<div class="container">

    <!-- 현재 오버라이드 상태 -->
    <div class="card">
        <div class="card-head">
            <h2>현재 디버그 상태</h2>
            <?php if ($currentOverride): ?>
            <span class="badge badge-on">오버라이드 활성</span>
            <?php else: ?>
            <span class="badge badge-off">오버라이드 없음 (실제 데이터)</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($currentOverride): ?>
            <div class="override-box">
                <div class="label">세션에 설정된 디버그 값</div>
                현재 Day: <strong><?= (int)($currentOverride['currentDay'] ?? 1) ?></strong> &nbsp;|&nbsp;
                완료 Days: <strong><?= implode(', ', array_slice($currentOverride['completedDays'] ?? [], 0, 10)) ?><?= count($currentOverride['completedDays'] ?? []) > 10 ? '...' : '' ?></strong>
                (총 <?= count($currentOverride['completedDays'] ?? []) ?>개) &nbsp;|&nbsp;
                잠금해제: <strong>Day <?= (int)($currentOverride['unlockedDays'] ?? 1) ?>까지</strong>
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;">
                <a href="../index.php" target="_blank" class="btn btn-blue">메인 페이지로 이동 (오버라이드 적용)</a>
                <button class="btn btn-ghost" onclick="clearOverride()">오버라이드 해제</button>
            </div>
            <?php else: ?>
            <p style="color:var(--muted);font-size:.85rem;">아래에서 Day를 설정하면 메인 페이지가 해당 상태로 열립니다.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Day 직접 이동 -->
    <div class="card">
        <div class="card-head">
            <h2>Day 직접 이동</h2>
        </div>
        <div class="card-body">
            <div class="day-picker">
                <div class="slider-row">
                    <input type="range" id="day-slider" min="1" max="<?= $dayMax ?>" value="1"
                           oninput="onSlider(this.value)">
                    <div class="day-num-badge">Day <span id="day-display">1</span></div>
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="setDay('start')">
                        이 Day부터 시작
                    </button>
                    <button class="btn btn-green" onclick="setDay('complete')">
                        이 Day까지 완료 처리
                    </button>
                    <button class="btn btn-ghost" onclick="setDay('fresh')">
                        Day 1 초기화 (처음부터)
                    </button>
                </div>
                <p style="font-size:.78rem;color:var(--muted);">
                    "이 Day부터 시작" → 선택한 Day가 현재 Day, 이전은 모두 완료<br>
                    "이 Day까지 완료" → 선택한 Day까지 완료, 다음 Day가 현재
                </p>
            </div>
        </div>
    </div>

    <!-- Day 콘텐츠 미리보기 -->
    <div class="card">
        <div class="card-head">
            <h2>Day 콘텐츠 확인</h2>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;gap:8px;align-items:center;">
                <select id="preview-day-select">
                    <?php for ($i = 1; $i <= $dayMax; $i++): ?>
                    <option value="<?= $i ?>">Day <?= $i ?></option>
                    <?php endfor; ?>
                </select>
                <button class="btn btn-ghost btn-sm" onclick="loadDayPreview()">불러오기</button>
                <a id="book-link" href="../book.php?day=1" target="_blank" class="btn btn-blue btn-sm">book.php로 열기</a>
            </div>
            <div id="day-preview"><p style="color:var(--muted);">불러오기 버튼을 누르면 해당 Day 동사/표현을 확인합니다.</p></div>
        </div>
    </div>

    <!-- TTS 테스트 -->
    <div class="card">
        <div class="card-head">
            <h2>TTS 테스트</h2>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
            <div class="tts-row">
                <input type="text" id="tts-input" value="I have a dream." placeholder="영어 문장 입력...">
                <button class="btn btn-primary" onclick="playTTS()">재생</button>
                <button class="btn btn-ghost" onclick="stopTTS()">정지</button>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;" id="tts-presets"></div>
            <div style="display:flex;gap:12px;align-items:center;font-size:.82rem;">
                <label>속도: <input type="range" id="tts-rate" min="0.5" max="1.5" step="0.05" value="0.85" style="width:100px;accent-color:var(--pink-dk)"> <span id="tts-rate-val">0.85</span></label>
            </div>
        </div>
    </div>

    <!-- 진도 초기화 -->
    <div class="card">
        <div class="card-head">
            <h2>사용자 진도 초기화 (DB)</h2>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
            <p style="font-size:.82rem;color:var(--muted);">선택한 사용자의 progress, expression_progress 테이블을 삭제합니다.</p>
            <div style="display:flex;gap:8px;align-items:center;">
                <select id="reset-user-select">
                    <option value="">사용자 선택...</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['email']) ?> (<?= htmlspecialchars($u['nickname'] ?? '') ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-red btn-sm" onclick="resetProgress()">진도 초기화</button>
            </div>
        </div>
    </div>

    <!-- 빠른 링크 -->
    <div class="card">
        <div class="card-head">
            <h2>빠른 링크</h2>
        </div>
        <div class="card-body">
            <div class="link-grid" id="quick-links">
                <?php for ($i = 1; $i <= min($dayMax, 50); $i++): ?>
                <a href="../book.php?day=<?= $i ?>" target="_blank" class="quick-link">book Day<?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

</div>

<div class="toast" id="toast"></div>

<script>
const DAY_MAX = <?= $dayMax ?>;
let selectedDay = 1;

function onSlider(val) {
    selectedDay = parseInt(val);
    document.getElementById('day-display').textContent = val;
    document.getElementById('book-link').href = `../book.php?day=${val}`;
    document.getElementById('preview-day-select').value = val;
}

function toast(msg) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2000);
}

function post(data) {
    return fetch('debug.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(data)
    }).then(r => r.json());
}

function setDay(mode) {
    post({ action: 'set_day', day: selectedDay, mode }).then(res => {
        if (res.ok) {
            toast(`Day ${selectedDay} 설정 완료 — 메인 페이지로 이동하세요`);
            setTimeout(() => location.reload(), 800);
        }
    });
}

function clearOverride() {
    post({ action: 'set_day', day: 1, mode: 'clear' }).then(() => {
        toast('오버라이드 해제됨');
        setTimeout(() => location.reload(), 600);
    });
}

function loadDayPreview() {
    const day = parseInt(document.getElementById('preview-day-select').value);
    document.getElementById('book-link').href = `../book.php?day=${day}`;
    post({ action: 'get_day_data', day }).then(res => {
        if (!res.ok || !res.data.length) {
            document.getElementById('day-preview').innerHTML = '<p style="color:#dc2626">데이터 없음</p>';
            return;
        }
        let html = '<table><thead><tr><th>동사</th><th>표현 (영어)</th><th>한국어</th></tr></thead><tbody>';
        res.data.forEach(r => {
            html += `<tr><td>${r.verb_en} / ${r.verb_kr}</td><td>${r.expression_en}</td><td style="color:var(--muted)">${r.expression_kr}</td></tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('day-preview').innerHTML = html;
    });
}

// TTS
function playTTS() {
    if (!('speechSynthesis' in window)) return alert('TTS 미지원 브라우저');
    window.speechSynthesis.cancel();
    const text = document.getElementById('tts-input').value.trim();
    const rate = parseFloat(document.getElementById('tts-rate').value);
    if (!text) return;
    setTimeout(() => {
        const ut = new SpeechSynthesisUtterance(text);
        ut.lang = 'en-US';
        ut.rate = rate;
        window.speechSynthesis.speak(ut);
    }, 150);
}
function stopTTS() {
    if ('speechSynthesis' in window) window.speechSynthesis.cancel();
}

// TTS 프리셋
const presets = [
    'I have a dream.', 'I have a chance.', 'I have fun.',
    'You have a dream.', 'He has a dream.',
    'Do you have a dream?', "I don't have a dream.",
    'Have a dream, please.'
];
const presetEl = document.getElementById('tts-presets');
presets.forEach(p => {
    const btn = document.createElement('button');
    btn.className = 'btn btn-ghost btn-sm';
    btn.textContent = p;
    btn.onclick = () => {
        document.getElementById('tts-input').value = p;
        playTTS();
    };
    presetEl.appendChild(btn);
});

document.getElementById('tts-rate').oninput = function() {
    document.getElementById('tts-rate-val').textContent = this.value;
};

// 진도 초기화
function resetProgress() {
    const uid = document.getElementById('reset-user-select').value;
    if (!uid) return alert('사용자를 선택하세요');
    if (!confirm('정말 이 사용자의 진도를 모두 삭제하시겠습니까?')) return;
    post({ action: 'reset_progress', user_id: uid }).then(res => {
        if (res.ok) toast('진도 초기화 완료');
    });
}

// 슬라이더 초기화
document.getElementById('day-slider').max = DAY_MAX;
</script>
<a href="../index.php" title="학습 메인으로" style="position:fixed;bottom:20px;left:20px;z-index:9999;display:flex;align-items:center;gap:8px;background:#C75A6F;color:#fff;border-radius:980px;padding:8px 16px 8px 8px;text-decoration:none;font-size:.8rem;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.18);transition:opacity .2s;" onmouseover="this.style.opacity=.85" onmouseout="this.style.opacity=1"><img src="../img/ck_train.png" style="width:28px;height:28px;object-fit:contain;border-radius:50%;background:#fff;padding:2px;">학습 홈</a>
</body>
</html>
