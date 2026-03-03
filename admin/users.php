<?php
require_once '_auth.php';
require_once '../config/db.php';

$msg = $err = '';

// ── POST: 수동 인증 ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($action === 'verify' && $user_id) {
        $pdo->prepare("UPDATE users SET email_verified=1, verification_token=NULL, token_expires_at=NULL WHERE id=?")
            ->execute([$user_id]);
        $msg = '사용자 이메일이 인증 처리되었습니다.';
    }
    header('Location: users.php?org=' . urlencode($_POST['org_filter'] ?? '') . ($msg ? '&msg=' . urlencode($msg) : ''));
    exit;
}

if (isset($_GET['msg'])) $msg = htmlspecialchars($_GET['msg']);

// ── 지자체 목록 (필터용) ──────────────────────────────────────
$orgs = $pdo->query("SELECT id, name, region FROM organizations ORDER BY region, name")->fetchAll();
$orgs_grouped = [];
foreach ($orgs as $o) $orgs_grouped[$o['region']][] = $o;
$region_order = ['서울','부산','대구','인천','광주','대전','울산','세종','경기','강원','충북','충남','전북','전남','경북','경남','제주'];
uksort($orgs_grouped, fn($a,$b) => array_search($a,$region_order) <=> array_search($b,$region_order));
$filter_org = isset($_GET['org']) && $_GET['org'] !== '' ? (int)$_GET['org'] : null;

// ── 사용자 목록 ───────────────────────────────────────────────
$sql = "
    SELECT u.id, u.email, u.nickname, u.email_verified, u.created_at, u.role,
           o.name AS org_name
    FROM users u
    LEFT JOIN organizations o ON o.id = u.org_id
";
$params = [];
if ($filter_org !== null) {
    $sql    .= " WHERE u.org_id = ?";
    $params[] = $filter_org;
}
$sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>사용자 관리 · 청킹잉글리시 관리자</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
<style>
:root {
    --pink:    #FF7E96;
    --pink-lt: #FFD0D9;
    --pink-dk: #C75A6F;
    --bg:      #F9F5F6;
    --border:  #EDE0E3;
    --green:   #16a34a;
    --red:     #dc2626;
    --text:    #1a1a2e;
    --muted:   #78716c;
    --white:   #ffffff;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Noto Sans KR', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; font-size: 14px; }

header {
    background: var(--pink-dk); color: #fff;
    padding: 0 28px; display: flex; align-items: stretch;
    border-bottom: 1px solid rgba(0,0,0,.1);
}
.hd-title { padding: 14px 0; flex: 1; }
.hd-title h1 { font-size: 1rem; font-weight: 700; letter-spacing: -.3px; }
.hd-title .sub { font-size: .75rem; opacity: .7; margin-top: 2px; }
nav { display: flex; align-items: center; }
nav a {
    display: inline-flex; align-items: center; height: 100%; padding: 0 14px;
    font-size: .82rem; font-weight: 500; color: rgba(255,255,255,.75);
    text-decoration: none; border-bottom: 2px solid transparent;
    transition: color .12s, border-color .12s;
}
nav a:hover { color: #fff; }
nav a.active { color: #fff; border-bottom-color: #fff; }

.container { max-width: 1060px; margin: 0 auto; padding: 24px 20px; }

.alert { padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: .85rem; }
.alert.ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.alert.err { background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; }

.controls { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.sec-title { font-size: .88rem; font-weight: 600; color: var(--text); }

select {
    padding: 5px 10px; border: 1px solid var(--border); border-radius: 6px;
    font-family: inherit; font-size: .82rem; background: var(--white); outline: none;
    cursor: pointer;
}

.tbl-wrap { background: var(--white); border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
table { width: 100%; border-collapse: collapse; font-size: .82rem; }
th {
    padding: 8px 10px; text-align: left;
    font-size: .7rem; font-weight: 600; color: var(--muted);
    background: #fafafa; border-bottom: 1px solid var(--border);
}
td { padding: 7px 10px; border-bottom: 1px solid #f0eced; vertical-align: middle; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: #fdf8f9; }

.spill {
    display: inline-block; font-size: .72rem; font-weight: 500; padding: 2px 8px;
    border-radius: 4px; border: 1px solid transparent; white-space: nowrap;
}
.spill.ok   { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
.spill.warn { background: #fffbeb; color: #92400e; border-color: #fde68a; }

.btn {
    display: inline-flex; align-items: center;
    border: 1px solid var(--border); border-radius: 6px;
    padding: 3px 9px; font-size: .74rem; font-family: inherit;
    background: var(--white); color: var(--muted);
    cursor: pointer; transition: background .12s; white-space: nowrap;
}
.btn:hover { background: var(--pink-lt); color: var(--pink-dk); border-color: var(--pink-lt); }
</style>
</head>
<body>

<header>
    <div class="hd-title">
        <h1>청킹잉글리시 관리자</h1>
        <div class="sub">사용자 관리</div>
    </div>
    <nav>
        <a href="dashboard.php">대시보드</a>
        <a href="index.php">Day 목록</a>
        <a href="overview.php">전체 현황</a>
        <a href="organizations.php">지자체 관리</a>
        <a href="users.php" class="active">사용자 관리</a>
    </nav>
</header>

<div class="container">

    <?php if ($msg): ?><div class="alert ok"><?= $msg ?></div><?php endif; ?>

    <div class="controls">
        <div class="sec-title">사용자 목록 (<?= count($users) ?>명)</div>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <select name="org" onchange="this.form.submit()" style="max-width:240px">
                <option value="">전체 지자체</option>
                <?php foreach ($orgs_grouped as $region => $list): ?>
                    <optgroup label="── <?= $region ?> ──">
                    <?php foreach ($list as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= $filter_org === (int)$o['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($o['name']) ?>
                        </option>
                    <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>이메일</th>
                    <th>닉네임</th>
                    <th>지자체</th>
                    <th>역할</th>
                    <th>가입일</th>
                    <th style="text-align:center">인증</th>
                    <th style="text-align:center">관리</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$users): ?>
                <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">사용자가 없습니다.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td style="color:var(--muted);font-size:.72rem"><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['nickname'] ?? '-') ?></td>
                    <td style="color:var(--muted)"><?= htmlspecialchars($u['org_name'] ?? '직접 가입') ?></td>
                    <td style="font-size:.78rem"><?= htmlspecialchars($u['role']) ?></td>
                    <td style="font-size:.78rem"><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
                    <td style="text-align:center">
                        <span class="spill <?= $u['email_verified'] ? 'ok' : 'warn' ?>">
                            <?= $u['email_verified'] ? '인증' : '미인증' ?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <?php if (!$u['email_verified']): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action"     value="verify">
                            <input type="hidden" name="user_id"    value="<?= $u['id'] ?>">
                            <input type="hidden" name="org_filter" value="<?= $filter_org ?? '' ?>">
                            <button type="submit" class="btn"
                                onclick="return confirm('인증 처리하시겠습니까?')">수동 인증</button>
                        </form>
                        <?php else: ?>
                            <span style="color:var(--muted);font-size:.74rem">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
