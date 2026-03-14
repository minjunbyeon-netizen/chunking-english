<?php
require_once '_auth.php';
require_once '../config/db.php';

// ── 요약 카드 ──────────────────────────────────────────────────
$total_users  = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$today_active = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM page_views WHERE DATE(viewed_at) = CURDATE() AND user_id IS NOT NULL")->fetchColumn();
$active_orgs  = (int)$pdo->query("SELECT COUNT(*) FROM organizations WHERE is_active = 1")->fetchColumn();
$month_new    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())")->fetchColumn();

// ── 지자체별 사용자 현황 (가입자 있거나 최근 등록 순 상위 15개) ──
$org_stats = $pdo->query("
    SELECT o.id, o.name, o.region, o.license_code, o.max_users, o.expires_at, o.is_active,
           COUNT(u.id) AS user_count
    FROM organizations o
    LEFT JOIN users u ON u.org_id = o.id
    GROUP BY o.id
    ORDER BY user_count DESC, o.created_at DESC
    LIMIT 15
")->fetchAll();

// ── 인기 Day TOP 10 ───────────────────────────────────────────
$top_days = $pdo->query("
    SELECT day_number, COUNT(*) AS view_count
    FROM page_views
    GROUP BY day_number
    ORDER BY view_count DESC
    LIMIT 10
")->fetchAll();
$max_views = $top_days ? max(array_column($top_days, 'view_count')) : 1;

// ── 최근 가입자 10명 ──────────────────────────────────────────
$recent_users = $pdo->query("
    SELECT u.email, u.nickname, u.email_verified, u.created_at, o.name AS org_name
    FROM users u
    LEFT JOIN organizations o ON o.id = u.org_id
    ORDER BY u.created_at DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>대시보드 · 청킹잉글리시 관리자</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
    --yellow:  #d97706;
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

.container { max-width: 1100px; margin: 0 auto; padding: 24px 20px; }

/* ── 요약 카드 ── */
.summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
.sc {
    background: var(--white); border: 1px solid var(--border); border-radius: 8px;
    padding: 16px 18px;
}
.sc .n { font-size: 1.8rem; font-weight: 700; color: var(--pink-dk); line-height: 1; margin-bottom: 5px; }
.sc .l { font-size: .75rem; color: var(--muted); }

/* ── 섹션 타이틀 ── */
.sec-title {
    font-size: .88rem; font-weight: 600; color: var(--text);
    margin: 24px 0 10px; padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
}

/* ── 2열 그리드 ── */
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
@media (max-width: 760px) { .two-col { grid-template-columns: 1fr; } .summary-grid { grid-template-columns: repeat(2,1fr); } }

/* ── 테이블 ── */
.tbl-wrap { background: var(--white); border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
table { width: 100%; border-collapse: collapse; font-size: .82rem; }
th {
    padding: 8px 10px; text-align: left;
    font-size: .7rem; font-weight: 600; color: var(--muted);
    background: #fafafa; border-bottom: 1px solid var(--border);
    letter-spacing: .3px;
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
.spill.warn { background: #fffbeb; color: #92400e; border-color: #fde68a; }

/* ── Bar 차트 ── */
.bar-row { display: flex; align-items: center; gap: 10px; padding: 6px 0; }
.bar-label { width: 70px; font-size: .78rem; color: var(--text); flex-shrink: 0; text-align: right; }
.bar-track { flex: 1; height: 16px; background: #f0eced; border-radius: 4px; overflow: hidden; }
.bar-fill  { height: 100%; background: var(--pink-dk); border-radius: 4px; transition: width .3s; }
.bar-count { width: 46px; font-size: .76rem; color: var(--muted); text-align: right; flex-shrink: 0; }
</style>
</head>
<body>

<header>
    <div class="hd-title">
        <h1>청킹잉글리시 관리자</h1>
        <div class="sub">대시보드</div>
    </div>
    <nav>
        <a href="dashboard.php" class="active">대시보드</a>
        <a href="index.php">Day 목록</a>
        <a href="overview.php">전체 현황</a>
        <a href="organizations.php">지자체 관리</a>
        <a href="users.php">사용자 관리</a>
        <a href="generate_audio.php">오디오 생성</a>
        <a href="debug.php">디버그</a>
        <a href="book-check.php">청킹E-book</a>
    </nav>
</header>

<div class="container">

    <!-- 요약 카드 -->
    <div class="summary-grid">
        <div class="sc">
            <div class="n"><?= $total_users ?></div>
            <div class="l">총 가입자</div>
        </div>
        <div class="sc">
            <div class="n"><?= $today_active ?></div>
            <div class="l">오늘 접속 (조회)</div>
        </div>
        <div class="sc">
            <div class="n"><?= $active_orgs ?></div>
            <div class="l">활성 지자체</div>
        </div>
        <div class="sc">
            <div class="n"><?= $month_new ?></div>
            <div class="l">이번달 신규 가입</div>
        </div>
    </div>

    <!-- 2열: 지자체 현황 + 인기 Day -->
    <div class="two-col">

        <!-- 지자체별 사용자 현황 -->
        <div>
            <div class="sec-title" style="display:flex;justify-content:space-between;align-items:center">
                <span>지자체별 사용자 현황</span>
                <a href="../admin/organizations.php" style="font-size:.74rem;font-weight:400;color:var(--pink-dk);text-decoration:none">전체 관리 →</a>
            </div>
            <div class="tbl-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>지자체명</th>
                            <th>허가코드</th>
                            <th style="text-align:center">인원</th>
                            <th>만료일</th>
                            <th style="text-align:center">상태</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$org_stats): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px">등록된 지자체 없음</td></tr>
                    <?php endif; ?>
                    <?php foreach ($org_stats as $o): ?>
                        <tr>
                            <td><?= htmlspecialchars($o['name']) ?><?php if ($o['region']): ?><span style="color:var(--muted);font-size:.72rem"> · <?= htmlspecialchars($o['region']) ?></span><?php endif; ?></td>
                            <td style="font-family:monospace;font-size:.78rem"><?= htmlspecialchars($o['license_code']) ?></td>
                            <td style="text-align:center">
                                <?= $o['user_count'] ?>/<?= $o['max_users'] ?>
                            </td>
                            <td style="font-size:.78rem">
                                <?php if ($o['expires_at']): ?>
                                    <?php $exp = strtotime($o['expires_at']); ?>
                                    <span style="color:<?= $exp < time() ? 'var(--red)' : 'var(--text)' ?>">
                                        <?= date('Y-m-d', $exp) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--muted)">무기한</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center">
                                <span class="spill <?= $o['is_active'] ? 'ok' : 'err' ?>">
                                    <?= $o['is_active'] ? '활성' : '비활성' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 인기 Day TOP 10 -->
        <div>
            <div class="sec-title">인기 Day TOP 10 (조회수)</div>
            <div class="tbl-wrap" style="padding: 12px 16px;">
                <?php if (!$top_days): ?>
                    <p style="color:var(--muted);font-size:.82rem;padding:12px 0">조회 데이터 없음</p>
                <?php endif; ?>
                <?php foreach ($top_days as $row): ?>
                <div class="bar-row">
                    <div class="bar-label">Day <?= $row['day_number'] ?></div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:<?= round($row['view_count'] / max(1, $max_views) * 100) ?>%"></div>
                    </div>
                    <div class="bar-count"><?= number_format($row['view_count']) ?>회</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- 최근 가입자 10명 -->
    <div class="sec-title">최근 가입자 10명</div>
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>이메일</th>
                    <th>닉네임</th>
                    <th>지자체</th>
                    <th>가입일</th>
                    <th style="text-align:center">인증</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recent_users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['nickname'] ?? '-') ?></td>
                    <td style="color:var(--muted)"><?= htmlspecialchars($u['org_name'] ?? '직접 가입') ?></td>
                    <td style="font-size:.78rem"><?= date('Y-m-d H:i', strtotime($u['created_at'])) ?></td>
                    <td style="text-align:center">
                        <span class="spill <?= $u['email_verified'] ? 'ok' : 'warn' ?>">
                            <?= $u['email_verified'] ? '인증' : '미인증' ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
<a href="../index.php" title="학습 메인으로" style="position:fixed;bottom:20px;left:20px;z-index:9999;display:flex;align-items:center;gap:8px;background:#C75A6F;color:#fff;border-radius:980px;padding:8px 16px 8px 8px;text-decoration:none;font-size:.8rem;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.18);transition:opacity .2s;" onmouseover="this.style.opacity=.85" onmouseout="this.style.opacity=1"><img src="../img/ck_train.png" style="width:28px;height:28px;object-fit:contain;border-radius:50%;background:#fff;padding:2px;">학습 홈</a>
</body>
</html>
