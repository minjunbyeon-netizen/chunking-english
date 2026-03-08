<?php
require_once '_auth.php';
require_once '../config/db.php';

$msg = $err = '';

// ── POST 처리 ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name         = trim($_POST['name']         ?? '');
        $region       = trim($_POST['region']       ?? '');
        $org_code     = trim($_POST['org_code']     ?? '');
        $license_code = trim($_POST['license_code'] ?? '');
        $max_users    = (int)($_POST['max_users']   ?? 100);
        $expires_at   = trim($_POST['expires_at']   ?? '') ?: null;
        $note         = trim($_POST['note']         ?? '');

        if (!$name)         $err = '지자체명을 입력해주세요.';
        if (!$license_code) $err = '허가코드를 입력해주세요.';
        if ($org_code && (!ctype_digit($org_code) || strlen($org_code) !== 4))
            $err = '자치단체 고유번호는 숫자 4자리여야 합니다.';

        if (!$err) {
            if ($action === 'add') {
                try {
                    $pdo->prepare("
                        INSERT INTO organizations (name, org_code, region, license_code, max_users, expires_at, note)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ")->execute([$name, $org_code ?: null, $region ?: null, $license_code, $max_users, $expires_at, $note ?: null]);
                    $msg = '지자체가 추가되었습니다.';
                } catch (PDOException $e) {
                    $err = '허가코드 또는 고유번호가 이미 사용 중입니다.';
                }
            } else {
                $id = (int)($_POST['id'] ?? 0);
                try {
                    $pdo->prepare("
                        UPDATE organizations SET name=?, org_code=?, region=?, license_code=?, max_users=?, expires_at=?, note=?
                        WHERE id=?
                    ")->execute([$name, $org_code ?: null, $region ?: null, $license_code, $max_users, $expires_at, $note ?: null, $id]);
                    $msg = '수정되었습니다.';
                } catch (PDOException $e) {
                    $err = '허가코드 또는 고유번호가 이미 사용 중입니다.';
                }
            }
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE organizations SET is_active = 1 - is_active WHERE id = ?")
            ->execute([$id]);
        $msg = '상태가 변경되었습니다.';
    }

    if (!$err) {
        header('Location: organizations.php' . ($msg ? '?msg=' . urlencode($msg) : ''));
        exit;
    }
}

if (isset($_GET['msg'])) $msg = htmlspecialchars($_GET['msg']);

// 수정 모드
$edit_org = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM organizations WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_org = $stmt->fetch();
}

// 목록 조회 (지역별 정렬)
$orgs = $pdo->query("
    SELECT o.*, COUNT(u.id) AS user_count
    FROM organizations o
    LEFT JOIN users u ON u.org_id = o.id
    GROUP BY o.id
    ORDER BY o.region, o.name
")->fetchAll();

// 지역별 그룹핑
$grouped = [];
foreach ($orgs as $o) {
    $grouped[$o['region']][] = $o;
}

// 지역 순서 정의
$region_order = ['서울','부산','대구','인천','광주','대전','울산','세종','경기','강원','충북','충남','전북','전남','경북','경남','제주'];
uksort($grouped, fn($a,$b) => array_search($a,$region_order) <=> array_search($b,$region_order));

// 8자리 랜덤 코드 생성
function gen_code(): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code  = '';
    for ($i = 0; $i < 8; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
    return $code;
}
$new_code = gen_code();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>지자체 관리 · 청킹잉글리시 관리자</title>
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

/* ── 테이블 ── */
.tbl-wrap { background: var(--white); border: 1px solid var(--border); border-radius: 8px; overflow: hidden; margin-bottom: 24px; }
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
.spill.err  { background: #fff1f2; color: #9f1239; border-color: #fecdd3; }

/* ── 버튼 ── */
.btn {
    display: inline-flex; align-items: center;
    border: 1px solid var(--border); border-radius: 6px;
    padding: 4px 10px; font-size: .76rem; font-family: inherit;
    background: var(--white); color: var(--muted);
    cursor: pointer; transition: background .12s, color .12s;
    text-decoration: none;
}
.btn:hover { background: var(--pink-lt); color: var(--pink-dk); border-color: var(--pink-lt); }
.btn.primary { background: var(--pink-dk); color: #fff; border-color: var(--pink-dk); }
.btn.primary:hover { background: #a84860; border-color: #a84860; }

/* ── 폼 ── */
.form-box {
    background: var(--white); border: 1px solid var(--border); border-radius: 8px;
    padding: 20px 24px;
}
.form-box h3 { font-size: .9rem; font-weight: 600; margin-bottom: 16px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
@media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }
.field label { display: block; font-size: .74rem; color: var(--muted); margin-bottom: 4px; }
.field input, .field textarea, .field select {
    width: 100%; padding: 7px 10px; border: 1px solid var(--border); border-radius: 6px;
    font-family: inherit; font-size: .84rem; background: #fafafa; outline: none;
    transition: border-color .12s;
}
.field input:focus, .field textarea:focus { border-color: var(--pink); background: #fff; }
.field textarea { resize: vertical; min-height: 60px; }
.code-wrap { display: flex; gap: 6px; align-items: center; }
.code-wrap input { flex: 1; }
.form-actions { margin-top: 16px; display: flex; gap: 8px; }

.sec-title { font-size: .88rem; font-weight: 600; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid var(--border); }

/* ── 지역 그룹 ── */
.region-group { margin-bottom: 6px; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
.region-header {
    background: var(--pink-dk); color: #fff;
    padding: 8px 14px; cursor: pointer; user-select: none;
    display: flex; align-items: center; gap: 8px;
    font-size: .82rem; font-weight: 600;
    transition: background .12s;
}
.region-header:hover { background: #a84860; }
.region-chevron { font-size: .6rem; transition: transform .2s; display: inline-block; }
.region-body table { border-radius: 0; }
.region-body th, .region-body td { padding: 6px 10px; }
</style>
</head>
<body>

<header>
    <div class="hd-title">
        <h1>청킹잉글리시 관리자</h1>
        <div class="sub">지자체 관리</div>
    </div>
    <nav>
        <a href="dashboard.php">대시보드</a>
        <a href="index.php">Day 목록</a>
        <a href="overview.php">전체 현황</a>
        <a href="organizations.php" class="active">지자체 관리</a>
        <a href="users.php">사용자 관리</a>
        <a href="generate_audio.php">오디오 생성</a>
    </nav>
</header>

<div class="container">

    <?php if ($msg): ?><div class="alert ok"><?= $msg ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <!-- 검색 + 필터 -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px">
        <div class="sec-title" style="margin:0;border:none;padding:0">지자체 목록 (<?= count($orgs) ?>개)</div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <input type="text" id="search-input" placeholder="지자체명 / 허가코드 검색..."
                   oninput="filterTable()"
                   style="padding:5px 10px;border:1px solid var(--border);border-radius:6px;font-family:inherit;font-size:.82rem;background:#fff;outline:none;width:220px">
            <select id="region-filter" onchange="filterTable()"
                    style="padding:5px 10px;border:1px solid var(--border);border-radius:6px;font-family:inherit;font-size:.82rem;background:#fff;outline:none">
                <option value="">전체 지역</option>
                <?php foreach (array_keys($grouped) as $r): ?>
                    <option value="<?= $r ?>"><?= $r ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- 지역별 그룹 테이블 -->
    <?php foreach ($grouped as $region => $list): ?>
    <?php $active = array_sum(array_column($list,'is_active')); ?>
    <div class="region-group" data-region="<?= $region ?>">
        <div class="region-header" onclick="toggleRegion('<?= $region ?>')">
            <span class="region-chevron" id="chev-<?= $region ?>">▶</span>
            <strong><?= $region ?></strong>
            <span style="font-size:.76rem;color:rgba(255,255,255,.75);margin-left:8px"><?= count($list) ?>개 · 활성 <?= $active ?>개</span>
        </div>
        <div class="region-body" id="body-<?= $region ?>" style="display:none">
        <table>
            <thead>
                <tr>
                    <th>지자체명</th>
                    <th style="text-align:center">고유번호</th>
                    <th>허가코드</th>
                    <th style="text-align:center">인원</th>
                    <th>만료일</th>
                    <th style="text-align:center">상태</th>
                    <th style="text-align:center">관리</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($list as $o): ?>
                <tr class="org-row" data-name="<?= htmlspecialchars(mb_strtolower($o['name'])) ?>" data-code="<?= htmlspecialchars(mb_strtolower($o['license_code'])) ?>">
                    <td><strong><?= htmlspecialchars($o['name']) ?></strong></td>
                    <td style="text-align:center;font-family:monospace;font-size:.85rem;font-weight:700;letter-spacing:1px;color:var(--pink-dk)">
                        <?= $o['org_code'] ? htmlspecialchars($o['org_code']) : '<span style="color:var(--muted);font-weight:400">미설정</span>' ?>
                    </td>
                    <td style="font-family:monospace;font-size:.8rem;letter-spacing:.5px"><?= htmlspecialchars($o['license_code']) ?></td>
                    <td style="text-align:center">
                        <?php $cls = $o['user_count'] >= $o['max_users'] ? 'err' : 'ok'; ?>
                        <span class="spill <?= $cls ?>"><?= $o['user_count'] ?>/<?= $o['max_users'] ?></span>
                    </td>
                    <td style="font-size:.78rem">
                        <?php if ($o['expires_at']): ?>
                            <?php $exp = strtotime($o['expires_at']); ?>
                            <span style="color:<?= $exp < time() ? 'var(--red)' : 'var(--text)' ?>">
                                <?= date('Y-m-d', $exp) ?>
                            </span>
                        <?php else: ?><span style="color:var(--muted)">무기한</span><?php endif; ?>
                    </td>
                    <td style="text-align:center">
                        <span class="spill <?= $o['is_active'] ? 'ok' : 'err' ?>">
                            <?= $o['is_active'] ? '활성' : '비활성' ?>
                        </span>
                    </td>
                    <td style="text-align:center;white-space:nowrap">
                        <a href="?edit=<?= $o['id'] ?>#form" class="btn">수정</a>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id"     value="<?= $o['id'] ?>">
                            <button type="submit" class="btn"
                                onclick="return confirm('<?= $o['is_active'] ? '비활성화' : '활성화' ?>하시겠습니까?')">
                                <?= $o['is_active'] ? '비활성화' : '활성화' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endforeach; ?>
    <div style="margin-bottom:24px"></div>

    <!-- 추가 / 수정 폼 -->
    <a name="form"></a>
    <div class="form-box">
        <h3><?= $edit_org ? '지자체 수정' : '지자체 추가' ?></h3>
        <form method="POST">
            <input type="hidden" name="action" value="<?= $edit_org ? 'edit' : 'add' ?>">
            <?php if ($edit_org): ?>
                <input type="hidden" name="id" value="<?= $edit_org['id'] ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="field">
                    <label>지자체명 *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($edit_org['name'] ?? '') ?>" placeholder="예: 서울시 강남구" required>
                </div>
                <div class="field">
                    <label>고유번호 (숫자 4자리)</label>
                    <input type="text" name="org_code" value="<?= htmlspecialchars($edit_org['org_code'] ?? '') ?>"
                           placeholder="예: 1234" maxlength="4" pattern="\d{4}"
                           style="font-family:monospace;letter-spacing:2px;font-size:1rem;font-weight:700">
                </div>
                <div class="field">
                    <label>지역 (선택)</label>
                    <select name="region">
                        <option value="">-- 선택 --</option>
                        <?php foreach ($region_order as $r): ?>
                            <option value="<?= $r ?>" <?= ($edit_org['region'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>허가코드 *</label>
                    <div class="code-wrap">
                        <input type="text" name="license_code" id="license_code"
                               value="<?= htmlspecialchars($edit_org['license_code'] ?? $new_code) ?>"
                               maxlength="30" required style="font-family:monospace;letter-spacing:.5px">
                        <?php if (!$edit_org): ?>
                        <button type="button" class="btn" onclick="genCode()">재생성</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="field">
                    <label>최대 인원</label>
                    <input type="number" name="max_users" value="<?= htmlspecialchars($edit_org['max_users'] ?? 100) ?>" min="1" max="9999">
                </div>
                <div class="field">
                    <label>만료일 (선택)</label>
                    <input type="date" name="expires_at" value="<?= htmlspecialchars($edit_org['expires_at'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>메모 (선택)</label>
                    <input type="text" name="note" value="<?= htmlspecialchars($edit_org['note'] ?? '') ?>" placeholder="내부 메모">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn primary"><?= $edit_org ? '수정 저장' : '지자체 추가' ?></button>
                <?php if ($edit_org): ?>
                    <a href="organizations.php" class="btn">취소</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

</div>

<script>
const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
function genCode() {
    let code = '';
    for (let i = 0; i < 8; i++) code += CHARS[Math.floor(Math.random() * CHARS.length)];
    document.getElementById('license_code').value = code;
}

// ── 지역 아코디언 ──
function toggleRegion(region) {
    const body  = document.getElementById('body-' + region);
    const chev  = document.getElementById('chev-' + region);
    const open  = body.style.display === 'block';
    body.style.display = open ? 'none' : 'block';
    chev.style.transform = open ? '' : 'rotate(90deg)';
}

// ── 검색 필터 ──
function filterTable() {
    const q      = document.getElementById('search-input').value.toLowerCase();
    const region = document.getElementById('region-filter').value;

    document.querySelectorAll('.region-group').forEach(group => {
        const gr = group.dataset.region;
        if (region && gr !== region) { group.style.display = 'none'; return; }
        group.style.display = '';

        let visCount = 0;
        group.querySelectorAll('.org-row').forEach(row => {
            const match = !q || row.dataset.name.includes(q) || row.dataset.code.includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visCount++;
        });

        // 검색 중이면 해당 그룹 자동 펼침
        const body = group.querySelector('.region-body');
        const chev = group.querySelector('.region-chevron');
        if (q && visCount > 0) {
            body.style.display = 'block';
            chev.style.transform = 'rotate(90deg)';
        }
        if (visCount === 0 && q) group.style.display = 'none';
    });
}
</script>
</body>
</html>
