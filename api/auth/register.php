<?php
/**
 * POST /api/auth/register.php
 * Body: { email, password, nickname, org_id }
 * → 지자체 검증 후 가입, 인증 이메일 발송, email_verified=0 상태로 저장
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
$org_id   = isset($data['org_id']) ? (int)$data['org_id'] : 0;

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
if (!$org_id) {
    http_response_code(400);
    echo json_encode(['error' => '소속 지자체를 선택해주세요.']);
    exit;
}

// 지자체 검증 (활성 · 만료 · 인원 초과)
$stmt = $pdo->prepare("SELECT id, is_active, expires_at, max_users FROM organizations WHERE id = ?");
$stmt->execute([$org_id]);
$org = $stmt->fetch();

if (!$org || !$org['is_active']) {
    http_response_code(400);
    echo json_encode(['error' => '유효하지 않은 지자체입니다.']);
    exit;
}
if ($org['expires_at'] && strtotime($org['expires_at']) < time()) {
    http_response_code(400);
    echo json_encode(['error' => '계약이 만료된 지자체입니다. 담당자에게 문의해주세요.']);
    exit;
}
$cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE org_id = ?");
$cnt_stmt->execute([$org_id]);
if ((int)$cnt_stmt->fetchColumn() >= (int)$org['max_users']) {
    http_response_code(400);
    echo json_encode(['error' => '해당 지자체의 가입 가능 인원이 초과되었습니다.']);
    exit;
}

// 이메일 중복 확인
$stmt = $pdo->prepare("SELECT id, email_verified FROM users WHERE email = ?");
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    if (!$existing['email_verified']) {
        // 미인증 계정 → 인증 메일 재발송
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $pdo->prepare("UPDATE users SET verification_token=?, token_expires_at=? WHERE email=?")
            ->execute([$token, $expires, $email]);
        send_verification_email($email, $nickname ?: $email, $token);
        echo json_encode(['success' => true, 'message' => '인증 이메일을 재발송했습니다. 메일함을 확인해주세요.']);
    } else {
        http_response_code(409);
        echo json_encode(['error' => '이미 사용 중인 이메일입니다.']);
    }
    exit;
}

// 토큰 생성 (24시간 유효)
$token   = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
$hashed  = password_hash($password, PASSWORD_BCRYPT);

// 회원 등록 (미인증 상태)
$stmt = $pdo->prepare("
    INSERT INTO users (email, password, nickname, email_verified, verification_token, token_expires_at, org_id)
    VALUES (?, ?, ?, 0, ?, ?, ?)
");
$stmt->execute([$email, $hashed, $nickname ?: null, $token, $expires, $org_id]);

// 인증 이메일 발송
$sent = send_verification_email($email, $nickname ?: $email, $token);

if (!$sent) {
    // 이메일 발송 실패해도 가입은 완료 (재발송 가능)
    echo json_encode(['success' => true, 'message' => '가입은 완료됐으나 이메일 발송에 실패했습니다. 잠시 후 다시 시도해주세요.']);
    exit;
}

echo json_encode(['success' => true, 'message' => '인증 이메일을 발송했습니다. 메일함을 확인해주세요.']);


// ── 인증 이메일 발송 함수 ─────────────────────────────────────────────────
function send_verification_email(string $to, string $name, string $token): bool
{
    $link    = APP_URL . '/verify_email.php?token=' . $token;
    $subject = '[청킹잉글리시] 이메일 인증을 완료해주세요';
    $html    = <<<HTML
<!DOCTYPE html>
<html lang="ko">
<body style="font-family:'Apple SD Gothic Neo',sans-serif;background:#FFF5F7;margin:0;padding:40px 20px;">
  <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:20px;padding:40px;border:2px solid #FFD0D9;">
    <h1 style="color:#C75A6F;font-size:1.4rem;margin-bottom:8px;">청킹잉글리시 이메일 인증</h1>
    <p style="color:#555;line-height:1.7;">안녕하세요, <strong>{$name}</strong>님!<br>
    아래 버튼을 클릭하면 이메일 인증이 완료됩니다.</p>
    <div style="text-align:center;margin:32px 0;">
      <a href="{$link}"
         style="display:inline-block;background:#FF8FA3;color:#fff;text-decoration:none;
                padding:14px 36px;border-radius:14px;font-size:1rem;font-weight:600;
                border:2px solid #2D2D2D;box-shadow:4px 4px 0 #2D2D2D;">
        이메일 인증하기
      </a>
    </div>
    <p style="color:#999;font-size:.82rem;">링크는 24시간 동안 유효합니다.<br>
    본인이 요청하지 않았다면 이 메일을 무시해주세요.</p>
  </div>
</body>
</html>
HTML;

    $payload = json_encode([
        'from'    => MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'to'      => [$to],
        'subject' => $subject,
        'html'    => $html,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . RESEND_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code === 200 || $code === 201;
}
