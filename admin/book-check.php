<?php
require_once '_auth.php';
$day = max(1, min(250, intval($_GET['day'] ?? 1)));
$prev = $day > 1   ? $day - 1 : null;
$next = $day < 250 ? $day + 1 : null;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>청킹E-book 확인 · 관리자</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
<style>
:root {
    --pink:    #FF7E96;
    --pink-dk: #C75A6F;
    --bg:      #F9F5F6;
    --border:  #EDE0E3;
    --text:    #1a1a2e;
    --muted:   #78716c;
    --white:   #ffffff;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Noto Sans KR', sans-serif; background: var(--bg); color: var(--text); height: 100vh; display: flex; flex-direction: column; font-size: 14px; }

header {
    background: var(--pink-dk); color: #fff;
    padding: 0 28px; display: flex; align-items: stretch;
    border-bottom: 1px solid rgba(0,0,0,.1); flex-shrink: 0;
}
.hd-title { padding: 14px 0; flex: 1; }
.hd-title h1 { font-size: 1rem; font-weight: 700; letter-spacing: -.3px; }
.hd-title .sub { font-size: .75rem; opacity: .7; margin-top: 2px; }
nav { display: flex; align-items: center; }
nav a {
    display: inline-flex; align-items: center; height: 100%; padding: 0 16px;
    font-size: .82rem; font-weight: 500; color: rgba(255,255,255,.75);
    text-decoration: none; border-bottom: 2px solid transparent; transition: color .12s, border-color .12s;
}
nav a:hover { color: #fff; }
nav a.active { color: #fff; border-bottom-color: #fff; }

.toolbar {
    background: var(--white); border-bottom: 1px solid var(--border);
    padding: 10px 20px; display: flex; align-items: center; gap: 12px; flex-shrink: 0;
}
.toolbar form { display: flex; align-items: center; gap: 8px; }
.toolbar label { font-size: .82rem; color: var(--muted); }
.toolbar input[type=number] {
    width: 72px; padding: 5px 8px; border: 1px solid var(--border);
    border-radius: 6px; font-size: .82rem; font-family: inherit;
    text-align: center;
}
.toolbar input[type=number]:focus { outline: none; border-color: var(--pink-dk); }
.btn {
    padding: 5px 14px; border-radius: 6px; font-size: .82rem; font-family: inherit;
    font-weight: 500; cursor: pointer; text-decoration: none; border: none;
    display: inline-flex; align-items: center; gap: 6px;
}
.btn-primary { background: var(--pink-dk); color: #fff; }
.btn-primary:hover { opacity: .88; }
.btn-nav { background: var(--white); color: var(--text); border: 1px solid var(--border); }
.btn-nav:hover { background: var(--bg); }
.btn-nav.disabled { opacity: .35; pointer-events: none; }
.day-label { font-size: .9rem; font-weight: 700; color: var(--pink-dk); min-width: 60px; text-align: center; }
.sep { width: 1px; height: 20px; background: var(--border); }
.open-link { font-size: .78rem; color: var(--muted); text-decoration: none; }
.open-link:hover { color: var(--pink-dk); text-decoration: underline; }

iframe {
    flex: 1; border: none; width: 100%;
}
</style>
</head>
<body>

<header>
    <div class="hd-title">
        <h1>청킹잉글리시 관리자</h1>
        <div class="sub">Chunking English Admin</div>
    </div>
    <nav>
        <a href="dashboard.php">대시보드</a>
        <a href="index.php">Day 목록</a>
        <a href="overview.php">전체 현황</a>
        <a href="organizations.php">지자체 관리</a>
        <a href="users.php">사용자 관리</a>
        <a href="generate_audio.php">오디오 생성</a>
        <a href="debug.php">디버그</a>
        <a href="book-check.php" class="active">청킹E-book</a>
    </nav>
</header>

<div class="toolbar">
    <?php if ($prev): ?>
        <a href="?day=<?= $prev ?>" class="btn btn-nav">&lsaquo; Day <?= $prev ?></a>
    <?php else: ?>
        <span class="btn btn-nav disabled">&lsaquo; 이전</span>
    <?php endif; ?>

    <span class="day-label">Day <?= $day ?></span>

    <?php if ($next): ?>
        <a href="?day=<?= $next ?>" class="btn btn-nav">Day <?= $next ?> &rsaquo;</a>
    <?php else: ?>
        <span class="btn btn-nav disabled">다음 &rsaquo;</span>
    <?php endif; ?>

    <div class="sep"></div>

    <form method="get">
        <label>Day 이동</label>
        <input type="number" name="day" value="<?= $day ?>" min="1" max="250">
        <button type="submit" class="btn btn-primary">이동</button>
    </form>

    <div class="sep"></div>

    <a href="../book.php?day=<?= $day ?>" target="_blank" class="open-link">새 탭으로 열기 &nearr;</a>
</div>

<iframe src="../book.php?day=<?= $day ?>" title="Day <?= $day ?> E-book"></iframe>

</body>
</html>
