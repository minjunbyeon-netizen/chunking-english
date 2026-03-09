<?php
require_once '_auth.php'; // _auth.php가 db.php 포함

header('Content-Type: text/html; charset=utf-8');

$day = isset($_GET['day']) ? (int)$_GET['day'] : 0;
if ($day <= 0) { http_response_code(400); echo 'invalid'; exit; }

function web_url_d(string $rel): string {
    return '../' . implode('/', array_map('rawurlencode', explode('/', str_replace('\\', '/', $rel))));
}

$rows = $pdo->prepare("
    SELECT v.order_num AS v_order, v.global_num, v.verb_en, v.verb_kr,
           e.order_num AS e_order, e.expression_en, e.expression_kr,
           e.image_path, e.audio_path
    FROM days d
    JOIN verbs v ON v.day_id = d.id
    JOIN expressions e ON e.verb_id = v.id
    WHERE d.day_number = ?
    ORDER BY v.order_num, e.order_num
");
$rows->execute([$day]);
$rows = $rows->fetchAll();

if (!$rows) { echo '<p style="padding:16px;color:#78716c">데이터 없음</p>'; exit; }

// Group by verb
$verbs = [];
foreach ($rows as $r) {
    $gv = (int)$r['global_num'];
    if (!isset($verbs[$gv])) {
        $verbs[$gv] = [
            'global_num'  => $gv,
            'verb_en'     => $r['verb_en'],
            'verb_kr'     => $r['verb_kr'],
            'expressions' => [],
        ];
    }
    $img_url = $audio_url = '';
    $img_exists = $audio_exists = false;
    if (!empty($r['image_path'])) {
        $img_exists = true;
        $img_url = web_url_d(str_replace('\\', '/', $r['image_path']));
    }
    if (!empty($r['audio_path'])) {
        $audio_exists = true;
        $audio_url = web_url_d(str_replace('\\', '/', $r['audio_path']));
    }
    $verbs[$gv]['expressions'][] = [
        'expression_en' => $r['expression_en'],
        'expression_kr' => $r['expression_kr'] ?? '',
        'img_exists'    => $img_exists,
        'img_url'       => $img_url,
        'audio_exists'  => $audio_exists,
        'audio_url'     => $audio_url,
    ];
}
?>
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
            <td style="color:var(--muted)"><?= htmlspecialchars($e['expression_kr']) ?></td>
            <td>
                <div class="img-cell">
                    <?php if ($e['img_exists']): ?>
                        <img class="thumb" src="<?= htmlspecialchars($e['img_url']) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <span class="dot <?= $e['img_exists'] ? 'dot-ok' : 'dot-no' ?>"></span>
                </div>
            </td>
            <td style="text-align:center">
                <?php if ($e['audio_exists']): ?>
                    <button class="play-btn" onclick="playAudio(this)" data-src="<?= htmlspecialchars($e['audio_url']) ?>" title="재생">
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
