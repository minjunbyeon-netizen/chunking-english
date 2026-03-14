<?php
require_once '_auth.php';
require_once '../config/db.php';
$BASE = dirname(__DIR__);

$sync_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sync') {
    $sync_result = do_sync($pdo, $BASE);
}

function do_sync(PDO $pdo, string $BASE): array {
    $updated_img = $updated_audio = $skipped = 0;
    $all = $pdo->query("
        SELECT e.id, e.expression_en, e.image_path, e.audio_path,
               v.global_num, v.verb_en, d.day_number
        FROM expressions e
        JOIN verbs v ON v.id = e.verb_id
        JOIN days  d ON d.id = v.day_id
        ORDER BY d.day_number, v.order_num, e.order_num
    ")->fetchAll();
    $lookup = [];
    foreach ($all as $r) {
        $slug = strtolower(str_replace(' ', '_', $r['expression_en']));
        $lookup["{$r['day_number']}|{$r['global_num']}|{$slug}"] = $r['id'];
    }
    function find_id(array &$lookup, int $day, int $gv, string $stem): ?int {
        $slug = strtolower($stem);
        if (isset($lookup["{$day}|{$gv}|{$slug}"])) return $lookup["{$day}|{$gv}|{$slug}"];
        return $lookup["{$day}|{$gv}|" . preg_replace('/_\d+$/', '', $slug)] ?? null;
    }
    $upd_img   = $pdo->prepare("UPDATE expressions SET image_path = ? WHERE id = ? AND (image_path IS NULL OR image_path != ?)");
    $upd_audio = $pdo->prepare("UPDATE expressions SET audio_path = ? WHERE id = ? AND (audio_path IS NULL OR audio_path != ?)");
    $manifest_path = $BASE . '/data/img_manifest.json';
    $img_list = file_exists($manifest_path)
        ? (json_decode(file_get_contents($manifest_path), true)['images'] ?? [])
        : array_map(fn($f) => str_replace('\\', '/', substr($f, strlen($BASE) + 1)),
                    glob($BASE . '/asset/img/day */*/*.png') ?: []);
    foreach ($img_list as $rel) {
        if (!preg_match('#asset/img/day (\d+)/(\d+)\. .+?/(.+)\.png$#i', $rel, $m)) continue;
        $id = find_id($lookup, (int)$m[1], (int)$m[2], $m[3]);
        if (!$id) { $skipped++; continue; }
        $upd_img->execute([$rel, $id, $rel]);
        if ($upd_img->rowCount()) $updated_img++;
    }
    foreach (glob($BASE . '/asset/audio/day */*/*.mp3') ?: [] as $f) {
        $rel = str_replace('\\', '/', substr($f, strlen($BASE) + 1));
        if (!preg_match('#asset/audio/day (\d+)/(\d+)\. .+?/(.+)\.mp3$#i', $rel, $m)) continue;
        $id = find_id($lookup, (int)$m[1], (int)$m[2], $m[3]);
        if (!$id) { $skipped++; continue; }
        $upd_audio->execute([$rel, $id, $rel]);
        if ($upd_audio->rowCount()) $updated_audio++;
    }
    $upd_sent = $pdo->prepare("UPDATE verbs SET sentence_audio_path = ? WHERE global_num = ? AND (sentence_audio_path IS NULL OR sentence_audio_path != ?)");
    $updated_sentence = 0;
    foreach (glob($BASE . '/asset/audio/day */*/sentence.mp3') ?: [] as $f) {
        $rel = str_replace('\\', '/', substr($f, strlen($BASE) + 1));
        if (!preg_match('#asset/audio/day \d+/(\d+)\.#', $rel, $m)) continue;
        $upd_sent->execute([$rel, (int)$m[1], $rel]);
        if ($upd_sent->rowCount()) $updated_sentence++;
    }
    return compact('updated_img', 'updated_audio', 'updated_sentence', 'skipped');
}

// file_exists() 완전 제거 — DB의 image_path/audio_path 유무로만 판단
// (index.php 와 동일한 방식, GCP file_exists 병목 해소)
$rows = $pdo->query("
    SELECT d.day_number, v.global_num, v.verb_en, v.verb_kr,
           e.expression_en, e.image_path, e.audio_path
    FROM days d JOIN verbs v ON v.day_id = d.id JOIN expressions e ON e.verb_id = v.id
    ORDER BY d.day_number, v.order_num, e.order_num
")->fetchAll();

$all_days = [];
$g_total = $g_img = $g_audio = 0;
foreach ($rows as $r) {
    $dn = (int)$r['day_number'];
    if (!isset($all_days[$dn])) $all_days[$dn] = ['total' => 0, 'img' => 0, 'audio' => 0, 'verbs' => []];
    $all_days[$dn]['total']++;
    if (!in_array($r['verb_en'], $all_days[$dn]['verbs'])) $all_days[$dn]['verbs'][] = $r['verb_en'];
    if (!empty($r['image_path']))  { $all_days[$dn]['img']++;   $g_img++; }
    if (!empty($r['audio_path']))  { $all_days[$dn]['audio']++; $g_audio++; }
    $g_total++;
}
$max_day = count($all_days);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>전체 현황 · 청킹잉글리시 관리자</title>
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
    --yellow:  #d97706;
    --text:    #1a1a2e;
    --muted:   #78716c;
    --white:   #ffffff;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Noto Sans KR', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; font-size: 14px; }

header {
    background: var(--pink-dk); color: #fff;
    padding: 0 28px; display: flex; align-items: stretch;
    border-bottom: 1px solid rgba(0,0,0,.1);
}
.hd-title { padding: 14px 0; flex: 1; }
.hd-title h1 { font-size: 1rem; font-weight: 700; letter-spacing: -.3px; }
.hd-title .sub { font-size: .75rem; opacity: .7; margin-top: 2px; }
nav { display: flex; align-items: center; }
nav a {
    display: inline-flex; align-items: center; height: 100%; padding: 0 16px;
    font-size: .82rem; font-weight: 500; color: rgba(255,255,255,.75);
    text-decoration: none; border-bottom: 2px solid transparent; transition: color .12s, border-color .12s;
}
nav a:hover { color: #fff; }
nav a.active { color: #fff; border-bottom-color: #fff; }

.container { max-width: 1060px; margin: 0 auto; padding: 20px; }

/* ── 동기화 ── */
.sync-box {
    background: var(--white); border: 1px solid var(--border); border-radius: 8px;
    padding: 16px 20px; margin-bottom: 16px;
    display: flex; align-items: flex-start; gap: 24px; flex-wrap: wrap;
}
.sync-desc h3 { font-size: .85rem; font-weight: 600; color: var(--text); margin-bottom: 4px; }
.sync-desc p  { font-size: .76rem; color: var(--muted); line-height: 1.6; }
.sync-desc code { font-family: monospace; font-size: .74rem; background: #f0eced; padding: 1px 4px; border-radius: 3px; }
.sync-ctrl { display: flex; flex-direction: column; gap: 8px; min-width: 140px; }
.btn-sync {
    display: inline-flex; align-items: center; justify-content: center;
    background: var(--pink-dk); color: #fff; border: none; border-radius: 6px;
    padding: 7px 16px; font-size: .8rem; font-family: inherit; font-weight: 500; cursor: pointer;
    transition: background .12s;
}
.btn-sync:hover { background: #a84860; }
.sync-result { font-size: .76rem; }
.sr-row { display: flex; justify-content: space-between; gap: 12px; padding: 2px 0; border-bottom: 1px solid #f0eced; color: var(--muted); }
.sr-row:last-child { border-bottom: none; }
.n-ok { color: var(--green); font-weight: 600; }

/* ── 통계 카드 ── */
.stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
.sc {
    background: var(--white); border: 1px solid var(--border); border-radius: 8px;
    padding: 14px 16px;
}
.sc .n { font-size: 1.6rem; font-weight: 700; color: var(--pink-dk); line-height: 1; margin-bottom: 4px; }
.sc .l { font-size: .74rem; color: var(--muted); }
.sc .s { font-size: .76rem; color: var(--muted); margin-top: 3px; }

/* ── 섹션 타이틀 ── */
.sec-title {
    font-size: .85rem; font-weight: 600; color: var(--text);
    margin-bottom: 12px; padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.legend { display: flex; gap: 10px; font-size: .72rem; color: var(--muted); font-weight: 400; }
.legend span { display: flex; align-items: center; gap: 4px; }
.ldot { width: 8px; height: 8px; border-radius: 50%; }

/* ── Day 그리드 ── */
.days-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; }
.dc {
    background: var(--white); border: 1px solid var(--border); border-radius: 8px;
    padding: 10px 12px; display: block; text-decoration: none; color: inherit;
    transition: border-color .12s, box-shadow .12s;
}
.dc:hover { border-color: var(--pink-lt); box-shadow: 0 2px 8px rgba(0,0,0,.07); }

.dc-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.dc-num { font-size: .82rem; font-weight: 700; }
.dc-dot { width: 8px; height: 8px; border-radius: 50%; }

.dc-verbs { font-size: .65rem; color: var(--muted); margin-bottom: 7px; line-height: 1.5; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.dc-row { display: flex; justify-content: space-between; font-size: .68rem; color: var(--muted); margin-bottom: 2px; }
.dc-bar { height: 3px; border-radius: 2px; background: #ede0e3; overflow: hidden; margin-bottom: 5px; }
.dc-fill { height: 100%; border-radius: 2px; }
</style>
</head>
<body>

<header>
    <div class="hd-title">
        <h1>청킹잉글리시 관리자</h1>
        <div class="sub">전체 현황 · 파일 동기화</div>
    </div>
    <nav>
        <a href="dashboard.php">대시보드</a>
        <a href="index.php">Day 목록</a>
        <a href="overview.php" class="active">전체 현황</a>
        <a href="organizations.php">지자체 관리</a>
        <a href="users.php">사용자 관리</a>
        <a href="generate_audio.php">오디오 생성</a>
        <a href="debug.php">디버그</a>
        <a href="book-check.php">청킹E-book</a>
    </nav>
</header>

<div class="container">

    <!-- 동기화 -->
    <div class="sync-box">
        <div class="sync-desc">
            <h3>파일 동기화</h3>
            <p>
                <code>asset/img/</code> · <code>asset/audio/</code> 폴더를 스캔해 DB 경로를 갱신합니다.<br>
                MP3를 폴더에 넣은 후 실행하세요. &nbsp;
                경로 규칙: <code>asset/audio/day N/NN. verb/expression_slug.mp3</code>
            </p>
        </div>
        <div class="sync-ctrl">
            <form method="POST">
                <input type="hidden" name="action" value="sync">
                <button type="submit" class="btn-sync">동기화 실행</button>
            </form>
            <?php if ($sync_result !== null): ?>
            <div class="sync-result">
                <div class="sr-row"><span>이미지 갱신</span><span class="n-ok"><?= $sync_result['updated_img'] ?>건</span></div>
                <div class="sr-row"><span>오디오 갱신</span><span class="n-ok"><?= $sync_result['updated_audio'] ?>건</span></div>
                <div class="sr-row"><span>예문 오디오</span><span class="n-ok"><?= $sync_result['updated_sentence'] ?>건</span></div>
                <div class="sr-row"><span>미매칭</span><span><?= $sync_result['skipped'] ?>건</span></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 통계 -->
    <div class="stat-row">
        <div class="sc">
            <div class="n"><?= $max_day ?>일</div>
            <div class="l">등록 Day 수</div>
            <div class="s">표현 <?= $g_total ?>개</div>
        </div>
        <div class="sc">
            <div class="n" style="color:<?= $g_img===$g_total?'var(--green)':'var(--yellow)' ?>"><?= $g_img ?></div>
            <div class="l">이미지 완료</div>
            <div class="s"><?= $g_total ?>개 중 · <?= round($g_img/max(1,$g_total)*100) ?>%</div>
        </div>
        <div class="sc">
            <div class="n" style="color:<?= $g_audio===$g_total?'var(--green)':($g_audio===0?'var(--red)':'var(--yellow)') ?>"><?= $g_audio ?></div>
            <div class="l">MP3 완료</div>
            <div class="s"><?= $g_total ?>개 중 · <?= round($g_audio/max(1,$g_total)*100) ?>%</div>
        </div>
    </div>

    <!-- 전체 현황 그리드 -->
    <div class="sec-title">
        <span>전체 <?= $max_day ?>일 현황</span>
        <div class="legend">
            <span><span class="ldot" style="background:var(--green)"></span>완료</span>
            <span><span class="ldot" style="background:var(--yellow)"></span>일부</span>
            <span><span class="ldot" style="background:#e2d9db"></span>없음</span>
        </div>
    </div>
    <div class="days-grid">
    <?php foreach ($all_days as $dn => $info):
        $t    = max(1, $info['total']);
        $ip   = (int)round($info['img']   / $t * 100);
        $ap   = (int)round($info['audio'] / $t * 100);
        $iclr = $ip >= 100 ? 'var(--green)' : ($ip === 0 ? '#e2d9db' : 'var(--yellow)');
        $aclr = $ap >= 100 ? 'var(--green)' : ($ap === 0 ? '#e2d9db' : 'var(--yellow)');
        $dclr = ($ip >= 100 && $ap >= 100) ? 'var(--green)' : (($ip === 0 && $ap === 0) ? '#e2d9db' : 'var(--yellow)');
    ?>
        <a class="dc" href="index.php#day-<?= $dn ?>">
            <div class="dc-top">
                <span class="dc-num">Day <?= $dn ?></span>
                <span class="dc-dot" style="background:<?= $dclr ?>"></span>
            </div>
            <div class="dc-verbs"><?= htmlspecialchars(implode(' · ', $info['verbs'])) ?></div>
            <div class="dc-row"><span>이미지</span><span><?= $info['img'] ?>/<?= $info['total'] ?></span></div>
            <div class="dc-bar"><div class="dc-fill" style="width:<?= $ip ?>%;background:<?= $iclr ?>"></div></div>
            <div class="dc-row"><span>MP3</span><span><?= $info['audio'] ?>/<?= $info['total'] ?></span></div>
            <div class="dc-bar"><div class="dc-fill" style="width:<?= $ap ?>%;background:<?= $aclr ?>"></div></div>
        </a>
    <?php endforeach; ?>
    </div>

</div>
<a href="../index.php" title="학습 메인으로" style="position:fixed;bottom:20px;left:20px;z-index:9999;display:flex;align-items:center;gap:8px;background:#C75A6F;color:#fff;border-radius:980px;padding:8px 16px 8px 8px;text-decoration:none;font-size:.8rem;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.18);transition:opacity .2s;" onmouseover="this.style.opacity=.85" onmouseout="this.style.opacity=1"><img src="../img/ck_train.png" style="width:28px;height:28px;object-fit:contain;border-radius:50%;background:#fff;padding:2px;">학습 홈</a>
</body>
</html>
