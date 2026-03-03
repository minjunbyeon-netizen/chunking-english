<?php
/**
 * POST /api/progress/save.php
 * Body: { day_number }
 * 학습 완료 저장
 */
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '로그인이 필요합니다.']);
    exit;
}

$data       = json_decode(file_get_contents('php://input'), true);
$day_number = intval($data['day_number'] ?? 0);

if ($day_number < 1) {
    http_response_code(400);
    echo json_encode(['error' => '유효하지 않은 Day입니다.']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM days WHERE day_number = ?");
$stmt->execute([$day_number]);
$day = $stmt->fetch();

if (!$day) {
    http_response_code(404);
    echo json_encode(['error' => '해당 Day가 없습니다.']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO progress (user_id, day_id, completed)
    VALUES (?, ?, 1)
    ON DUPLICATE KEY UPDATE completed = 1, studied_at = NOW()
");
$stmt->execute([$_SESSION['user_id'], $day['id']]);

echo json_encode(['success' => true]);
