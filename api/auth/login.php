<?php
/**
 * POST /api/auth/login.php
 * Body: { email, password }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST 요청만 허용됩니다.']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => '이메일과 비밀번호를 입력해주세요.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => '이메일 또는 비밀번호가 올바르지 않습니다.']);
    exit;
}

if (!$user['email_verified']) {
    http_response_code(403);
    echo json_encode(['error' => '이메일 인증이 필요합니다. 가입 시 받은 인증 메일을 확인해주세요.']);
    exit;
}

// 세션 고정 공격 방지
session_regenerate_id(true);

$_SESSION['user_id']   = $user['id'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['nickname']  = $user['nickname'];
$_SESSION['org_id']    = $user['org_id'];

echo json_encode([
    'success'  => true,
    'nickname' => $user['nickname'],
    'role'     => $user['role'],
]);
