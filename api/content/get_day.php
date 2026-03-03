<?php
/**
 * GET /api/content/get_day.php?day=1
 *
 * Response JSON 구조:
 * {
 *   "day_number": 1,
 *   "verbs": [
 *     {
 *       "global_num": 1,
 *       "verb_en": "have",
 *       "verb_kr": "가지다",
 *       "sentence_en": "I have a dream.",
 *       "sentence_kr": "나는 가지다 꿈을",
 *       "sentence_audio_url": null | "asset/audio/...",
 *       "expressions": [
 *         {
 *           "order_num": 1,
 *           "expression_en": "have a dream",
 *           "expression_kr": "가지다 꿈을",
 *           "image_url":  null | "/chunking-english/asset/img/...",
 *           "audio_url":  null | "/chunking-english/asset/audio/..."
 *         }
 *       ]
 *     }
 *   ]
 * }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../../config/db.php';

$day_number = isset($_GET['day']) ? intval($_GET['day']) : 1;
if ($day_number < 1 || $day_number > 9999) {
    http_response_code(400);
    echo json_encode(['error' => '유효하지 않은 Day 번호입니다.']);
    exit;
}

// ── Day 조회 ──────────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM days WHERE day_number = ? AND is_active = 1");
$stmt->execute([$day_number]);
$day = $stmt->fetch();

if (!$day) {
    http_response_code(404);
    echo json_encode(['error' => '해당 Day를 찾을 수 없습니다.']);
    exit;
}

// ── 웹 URL 생성 헬퍼 ─────────────────────────────────────────────────────────
$BASE     = dirname(__DIR__, 2);
$WEB_ROOT = APP_BASE;   // config/db.php 의 APP_BASE 값 사용

function make_url(?string $path): ?string {
    global $WEB_ROOT, $BASE;
    if (!$path) return null;
    $clean = str_replace('\\', '/', $path);
    // DB에 저장된 경로 → 실제 파일 존재 확인 → 웹 URL 반환
    if (file_exists($BASE . '/' . $clean)) {
        $parts = explode('/', $clean);
        return $WEB_ROOT . '/' . implode('/', array_map('rawurlencode', $parts));
    }
    return null;
}

function rule_img_url(string $expression_en, int $global_num, string $verb_en, int $day): ?string {
    global $WEB_ROOT, $BASE;
    $gv   = str_pad($global_num, 2, '0', STR_PAD_LEFT);
    $slug = str_replace(' ', '_', $expression_en);
    $rel  = "asset/img/day {$day}/{$gv}. {$verb_en}/{$slug}.png";
    if (!file_exists($BASE . '/' . $rel)) return null;
    $parts = explode('/', $rel);
    return $WEB_ROOT . '/' . implode('/', array_map('rawurlencode', $parts));
}

function rule_audio_url(string $expression_en, int $global_num, string $verb_en, int $day): ?string {
    global $WEB_ROOT, $BASE;
    $gv   = str_pad($global_num, 2, '0', STR_PAD_LEFT);
    $slug = str_replace(' ', '_', $expression_en);
    $rel  = "asset/audio/day {$day}/{$gv}. {$verb_en}/{$slug}.mp3";
    if (!file_exists($BASE . '/' . $rel)) return null;
    $parts = explode('/', $rel);
    return $WEB_ROOT . '/' . implode('/', array_map('rawurlencode', $parts));
}

// ── 동사 조회 ─────────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT id, order_num, global_num, verb_en, verb_kr,
           sentence_en, sentence_kr, sentence_audio_path
    FROM verbs
    WHERE day_id = ?
    ORDER BY order_num
");
$stmt->execute([$day['id']]);
$verbs = $stmt->fetchAll();

// ── 표현 조회 + URL 조립 ──────────────────────────────────────────────────────
foreach ($verbs as &$verb) {
    // 예문 오디오
    $verb['sentence_audio_url'] = make_url($verb['sentence_audio_path'])
        ?? rule_audio_url('sentence', $verb['global_num'], $verb['verb_en'], $day_number);
    unset($verb['sentence_audio_path'], $verb['id']);

    $stmt = $pdo->prepare("
        SELECT order_num, expression_en, expression_kr, image_path, audio_path
        FROM expressions
        WHERE verb_id = ?
        ORDER BY order_num
    ");
    $stmt->execute([$verb['id'] ?? 0]);

    // verb['id']를 방금 unset 했으니 다시 가져와야 함 → 아래에서 처리
}
unset($verb);

// id 제거 전에 expressions 조회
$stmt_v = $pdo->prepare("
    SELECT id, order_num, global_num, verb_en, verb_kr,
           sentence_en, sentence_kr, sentence_audio_path
    FROM verbs WHERE day_id = ? ORDER BY order_num
");
$stmt_v->execute([$day['id']]);
$verbs = $stmt_v->fetchAll();

$stmt_e = $pdo->prepare("
    SELECT order_num, expression_en, expression_kr, image_path, audio_path
    FROM expressions WHERE verb_id = ? ORDER BY order_num
");

foreach ($verbs as &$verb) {
    $stmt_e->execute([$verb['id']]);
    $expressions = $stmt_e->fetchAll();

    foreach ($expressions as &$expr) {
        // 이미지 URL: DB 경로 우선, 없으면 규칙 기반
        $expr['image_url'] = make_url($expr['image_path'])
            ?? rule_img_url($expr['expression_en'], $verb['global_num'], $verb['verb_en'], $day_number);

        // 오디오 URL: DB 경로 우선, 없으면 규칙 기반
        $expr['audio_url'] = make_url($expr['audio_path'])
            ?? rule_audio_url($expr['expression_en'], $verb['global_num'], $verb['verb_en'], $day_number);

        unset($expr['image_path'], $expr['audio_path']);
    }
    unset($expr);

    $verb['sentence_audio_url'] = make_url($verb['sentence_audio_path'])
        ?? rule_audio_url('sentence', $verb['global_num'], $verb['verb_en'], $day_number);

    $verb['expressions'] = $expressions;

    // 프론트엔드에 불필요한 내부 필드 제거
    unset($verb['id'], $verb['day_id'], $verb['sentence_audio_path']);
}
unset($verb);

echo json_encode([
    'day_number' => $day['day_number'],
    'verbs'      => $verbs,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
