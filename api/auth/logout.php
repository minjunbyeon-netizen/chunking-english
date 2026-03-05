<?php
/**
 * GET or POST /api/auth/logout.php
 * GET: 세션 삭제 후 index.php로 리다이렉트
 * POST: JSON 응답
 */
session_start();
session_destroy();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: /chunking-english/index.php');
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true]);
