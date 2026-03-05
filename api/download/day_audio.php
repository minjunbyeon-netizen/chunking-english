<?php
/**
 * GET /api/download/day_audio.php?day=N
 * 해당 Day의 MP3 파일 전체를 ZIP으로 묶어 다운로드
 * - 로그인한 사용자만 허용
 */
session_start();
require_once '../../config/db.php';

// ── 로그인 확인 (localhost는 관리자 테스트용으로 통과) ─────────
$is_localhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
if (!$is_localhost && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => '로그인이 필요합니다.']);
    exit;
}

// ── Day 파라미터 ──────────────────────────────────────────────
$day_num = max(1, min(250, intval($_GET['day'] ?? 0)));
if (!$day_num) {
    http_response_code(400);
    echo json_encode(['error' => '올바른 Day를 입력해주세요.']);
    exit;
}

// ── 오디오 폴더 경로 ──────────────────────────────────────────
$BASE      = dirname(__DIR__, 2);
$audio_dir = $BASE . DIRECTORY_SEPARATOR . 'asset' . DIRECTORY_SEPARATOR . 'audio' . DIRECTORY_SEPARATOR . "day {$day_num}";

if (!is_dir($audio_dir)) {
    http_response_code(404);
    echo json_encode(['error' => "Day {$day_num}의 오디오 파일이 없습니다."]);
    exit;
}

// ── MP3 파일 수집 ─────────────────────────────────────────────
$files = [];
$iter  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($audio_dir));
foreach ($iter as $file) {
    if ($file->isFile() && strtolower($file->getExtension()) === 'mp3') {
        $files[] = $file->getPathname();
    }
}

if (empty($files)) {
    http_response_code(404);
    echo json_encode(['error' => "Day {$day_num}의 MP3 파일이 없습니다."]);
    exit;
}

// ── ZIP 생성 (임시파일) ───────────────────────────────────────
$zip_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "chunking_day{$day_num}_audio.zip";

$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo json_encode(['error' => 'ZIP 생성에 실패했습니다.']);
    exit;
}

foreach ($files as $filepath) {
    // ZIP 내부 경로: verb폴더/파일명.mp3
    $rel = str_replace($audio_dir . DIRECTORY_SEPARATOR, '', $filepath);
    $rel = str_replace('\\', '/', $rel);
    $zip->addFile($filepath, $rel);
}
$zip->close();

// ── 다운로드 스트리밍 ─────────────────────────────────────────
$filename = "chunking_day{$day_num}_audio.zip";
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($zip_path));
header('Cache-Control: no-cache');

readfile($zip_path);
unlink($zip_path); // 임시파일 삭제
exit;
