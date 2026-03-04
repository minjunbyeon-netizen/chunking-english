<?php
require_once '_auth.php';
require_once '../config/db.php';
$BASE = dirname(__DIR__);

function web_url(string $rel): string {
    return '../' . implode('/', array_map('rawurlencode', explode('/', str_replace('\\', '/', $rel))));
}
function resolve_img(array $expr, array $verb, int $day, string $BASE): array {
    if (!empty($expr['image_path'])) {
        $clean = str_replace('\\', '/', $expr['image_path']);
        if (file_exists($BASE . '/' . $clean))
            return ['exists' => true, 'url' => web_url($clean)];
    }
    $gv   = str_pad($verb['global_num'], 2, '0', STR_PAD_LEFT);
    $slug = str_replace(' ', '_', $expr['expression_en']);
    $rel  = "asset/img/day {$day}/{$gv}. {$verb['verb_en']}/{$slug}.png";
    $ok   = file_exists($BASE . '/' . $rel);
    return ['exists' => $ok, 'url' => $ok ? web_url($rel) : null];
}
function resolve_audio(array $expr, array $verb, int $day, string $BASE): array {
    if (!empty($expr['audio_path'])) {
        $clean = str_replace('\\', '/', $expr['audio_path']);
        if (file_exists($BASE . '/' . $clean))
            return ['exists' => true, 'url' => web_url($clean)];
    }
    $gv   = str_pad($verb['global_num'], 2, '0', STR_PAD_LEFT);
    $slug = str_replace(' ', '_', $expr['expression_en']);
    $rel  = "asset/audio/day {$day}/{$gv}. {$verb['verb_en']}/{$slug}.mp3";
    $ok   = file_exists($BASE . '/' . $rel);
    return ['exists' => $ok, 'url' => $ok ? web_url($rel) : null];
}

$rows = $pdo->query("
    SELECT d.day_number,
           v.order_num AS v_order, v.global_num, v.verb_en, v.verb_kr, v.sentence_en,
           e.order_num AS e_order, e.expression_en, e.expression_kr,
           e.image_path, e.audio_path
    FROM days d
    JOIN verbs v ON v.day_id = d.id
    JOIN expressions e ON e.verb_id = v.id
    ORDER BY d.day_number, v.order_num, e.order_num
")->fetchAll();

$days = [];
foreach ($rows as $r) {
    $dn = (int)$r['day_number'];
    $gv = (int)$r['global_num'];
    if (!isset($days[$dn]))
        $days[$dn] = ['day_number' => $dn, 'verbs' => [], 'total' => 0, 'img_ok' => 0, 'audio_ok' => 0];
    if (!isset($days[$dn]['verbs'][$gv]))
        $days[$dn]['verbs'][$gv] = ['global_num' => $gv, 'verb_en' => $r['verb_en'], 'verb_kr' => $r['verb_kr'], 'expressions' => []];

    $verb_ref = &$days[$dn]['verbs'][$gv];
    $img   = resolve_img($r, ['global_num' => $gv, 'verb_en' => $r['verb_en']], $dn, $BASE);
    $audio = resolve_audio($r, ['global_num' => $gv, 'verb_en' => $r['verb_en']], $dn, $BASE);
    $verb_ref['expressions'][] = [
        'expression_en' => $r['expression_en'],
        'expression_kr' => $r['expression_kr'],
        'img'           => $img,
        'audio'         => $audio,
    ];
    unset($verb_ref);
    $days[$dn]['total']++;
    if ($img['exists'])   $days[$dn]['img_ok']++;
    if ($audio['exists']) $days[$dn]['audio_ok']++;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>청킹잉글리시 관리자</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
<style>
:root {
    --pink:     #FF7E96;
    --pink-lt:  #FFD0D9;
    --pink-dk:  #C75A6F;
    --bg:       #F9F5F6;
    --border:   #EDE0E3;
    --green:    #16a34a;
    --red:      #dc2626;
    --text:     #1a1a2e;
    --muted:    #78716c;
    --white:    #ffffff;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Noto Sans KR', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; font-size: 14px; }

/* ── 헤더 ── */
header {
    background: var(--pink-dk);
    color: #fff; padding: 0 28px;
    display: flex; align-items: stretch;
    border-bottom: 1px solid rgba(0,0,0,.1);
}
.hd-title { padding: 14px 0; flex: 1; }
.hd-title h1 { font-size: 1rem; font-weight: 700; letter-spacing: -.3px; }
.hd-title .sub { font-size: .75rem; opacity: .7; margin-top: 2px; }
nav { display: flex; align-items: center; }
nav a {
    display: inline-flex; align-items: center; height: 100%;
    padding: 0 16px; font-size: .82rem; font-weight: 500;
    color: rgba(255,255,255,.75); text-decoration: none;
    border-bottom: 2px solid transparent;
    transition: color .12s, border-color .12s;
}
nav a:hover { color: #fff; }
nav a.active { color: #fff; border-bottom-color: #fff; }

/* ── 레이아웃 ── */
.container { max-width: 1060px; margin: 0 auto; padding: 20px 20px; }

/* ── 컨트롤 바 ── */
.controls {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px;
}
.btn-row { display: flex; gap: 6px; }
.btn {
    display: inline-flex; align-items: center;
    border: 1px solid var(--border); border-radius: 6px;
    padding: 5px 12px; font-size: .78rem; font-family: inherit;
    background: var(--white); color: var(--muted);
    cursor: pointer; transition: background .12s, color .12s;
}
.btn:hover { background: var(--pink-lt); color: var(--pink-dk); border-color: var(--pink-lt); }
.info-text { font-size: .75rem; color: var(--muted); }

/* ── 아코디언 리스트 ── */
.day-list { display: flex; flex-direction: column; gap: 4px; }

.day-item {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
}
.day-item.open { border-color: var(--pink-lt); }

/* ── Day 헤더 ── */
.day-header {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px; cursor: pointer; user-select: none;
    transition: background .1s;
}
.day-header:hover { background: #FFF5F7; }
.day-item.open .day-header { background: #FFF0F3; border-bottom: 1px solid var(--border); }

/* CSS chevron */
.chevron {
    width: 16px; height: 16px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.chevron::after {
    content: '';
    display: block;
    width: 6px; height: 6px;
    border-right: 1.5px solid var(--muted);
    border-bottom: 1.5px solid var(--muted);
    transform: rotate(-45deg) translate(-1px, 1px);
    transition: transform .2s;
}
.day-item.open .chevron::after { transform: rotate(45deg) translate(-2px, -1px); }

.dh-num { font-size: .88rem; font-weight: 700; color: var(--pink-dk); min-width: 48px; flex-shrink: 0; }

.dh-verbs { flex: 1; display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.vchip {
    font-size: .76rem; padding: 2px 8px; border-radius: 4px;
    background: #f5f0f1; color: var(--text);
    white-space: nowrap;
}
.vchip .kr { color: var(--muted); margin-left: 3px; }

.dh-stats { display: flex; gap: 6px; flex-shrink: 0; }
.spill {
    font-size: .74rem; font-weight: 500; padding: 3px 9px;
    border-radius: 4px; border: 1px solid transparent; white-space: nowrap;
}
.spill.ok  { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
.spill.err { background: #fff1f2; color: #9f1239; border-color: #fecdd3; }
.spill.warn{ background: #fffbeb; color: #92400e; border-color: #fde68a; }

/* ── 아코디언 바디 ── */
.day-body { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .24s ease; }
.day-item.open .day-body { grid-template-rows: 1fr; }
.day-body-inner { overflow: hidden; }

/* ── 테이블 ── */
table { width: 100%; border-collapse: collapse; font-size: .82rem; }
th {
    padding: 7px 10px; text-align: left;
    font-size: .7rem; font-weight: 600; color: var(--muted);
    background: #fafafa; border-bottom: 1px solid var(--border);
    letter-spacing: .3px;
}
th + th { border-left: 1px solid var(--border); }
td { padding: 6px 10px; border-bottom: 1px solid #f0eced; vertical-align: middle; }
td + td { border-left: 1px solid #f0eced; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: #fdf8f9; }
tbody tr:hover .vcell { background: #faeef1; }

.vcell {
    font-weight: 600; font-size: .78rem; color: var(--pink-dk);
    background: #fdf5f6; text-align: center; line-height: 1.5;
    border-right: 2px solid var(--pink-lt) !important;
}
.vnum { display: block; font-size: .64rem; color: var(--muted); font-weight: 400; }

/* 이미지 셀 */
.img-cell { display: flex; align-items: center; justify-content: center; gap: 5px; }
.thumb { width: 32px; height: 32px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border); flex-shrink: 0; }

/* 상태 점 */
.dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.dot-ok   { background: var(--green); }
.dot-no   { background: #e2d9db; }

/* ── 카테고리 구분 ── */
.cat-header {
    display: flex; align-items: center; gap: 10px;
    margin: 18px 0 6px;
}
.cat-header::before, .cat-header::after {
    content: ''; flex: 1; height: 1px; background: var(--border);
}
.cat-badge {
    display: flex; align-items: center; gap: 6px;
    padding: 4px 14px; border-radius: 20px;
    background: var(--pink-dk); color: #fff;
    font-size: .75rem; font-weight: 600; white-space: nowrap;
}
.cat-badge .cat-kr { font-weight: 400; opacity: .8; }

/* 재생 버튼 */
.play-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 50%;
    background: #f0fdf4; border: 1px solid #bbf7d0;
    color: var(--green); font-size: .7rem;
    cursor: pointer; transition: background .12s, transform .1s;
    line-height: 1;
}
.play-btn:hover  { background: #dcfce7; transform: scale(1.1); }
.play-btn.playing { background: var(--green); color: #fff; border-color: var(--green); }
</style>
</head>
<body>

<header>
    <div class="hd-title">
        <h1>청킹잉글리시 관리자</h1>
        <div class="sub">콘텐츠 관리 · 이미지 / MP3 현황</div>
    </div>
    <nav>
        <a href="dashboard.php">대시보드</a>
        <a href="index.php" class="active">Day 목록</a>
        <a href="overview.php">전체 현황</a>
        <a href="organizations.php">지자체 관리</a>
        <a href="users.php">사용자 관리</a>
    </nav>
</header>

<div class="container">

    <div class="controls">
        <div class="btn-row">
            <button class="btn" onclick="expandAll()">전체 펼치기</button>
            <button class="btn" onclick="collapseAll()">전체 접기</button>
        </div>
        <span class="info-text">총 <?= count($days) ?>일 &nbsp;·&nbsp; <?= array_sum(array_column($days, 'total')) ?>개 표현</span>
    </div>

    <div class="day-list">
    <?php
    $categories = [
        1   => ['en' => 'Hope & Practice',        'kr' => '희망 & 실천'],
        11  => ['en' => 'Morning Routine',         'kr' => '아침 일상'],
        30  => ['en' => 'School Life',             'kr' => '학교 생활'],
        88  => ['en' => 'Exercise & Sports',       'kr' => '운동 & 스포츠'],
        100 => ['en' => 'Food & Cooking',          'kr' => '음식 & 요리'],
        139 => ['en' => 'Daily Life',              'kr' => '일상 생활'],
        195 => ['en' => 'Transportation & Travel', 'kr' => '교통 & 여행'],
        225 => ['en' => 'Health & Medicine',       'kr' => '건강 & 의료'],
        235 => ['en' => 'Evening Routine',         'kr' => '저녁 일상'],
    ];
    foreach ($days as $dn => $day):
        $t    = max(1, $day['total']);
        $i_ok = $day['img_ok'];
        $a_ok = $day['audio_ok'];
        $i_cls = $i_ok === $t ? 'ok' : ($i_ok === 0 ? 'err' : 'warn');
        $a_cls = $a_ok === $t ? 'ok' : ($a_ok === 0 ? 'err' : 'warn');
        $verbs = array_values($day['verbs']);
        if (isset($categories[$dn])):
            $cat = $categories[$dn];
    ?>
    <div class="cat-header">
        <div class="cat-badge">
            <?= htmlspecialchars($cat['en']) ?>
            <span class="cat-kr"><?= htmlspecialchars($cat['kr']) ?></span>
        </div>
    </div>
    <?php endif; ?>
    <div class="day-item" id="day-<?= $dn ?>">

        <div class="day-header" onclick="toggleDay(<?= $dn ?>)">
            <div class="chevron"></div>
            <div class="dh-num">Day <?= $dn ?></div>
            <div class="dh-verbs">
                <?php foreach ($verbs as $v): ?>
                <span class="vchip"><?= htmlspecialchars($v['verb_en']) ?><span class="kr"><?= htmlspecialchars($v['verb_kr']) ?></span></span>
                <?php endforeach; ?>
            </div>
            <div class="dh-stats">
                <span class="spill <?= $i_cls ?>">이미지 <?= $i_ok ?>/<?= $day['total'] ?></span>
                <span class="spill <?= $a_cls ?>">MP3 <?= $a_ok ?>/<?= $day['total'] ?></span>
                <?php if ($a_ok > 0): ?>
                <a href="../api/download/day_audio.php?day=<?= $dn ?>"
                   onclick="event.stopPropagation()"
                   style="font-size:.72rem;padding:3px 9px;border-radius:4px;border:1px solid #bbf7d0;background:#f0fdf4;color:#166534;text-decoration:none;white-space:nowrap;">
                    ↓ MP3
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="day-body">
            <div class="day-body-inner">
                <table>
                    <thead>
                        <tr>
                            <th style="width:82px;text-align:center">동사</th>
                            <th style="width:24px;text-align:center">#</th>
                            <th>표현 (영어)</th>
                            <th>한국어</th>
                            <th style="width:90px;text-align:center">이미지</th>
                            <th style="width:54px;text-align:center">MP3</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($verbs as $verb):
                        $span  = count($verb['expressions']);
                        $first = true;
                        foreach ($verb['expressions'] as $idx => $e):
                    ?>
                        <tr>
                            <?php if ($first): $first = false; ?>
                            <td class="vcell" rowspan="<?= $span ?>">
                                <span class="vnum"><?= str_pad($verb['global_num'], 2, '0', STR_PAD_LEFT) ?></span>
                                <?= htmlspecialchars($verb['verb_en']) ?>
                            </td>
                            <?php endif; ?>
                            <td style="color:var(--muted);font-size:.72rem;text-align:center"><?= $idx + 1 ?></td>
                            <td><?= htmlspecialchars($e['expression_en']) ?></td>
                            <td style="color:var(--muted)"><?= htmlspecialchars($e['expression_kr'] ?? '') ?></td>
                            <td>
                                <div class="img-cell">
                                    <?php if ($e['img']['exists']): ?>
                                        <img class="thumb" src="<?= htmlspecialchars($e['img']['url']) ?>" alt="" loading="lazy">
                                    <?php endif; ?>
                                    <span class="dot <?= $e['img']['exists'] ? 'dot-ok' : 'dot-no' ?>"></span>
                                </div>
                            </td>
                            <td style="text-align:center">
                                <?php if ($e['audio']['exists']): ?>
                                    <button class="play-btn" onclick="playAudio(this)" data-src="<?= htmlspecialchars($e['audio']['url']) ?>" title="재생">
                                        ▶
                                    </button>
                                <?php else: ?>
                                    <span class="dot dot-no"></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

</div>

<audio id="admin-audio"></audio>
<script>
let _currentBtn = null;
function playAudio(btn) {
    const audio = document.getElementById('admin-audio');
    if (_currentBtn && _currentBtn !== btn) {
        _currentBtn.classList.remove('playing');
        _currentBtn.textContent = '▶';
    }
    if (btn.classList.contains('playing')) {
        audio.pause();
        btn.classList.remove('playing');
        btn.textContent = '▶';
        _currentBtn = null;
        return;
    }
    audio.src = btn.dataset.src;
    audio.play();
    btn.classList.add('playing');
    btn.textContent = '■';
    _currentBtn = btn;
    audio.onended = () => { btn.classList.remove('playing'); btn.textContent = '▶'; _currentBtn = null; };
}

function toggleDay(n) { document.getElementById('day-' + n).classList.toggle('open'); }
function expandAll()  { document.querySelectorAll('.day-item').forEach(el => el.classList.add('open')); }
function collapseAll(){ document.querySelectorAll('.day-item').forEach(el => el.classList.remove('open')); }
window.addEventListener('DOMContentLoaded', () => {
    const hash = location.hash;
    if (hash && hash.startsWith('#day-')) {
        const el = document.querySelector(hash);
        if (el) { el.classList.add('open'); setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 80); }
    }
});
</script>
</body>
</html>
