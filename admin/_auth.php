<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 로그인 여부 확인
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// DB 연결 (require_once → 이후 admin 페이지에서 재포함해도 중복 없음)
require_once '../config/db.php';

// 관리자 role 확인
$_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$_stmt->execute([$_SESSION['user_id']]);
$_admin_user = $_stmt->fetch();

if (!$_admin_user || $_admin_user['role'] !== 'admin') {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="ko"><head><meta charset="UTF-8">
    <title>접근 거부</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center;
               min-height: 100vh; margin: 0; background: #F9F5F6; flex-direction: column; gap: 16px; }
        h2 { color: #C75A6F; }
        a { color: #C75A6F; text-decoration: none; font-weight: bold; }
    </style>
    </head><body>
    <h2>403 - 접근 권한 없음</h2>
    <p>관리자 계정으로 로그인해야 합니다.</p>
    <a href="../index.php">← 홈으로 돌아가기</a>
    </body></html>';
    exit;
}

unset($_stmt, $_admin_user);
