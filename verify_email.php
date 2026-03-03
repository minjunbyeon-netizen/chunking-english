<?php
require_once 'config/db.php';

$token  = trim($_GET['token'] ?? '');
$status = 'invalid'; // invalid | expired | already | ok

if ($token) {
    $stmt = $pdo->prepare("SELECT id, email_verified, token_expires_at FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $status = 'invalid';
    } elseif ($user['email_verified']) {
        $status = 'already';
    } elseif (new DateTime() > new DateTime($user['token_expires_at'])) {
        $status = 'expired';
    } else {
        $pdo->prepare("UPDATE users SET email_verified=1, verification_token=NULL, token_expires_at=NULL WHERE id=?")
            ->execute([$user['id']]);
        $status = 'ok';
    }
}

$messages = [
    'ok'      => ['title' => '인증 완료!',           'sub' => '이메일 인증이 완료됐습니다. 이제 로그인할 수 있어요.',         'color' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#bbf7d0'],
    'already' => ['title' => '이미 인증된 계정',     'sub' => '이미 이메일 인증이 완료된 계정입니다.',                        'color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
    'expired' => ['title' => '링크가 만료됐습니다',  'sub' => '인증 링크가 만료됐습니다. 다시 회원가입하거나 재발송해주세요.', 'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a'],
    'invalid' => ['title' => '유효하지 않은 링크',   'sub' => '잘못된 인증 링크입니다. 메일을 다시 확인해주세요.',            'color' => '#dc2626', 'bg' => '#fff1f2', 'border' => '#fecdd3'],
];
$m = $messages[$status];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>이메일 인증 · 청킹잉글리시</title>
<link href="https://fonts.googleapis.com/css2?family=Chewy&family=Jua&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Jua', sans-serif;
    background: #FFF5F7;
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
}
.login-bg {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background-image: radial-gradient(#FFE4E8 2px, transparent 2px), linear-gradient(to bottom, #FFF5F7 0%, #FFE4E8 100%);
    background-size: 30px 30px, 100% 100%;
    z-index: -1;
}
.card {
    width: 100%; max-width: 420px;
    background: #fff;
    border: 4px solid #2D2D2D;
    border-radius: 40px;
    padding: 48px 40px;
    box-shadow: 12px 12px 0px #2D2D2D;
    text-align: center;
}
.icon-box {
    width: 72px; height: 72px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 2rem; margin-bottom: 20px;
    background: <?= $m['bg'] ?>; border: 2px solid <?= $m['border'] ?>;
}
h1 { font-family: 'Chewy', cursive; font-size: 1.8rem; color: <?= $m['color'] ?>; margin-bottom: 12px; }
p  { color: #555; font-size: .95rem; line-height: 1.7; margin-bottom: 28px; }
.btn {
    display: inline-block;
    background: #FF8FA3; color: #fff; text-decoration: none;
    padding: 14px 36px; border-radius: 14px;
    font-family: 'Chewy', cursive; font-size: 1.1rem; letter-spacing: .5px;
    border: 2px solid #2D2D2D; box-shadow: 4px 4px 0 #2D2D2D;
    transition: all .1s;
}
.btn:active { transform: translateY(3px); box-shadow: 1px 1px 0 #2D2D2D; }
</style>
</head>
<body>
<div class="login-bg"></div>
<div class="card">
    <div class="icon-box">
        <?= $status === 'ok' ? '✅' : ($status === 'already' ? '✉️' : ($status === 'expired' ? '⏰' : '❌')) ?>
    </div>
    <h1><?= $m['title'] ?></h1>
    <p><?= $m['sub'] ?></p>
    <a href="./login.php" class="btn">로그인하러 가기</a>
</div>
</body>
</html>
