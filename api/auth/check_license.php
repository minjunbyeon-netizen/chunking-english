<?php
/**
 * GET /api/auth/check_license.php?code=XXXX
 * 지자체 인증번호 실시간 확인 (회원가입 폼에서 호출)
 * Response: { name: "서울시 강남구" } | { name: null }
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/db.php';

$code = trim($_GET['code'] ?? '');
if (!$code) { echo json_encode(['name' => null]); exit; }

$stmt = $pdo->prepare("
    SELECT o.name, o.max_users,
           COUNT(u.id) AS user_count
    FROM organizations o
    LEFT JOIN users u ON u.org_id = o.id
    WHERE o.license_code = ?
      AND o.is_active = 1
      AND (o.expires_at IS NULL OR o.expires_at >= CURDATE())
    GROUP BY o.id
");
$stmt->execute([$code]);
$org = $stmt->fetch();

if (!$org || (int)$org['user_count'] >= (int)$org['max_users']) {
    echo json_encode(['name' => null]);
    exit;
}

echo json_encode(['name' => $org['name']]);
