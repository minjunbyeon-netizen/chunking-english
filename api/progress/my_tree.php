<?php
/**
 * GET /api/progress/my_tree.php
 * 로그인 유저의 학습 진도 반환 (tree.php 연동용)
 */
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => false, 'completed_days' => [], 'total' => 0]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT d.day_number
    FROM progress p
    JOIN days d ON p.day_id = d.id
    WHERE p.user_id = ? AND p.completed = 1
    ORDER BY d.day_number
");
$stmt->execute([$_SESSION['user_id']]);
$rows = $stmt->fetchAll();

$completed = array_column($rows, 'day_number');

echo json_encode([
    'logged_in'      => true,
    'completed_days' => $completed,
    'total'          => count($completed),
], JSON_UNESCAPED_UNICODE);
