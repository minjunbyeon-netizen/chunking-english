<?php
/**
 * POST /api/stats/record_view.php
 * Body: { day_number }
 * 세션에서 user_id, org_id 추출 → page_views INSERT → 해당 day 총 조회수 반환
 */
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST만 허용']);
    exit;
}

$data       = json_decode(file_get_contents('php://input'), true);
$day_number = isset($data['day_number']) ? (int)$data['day_number'] : 0;

if ($day_number < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'day_number 필요']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$org_id  = $_SESSION['org_id']  ?? null;

$pdo->prepare("
    INSERT INTO page_views (day_number, user_id, org_id)
    VALUES (?, ?, ?)
")->execute([$day_number, $user_id ?: null, $org_id ?: null]);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM page_views WHERE day_number = ?");
$stmt->execute([$day_number]);
$total = (int)$stmt->fetchColumn();

echo json_encode(['success' => true, 'day_number' => $day_number, 'total_views' => $total]);
