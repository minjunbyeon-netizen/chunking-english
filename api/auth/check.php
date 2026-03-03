<?php
/**
 * GET /api/auth/check.php
 * 현재 로그인 상태 반환
 */
header('Content-Type: application/json; charset=utf-8');

session_start();

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'user_id'   => $_SESSION['user_id'],
        'nickname'  => $_SESSION['nickname'],
        'role'      => $_SESSION['user_role'],
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
