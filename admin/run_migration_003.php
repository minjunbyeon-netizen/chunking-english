<?php
// 1회용 마이그레이션 실행 파일 - 실행 후 즉시 삭제
require_once '_auth.php';
require_once '../config/db.php';

$sqls = [
    "ALTER TABLE organizations ADD COLUMN org_code VARCHAR(4) NULL UNIQUE AFTER name",
    "ALTER TABLE users ADD COLUMN user_code VARCHAR(12) NULL UNIQUE AFTER org_id",
];

$results = [];
foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        $results[] = ['sql' => $sql, 'status' => 'OK'];
    } catch (PDOException $e) {
        $results[] = ['sql' => $sql, 'status' => 'ERROR: ' . $e->getMessage()];
    }
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
