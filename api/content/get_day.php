<?php
/**
 * GET /api/content/get_day.php?day=1
 * 특정 Day의 전체 콘텐츠 반환
 * Response: { day, verbs: [{ verb, expressions: [{ expression_en, expression_kr, image_url }] }] }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../../config/db.php';

$day_number = isset($_GET['day']) ? intval($_GET['day']) : 1;

if ($day_number < 1 || $day_number > 250) {
    http_response_code(400);
    echo json_encode(['error' => '유효하지 않은 Day 번호입니다.']);
    exit;
}

// Day 조회
$stmt = $pdo->prepare("SELECT * FROM days WHERE day_number = ? AND is_active = 1");
$stmt->execute([$day_number]);
$day = $stmt->fetch();

if (!$day) {
    http_response_code(404);
    echo json_encode(['error' => '해당 Day를 찾을 수 없습니다.']);
    exit;
}

// 동사 3개 조회
$stmt = $pdo->prepare("
    SELECT id, order_num, global_num, verb_en, verb_kr, sentence_en, sentence_kr
    FROM verbs
    WHERE day_id = ?
    ORDER BY order_num
");
$stmt->execute([$day['id']]);
$verbs = $stmt->fetchAll();

// 각 동사의 표현 7개 조회
foreach ($verbs as &$verb) {
    $stmt = $pdo->prepare("
        SELECT order_num, expression_en, expression_kr, image_path
        FROM expressions
        WHERE verb_id = ?
        ORDER BY order_num
    ");
    $stmt->execute([$verb['id']]);
    $expressions = $stmt->fetchAll();

    // 이미지 URL 변환 (path → 웹 URL)
    foreach ($expressions as &$expr) {
        if ($expr['image_path']) {
            // 경로 슬래시 통일 + 공백 URL 인코딩
            $parts    = explode('/', str_replace('\\', '/', $expr['image_path']));
            $encoded  = array_map('rawurlencode', $parts);
            $expr['image_url'] = '/03_chunking/' . implode('/', $encoded);
        } else {
            $expr['image_url'] = null;
        }
        unset($expr['image_path']);
    }
    unset($expr);

    $verb['expressions'] = $expressions;
}
unset($verb);

echo json_encode([
    'day_number' => $day['day_number'],
    'verbs'      => $verbs,
], JSON_UNESCAPED_UNICODE);
