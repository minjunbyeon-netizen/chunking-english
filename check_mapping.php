<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>이미지 매핑 확인</title>
<style>
    body { font-family: Arial, sans-serif; background: #1a1a2e; color: #eee; padding: 20px; }
    h1 { color: #ff7eb3; }
    .day-block { margin-bottom: 40px; }
    .day-title { font-size: 1.4rem; font-weight: bold; color: #ffd700; border-bottom: 2px solid #ffd700; padding-bottom: 6px; margin-bottom: 16px; }
    .verb-block { margin-bottom: 24px; background: #16213e; border-radius: 12px; padding: 16px; }
    .verb-title { font-size: 1rem; color: #00d4ff; margin-bottom: 12px; font-weight: bold; }
    .expr-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; }
    .expr-card { text-align: center; }
    .expr-card img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 8px; border: 2px solid #2ecc71; }
    .expr-card .no-img { width: 100%; aspect-ratio: 1; background: #c0392b; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; color: white; padding: 4px; border: 2px solid #e74c3c; }
    .expr-card p { font-size: 0.65rem; margin-top: 4px; color: #ccc; word-break: break-word; }
    .stats { position: fixed; top: 20px; right: 20px; background: #16213e; padding: 16px; border-radius: 12px; border: 1px solid #ffd700; font-size: 0.85rem; }
    .ok { color: #2ecc71; } .fail { color: #e74c3c; }
    select { background: #16213e; color: #ffd700; border: 1px solid #ffd700; padding: 8px 16px; border-radius: 8px; font-size: 1rem; margin-bottom: 20px; cursor: pointer; }
</style>
</head>
<body>

<h1>이미지 매핑 확인</h1>

<label>Day 선택:
<select onchange="location.href='?day='+this.value">
<?php for($i=1;$i<=50;$i++): ?>
<option value="<?=$i?>" <?= (($_GET['day']??1)==$i)?'selected':'' ?>>Day <?=$i?></option>
<?php endfor; ?>
</select>
</label>

<?php
$selected_day = intval($_GET['day'] ?? 1);
$BASE = __DIR__;
$IMG_BASE = $BASE . '/asset/img';

function is_english($t) {
    if (!$t) return false;
    return preg_match('/[a-zA-Z]/', $t) && !preg_match('/[가-힣]/', $t);
}

// 엑셀 파싱
$excel_file = $BASE . '/asset/청킹 Basic _20260303.xlsx';
$verbs_data = []; // [day => [[verb_en, exprs_en[], exprs_kr[]]]]

if (file_exists($excel_file)) {
    // Python으로 JSON 추출
    $json_file = $BASE . '/database/mapping_check.json';
    if (!file_exists($json_file)) {
        $py = <<<'PY'
import openpyxl, re, json, os, sys
BASE = sys.argv[1]
ws = openpyxl.load_workbook(BASE+'/asset/청킹 Basic _20260303.xlsx', data_only=True)['청킹 Baisc']
def is_en(t):
    if not t: return False
    t=str(t).strip()
    return bool(re.search(r'[a-zA-Z]',t)) and not bool(re.search(r'[가-힣]',t))
data={}; day=1; vi=0; gv=1; ri=22
while ri<=ws.max_row:
    if day>50: break
    a=ws.cell(ri,1).value; c=ws.cell(ri,3).value
    if a and re.match(r'^\s*Day\s+\d+',str(a)): ri+=1; continue
    if not a and not c: ri+=1; continue
    if is_en(c):
        en=[ws.cell(ri,j).value for j in range(7,14)]
        kr=[ws.cell(ri+1,j).value for j in range(7,14)]
        if day not in data: data[day]=[]
        data[day].append({'verb':str(c).strip(),'gv':gv,'en':[str(x).strip() if x else '' for x in en],'kr':[str(x).strip() if x else '' for x in kr]})
        vi+=1; gv+=1
        if vi>=3: vi=0; day+=1
        ri+=2; continue
    ri+=1
with open(BASE+'/database/mapping_check.json','w',encoding='utf-8') as f:
    json.dump(data,f,ensure_ascii=False)
PY;
        file_put_contents($BASE.'/database/_tmp_check.py', $py);
        exec("python \"{$BASE}/database/_tmp_check.py\" \"{$BASE}\"");
    }
    $all_data = json_decode(file_get_contents($json_file), true);
    $day_data = $all_data[(string)$selected_day] ?? [];
}

$total_ok = 0; $total_fail = 0;
?>

<div class="stats">
    <div>Day <?=$selected_day?></div>
    <div class="ok">✅ 매핑 성공: <span id="ok_count">-</span></div>
    <div class="fail">❌ 이미지 없음: <span id="fail_count">-</span></div>
</div>

<div class="day-block">
    <div class="day-title">Day <?=$selected_day?></div>
    <?php foreach ($day_data as $verb_info):
        $verb_en = $verb_info['verb'];
        $gv      = $verb_info['gv'];
        $exprs_en = $verb_info['en'];
        $exprs_kr = $verb_info['kr'];
    ?>
    <div class="verb-block">
        <div class="verb-title">#<?=$gv?> &nbsp; <?=htmlspecialchars($verb_en)?></div>
        <div class="expr-grid">
        <?php foreach ($exprs_en as $i => $exp_en):
            if (!$exp_en) continue;
            $fname    = str_replace(' ', '_', $exp_en) . '.png';
            $gv_pad   = str_pad($gv, 2, '0', STR_PAD_LEFT);
            $rel_path = "asset/img/day {$selected_day}/{$gv_pad}. {$verb_en}/{$fname}";
            $full_path = $BASE . '/' . $rel_path;
            $exists   = file_exists($full_path);
            if ($exists) $total_ok++; else $total_fail++;
            $web_path = '/03_chunking/' . str_replace(['asset/img/day ', '. ', ' '], ['asset/img/day%20', '.%20', '%20'], $rel_path);
            // 더 안전한 URL 인코딩
            $parts = explode('/', $rel_path);
            $encoded_parts = array_map('rawurlencode', $parts);
            $web_url = '/03_chunking/' . implode('/', $encoded_parts);
        ?>
        <div class="expr-card">
            <?php if ($exists): ?>
                <img src="<?=htmlspecialchars($web_url)?>" alt="<?=htmlspecialchars($exp_en)?>" loading="lazy">
            <?php else: ?>
                <div class="no-img">❌ 없음</div>
            <?php endif; ?>
            <p><?=htmlspecialchars($exp_en)?></p>
            <?php if (!empty($exprs_kr[$i])): ?>
            <p style="color:#aaa;"><?=htmlspecialchars($exprs_kr[$i])?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
document.getElementById('ok_count').textContent = <?=$total_ok?>;
document.getElementById('fail_count').textContent = <?=$total_fail?>;
</script>
</body>
</html>
