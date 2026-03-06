<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 로그인 확인
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('로그인이 필요합니다.');
}

$day = (int)($_GET['day'] ?? 0);
if ($day < 1) {
    http_response_code(400);
    exit('잘못된 요청입니다.');
}

$file = dirname(__DIR__, 2) . '/asset/pdf/day_' . $day . '.pdf';

if (!file_exists($file)) {
    http_response_code(404);
    exit('Day ' . $day . ' PDF 자료가 아직 준비되지 않았습니다.');
}

$filename = 'chunking_day' . $day . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: no-cache');
readfile($file);
