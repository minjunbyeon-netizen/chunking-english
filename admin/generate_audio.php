<?php
require_once '_auth.php';
require_once '../config/db.php';

define('TYPECAST_API_KEY', '__pltaTfoHjAHBVu9UgUAenBcFbMxUuRHKENzqLoKAu3G');
define('TYPECAST_TTS_URL', 'https://typecast.ai/api/text-to-speech');
define('TYPECAST_ACTOR_URL', 'https://typecast.ai/api/actor');
define('ASSET_BASE', dirname(__DIR__) . '/asset/audio');
define('MAX_DAY', 20);

/* ─── AJAX handlers ─────────────────────────────────────────── */

$action = $_GET['action'] ?? '';

if ($action === 'list_actors') {
    header('Content-Type: application/json');
    $ch = curl_init(TYPECAST_ACTOR_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . TYPECAST_API_KEY],
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$resp) {
        http_response_code(502);
        echo json_encode(['error' => 'Actor list fetch failed', 'http' => $code]);
        exit;
    }
    echo $resp;
    exit;
}

if ($action === 'generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $actor_id = trim($_POST['actor_id'] ?? '');
    $text     = trim($_POST['text'] ?? '');
    $rel_path = trim($_POST['rel_path'] ?? '');  // relative to asset/audio

    if (!$actor_id || !$text || !$rel_path) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing params']);
        exit;
    }
    // Security: rel_path must not escape asset/audio
    $abs = realpath(ASSET_BASE . '/' . ltrim($rel_path, '/\\'));
    if ($abs === false) {
        // File doesn't exist yet — check parent
        $parent = realpath(ASSET_BASE . '/' . ltrim(dirname($rel_path), '/\\'));
        if ($parent === false) {
            // Need to create dirs
            $dir = ASSET_BASE . '/' . ltrim(dirname($rel_path), '/\\');
            mkdir($dir, 0777, true);
            $parent = realpath($dir);
        }
        $abs = $parent . '/' . basename($rel_path);
    }
    // Path traversal guard
    if (strpos($abs, realpath(ASSET_BASE)) !== 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid path']);
        exit;
    }

    // Skip if already exists
    if (file_exists($abs)) {
        echo json_encode(['status' => 'skipped', 'path' => $rel_path]);
        exit;
    }

    // Create directory if needed
    $dir = dirname($abs);
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    // Call Typecast TTS
    $payload = json_encode([
        'actor_id'         => $actor_id,
        'text'             => $text,
        'lang'             => 'en',
        'xapi_audio_format'=> 'mp3',
        'xapi_hd'          => true,
        'speed_x'          => 1.0,
        'tts_mode'         => 'actor',
    ]);

    $ch = curl_init(TYPECAST_TTS_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . TYPECAST_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 60,
    ]);
    $mp3  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($code !== 200 || !$mp3 || strlen($mp3) < 100) {
        http_response_code(502);
        echo json_encode(['error' => "TTS failed (HTTP $code)", 'curl_error' => $err, 'body' => substr($mp3, 0, 300)]);
        exit;
    }

    file_put_contents($abs, $mp3);
    echo json_encode(['status' => 'ok', 'path' => $rel_path, 'bytes' => strlen($mp3)]);
    exit;
}

if ($action === 'list_items') {
    header('Content-Type: application/json');
    $rows = $pdo->query("
        SELECT d.day_number,
               v.order_num, v.global_num, v.verb_en, v.sentence_en,
               e.id AS expr_id, e.order_num AS e_order,
               e.expression_en
        FROM days d
        JOIN verbs v ON v.day_id = d.id
        JOIN expressions e ON e.verb_id = v.id
        WHERE d.day_number <= " . MAX_DAY . "
        ORDER BY d.day_number, v.order_num, e.order_num
    ")->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    $seenVerbs = [];
    foreach ($rows as $r) {
        $dn   = (int)$r['day_number'];
        $gv   = str_pad($r['global_num'], 2, '0', STR_PAD_LEFT);
        $verb = $r['verb_en'];
        $dir  = "day {$dn}/{$gv}. {$verb}";

        // Expression MP3
        $slug = str_replace(' ', '_', $r['expression_en']);
        $items[] = [
            'type'     => 'expression',
            'text'     => $r['expression_en'],
            'rel_path' => "{$dir}/{$slug}.mp3",
            'label'    => "Day {$dn} | {$verb} | {$r['expression_en']}",
        ];

        // Sentence MP3 (once per verb)
        $verbKey = "{$dn}_{$gv}";
        if (!isset($seenVerbs[$verbKey]) && !empty($r['sentence_en'])) {
            $seenVerbs[$verbKey] = true;
            $items[] = [
                'type'     => 'sentence',
                'text'     => $r['sentence_en'],
                'rel_path' => "{$dir}/sentence.mp3",
                'label'    => "Day {$dn} | {$verb} | [sentence]",
            ];
        }
    }
    echo json_encode($items);
    exit;
}

/* ─── HTML UI ────────────────────────────────────────────────── */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>오디오 생성 (Typecast TTS) - 관리자</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
<style>
:root {
    --pink:    #FF7E96;
    --pink-dk: #C75A6F;
    --bg:      #F9F5F6;
    --card:    #fff;
    --text:    #333;
    --gray:    #666;
    --border:  #e2e8f0;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Noto Sans KR', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }
header { background: var(--pink-dk); color: #fff; padding: 14px 24px; display: flex; align-items: center; gap: 12px; }
header a { color: rgba(255,255,255,.8); text-decoration: none; font-size: .85rem; }
header h1 { font-size: 1.1rem; font-weight: 700; }
main { max-width: 860px; margin: 0 auto; padding: 28px 16px; }
.card { background: var(--card); border-radius: 12px; border: 1px solid var(--border); padding: 24px; margin-bottom: 20px; }
h2 { font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--pink-dk); }
label { display: block; font-size: .85rem; font-weight: 500; margin-bottom: 6px; color: var(--gray); }
select, input[type=number] {
    width: 100%; padding: 9px 12px; border: 1px solid var(--border);
    border-radius: 8px; font-size: .9rem; font-family: inherit;
    background: #fafafa; appearance: none;
}
.row { display: flex; gap: 12px; flex-wrap: wrap; }
.row > * { flex: 1; min-width: 200px; }
.btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 22px; border-radius: 8px; border: none; cursor: pointer;
    font-size: .9rem; font-family: inherit; font-weight: 600; transition: .15s;
}
.btn-primary { background: var(--pink-dk); color: #fff; }
.btn-primary:hover { background: #a84a5e; }
.btn-primary:disabled { background: #ccc; cursor: not-allowed; }
.btn-secondary { background: #e2e8f0; color: var(--text); }
.btn-secondary:hover { background: #cbd5e0; }
#progress-bar-wrap { display: none; margin-top: 16px; }
#progress-bar-bg { background: #e2e8f0; border-radius: 999px; height: 10px; overflow: hidden; }
#progress-bar { height: 10px; background: var(--pink); border-radius: 999px; width: 0; transition: width .3s; }
#progress-label { font-size: .8rem; color: var(--gray); margin-top: 6px; }
#log { margin-top: 16px; max-height: 340px; overflow-y: auto; font-size: .78rem;
       background: #1a1a2e; color: #e0e0e0; border-radius: 8px; padding: 12px; line-height: 1.7; }
#log .ok   { color: #6ee7b7; }
#log .skip { color: #93c5fd; }
#log .err  { color: #fca5a5; }
#log .info { color: #fcd34d; }
.stats { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 12px; }
.stat { background: #f0f4f8; border-radius: 8px; padding: 8px 16px; font-size: .85rem; }
.stat span { font-weight: 700; color: var(--pink-dk); }
</style>
</head>
<body>
<header>
    <a href="index.php">← 관리자 홈</a>
    <h1>오디오 자동 생성 (Typecast TTS · Day 1~<?= MAX_DAY ?>)</h1>
</header>
<main>

<div class="card">
    <h2>설정</h2>
    <div class="row">
        <div>
            <label for="actor-select">음성 액터 선택</label>
            <select id="actor-select"><option value="">로딩 중...</option></select>
        </div>
        <div>
            <label for="speed-input">속도 (0.5 ~ 2.0)</label>
            <input type="number" id="speed-input" min="0.5" max="2.0" step="0.1" value="1.0">
        </div>
    </div>
</div>

<div class="card">
    <h2>생성 실행</h2>
    <div class="stats">
        <div class="stat">총 항목 <span id="total-count">-</span></div>
        <div class="stat">생성됨 <span id="ok-count">0</span></div>
        <div class="stat">건너뜀 <span id="skip-count">0</span></div>
        <div class="stat">오류 <span id="err-count">0</span></div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button class="btn btn-primary" id="btn-start" disabled>▶ 전체 생성 시작</button>
        <button class="btn btn-secondary" id="btn-stop" style="display:none;">■ 중지</button>
    </div>

    <div id="progress-bar-wrap">
        <div id="progress-bar-bg"><div id="progress-bar"></div></div>
        <div id="progress-label">준비 중...</div>
    </div>

    <div id="log"></div>
</div>

</main>

<script>
let items = [];
let stopped = false;
let okCount = 0, skipCount = 0, errCount = 0;

const actorSelect  = document.getElementById('actor-select');
const speedInput   = document.getElementById('speed-input');
const btnStart     = document.getElementById('btn-start');
const btnStop      = document.getElementById('btn-stop');
const log          = document.getElementById('log');
const progressWrap = document.getElementById('progress-bar-wrap');
const progressBar  = document.getElementById('progress-bar');
const progressLabel= document.getElementById('progress-label');
const totalEl      = document.getElementById('total-count');
const okEl         = document.getElementById('ok-count');
const skipEl       = document.getElementById('skip-count');
const errEl        = document.getElementById('err-count');

function addLog(cls, msg) {
    const el = document.createElement('div');
    el.className = cls;
    el.textContent = msg;
    log.appendChild(el);
    log.scrollTop = log.scrollHeight;
}

/* Load actors */
(async () => {
    try {
        const res = await fetch('?action=list_actors');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        // Typecast actor list: data.result.actor_list or data.actors (check structure)
        const list = data?.result?.actor_list ?? data?.actors ?? data ?? [];
        actorSelect.innerHTML = '';
        const engActors = list.filter(a => {
            const lang = (a.language ?? a.lang ?? '').toLowerCase();
            return lang.includes('en');
        });
        const pool = engActors.length ? engActors : list;
        pool.forEach(a => {
            const opt = document.createElement('option');
            opt.value = a.actor_id ?? a.id;
            opt.textContent = `${a.name ?? a.actor_id} (${a.language ?? a.lang ?? '?'})`;
            actorSelect.appendChild(opt);
        });
        addLog('info', `액터 ${pool.length}명 로드됨 (영어: ${engActors.length}명)`);
    } catch(e) {
        actorSelect.innerHTML = '<option value="">로드 실패</option>';
        addLog('err', '액터 로드 실패: ' + e.message);
    }

    /* Load item list */
    try {
        const res2 = await fetch('?action=list_items');
        items = await res2.json();
        totalEl.textContent = items.length;
        addLog('info', `총 ${items.length}개 파일 생성 예정 (Day 1~<?= MAX_DAY ?>)`);
        btnStart.disabled = false;
    } catch(e) {
        addLog('err', '항목 목록 로드 실패: ' + e.message);
    }
})();

btnStart.addEventListener('click', async () => {
    const actorId = actorSelect.value;
    const speed   = parseFloat(speedInput.value) || 1.0;
    if (!actorId) { alert('액터를 선택해주세요.'); return; }

    stopped = false;
    okCount = skipCount = errCount = 0;
    okEl.textContent = skipEl.textContent = errEl.textContent = '0';
    log.innerHTML = '';
    progressWrap.style.display = 'block';
    btnStart.style.display = 'none';
    btnStop.style.display = '';

    addLog('info', `생성 시작: 액터=${actorId}, 속도=${speed}`);

    for (let i = 0; i < items.length; i++) {
        if (stopped) { addLog('info', '중지됨.'); break; }
        const item = items[i];

        // Update progress bar
        const pct = Math.round((i / items.length) * 100);
        progressBar.style.width = pct + '%';
        progressLabel.textContent = `[${i+1}/${items.length}] ${item.label}`;

        try {
            const fd = new FormData();
            fd.append('actor_id', actorId);
            fd.append('text', item.text);
            fd.append('rel_path', item.rel_path);
            fd.append('speed_x', speed);

            const res = await fetch('?action=generate', { method: 'POST', body: fd });
            const json = await res.json();

            if (!res.ok) {
                errCount++;
                errEl.textContent = errCount;
                addLog('err', `[오류] ${item.label} → ${json.error ?? res.status}`);
            } else if (json.status === 'skipped') {
                skipCount++;
                skipEl.textContent = skipCount;
                addLog('skip', `[건너뜀] ${item.rel_path}`);
            } else {
                okCount++;
                okEl.textContent = okCount;
                addLog('ok', `[완료] ${item.rel_path} (${(json.bytes/1024).toFixed(1)} KB)`);
            }
        } catch(e) {
            errCount++;
            errEl.textContent = errCount;
            addLog('err', `[예외] ${item.label}: ${e.message}`);
        }

        // Small delay to avoid rate limits
        await new Promise(r => setTimeout(r, 300));
    }

    progressBar.style.width = '100%';
    progressLabel.textContent = `완료 (생성: ${okCount}, 건너뜀: ${skipCount}, 오류: ${errCount})`;
    addLog('info', `=== 작업 완료: 생성 ${okCount} / 건너뜀 ${skipCount} / 오류 ${errCount} ===`);
    btnStart.style.display = '';
    btnStop.style.display = 'none';
});

btnStop.addEventListener('click', () => {
    stopped = true;
    btnStop.disabled = true;
});
</script>
</body>
</html>
