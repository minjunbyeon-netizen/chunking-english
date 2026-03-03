<?php
// ─── config/db.php 설정 방법 ───────────────────────────────────
// 이 파일을 복사해서 config/db.php 로 저장 후 본인 환경에 맞게 수정
//   cp config/db.example.php config/db.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // XAMPP 기본값
define('DB_PASS', '');           // XAMPP 기본값 (비밀번호 없음)
define('DB_NAME', 'chunking_english');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB 연결 실패']);
    exit;
}
