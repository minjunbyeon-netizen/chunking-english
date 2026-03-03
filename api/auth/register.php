<?php
/**
 * POST /api/auth/register.php
 * Body: { email, password, nickname }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST 요청만 허용됩니다.']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');
$nickname = trim($data['nickname'] ?? '');

// 유효성 검사
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => '유효한 이메일을 입력해주세요.']);
    exit;
}
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => '비밀번호는 6자 이상이어야 합니다.']);
    exit;
}

// 이메일 중복 확인
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => '이미 사용 중인 이메일입니다.']);
    exit;
}

// 회원 등록
$hashed = password_hash($password, PASSWORD_BCRYPT);
$stmt   = $pdo->prepare("INSERT INTO users (email, password, nickname) VALUES (?, ?, ?)");
$stmt->execute([$email, $hashed, $nickname ?: null]);

echo json_encode(['success' => true, 'message' => '회원가입이 완료되었습니다.']);
