<?php
/**
 * GET /api/download/day_mp3_merged.php?day=N
 * 해당 Day의 표현 MP3 21개(동사 3개 × 7표현)를 순서대로 이어붙여 단일 MP3로 다운로드
 */
session_start();
require_once '../../config/db.php';

$is_localhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
if (!$is_localhost && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '로그인이 필요합니다.']);
    exit;
}

$day_num = max(1, min(50, intval($_GET['day'] ?? 0)));
if (!$day_num) {
    http_response_code(400);
    echo json_encode(['error' => '올바른 Day를 입력해주세요.']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM days WHERE day_number = ? AND is_active = 1");
$stmt->execute([$day_num]);
$day = $stmt->fetch();
if (!$day) {
    http_response_code(404);
    echo json_encode(['error' => "Day {$day_num}을 찾을 수 없습니다."]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT e.audio_path
    FROM verbs v
    JOIN expressions e ON e.verb_id = v.id
    WHERE v.day_id = ? AND e.audio_path IS NOT NULL AND e.audio_path != ''
    AND v.verb_en REGEXP '^[a-zA-Z]'
    ORDER BY v.order_num, e.order_num
");
$stmt->execute([$day['id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    http_response_code(404);
    echo json_encode(['error' => "Day {$day_num}의 표현 데이터가 없습니다."]);
    exit;
}

$BASE = dirname(__DIR__, 2);

$mp3_files = [];
foreach ($rows as $row) {
    $path = $BASE . '/' . $row['audio_path'];
    if (file_exists($path)) {
        $mp3_files[] = $path;
    }
}

if (empty($mp3_files)) {
    http_response_code(404);
    echo json_encode(['error' => "Day {$day_num}의 MP3 파일이 없습니다."]);
    exit;
}

$merged = '';
foreach ($mp3_files as $path) {
    $merged .= file_get_contents($path);
}

$filename = "chunking_day{$day_num}_audio.mp3";
header('Content-Type: audio/mpeg');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($merged));
header('Cache-Control: no-cache');

echo $merged;
exit;
