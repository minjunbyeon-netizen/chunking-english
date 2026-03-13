<?php
require 'config/db.php';

$stmt = $pdo->query("
    SELECT d.day_number, v.verb_en, v.verb_kr, v.order_num,
           e.expression_en, e.expression_kr, e.order_num as expr_order
    FROM days d
    JOIN verbs v ON v.day_id = d.id
    JOIN expressions e ON e.verb_id = v.id
    WHERE d.is_active = 1
    ORDER BY d.day_number, v.order_num, e.order_num
");
$rows = $stmt->fetchAll();

$db_index = [];
foreach ($rows as $r) {
    $dn = $r['day_number'];
    $slug = str_replace(' ', '_', trim($r['expression_en']));
    $db_index[$dn][$slug] = $r['expression_kr'] ?? '';
}

$base = __DIR__ . '/asset/book-img';
$ranges = [
    [1, 50, 'day 1 ~ 50'], [51, 100, 'day 51 ~ 100'],
    [101, 150, 'day 101 ~ 150'], [151, 200, 'day 151 ~ 200'],
    [201, 220, 'day 201 ~ 220'], [221, 250, 'day 221 ~ 250'],
];

// UTF-8 BOM + CSV 출력
$out = __DIR__ . '/mapping_audit.csv';
$fp = fopen($out, 'w');
fwrite($fp, "\xEF\xBB\xBF"); // UTF-8 BOM
fputcsv($fp, ['Day', 'Verb', 'Expression (EN)', 'Expression (KR)', 'Status']);

$summary = ['ok' => 0, 'no_kr' => 0];

for ($day = 1; $day <= 250; $day++) {
    $range_folder = null;
    foreach ($ranges as $r) {
        if ($day >= $r[0] && $day <= $r[1]) { $range_folder = $r[2]; break; }
    }
    if (!$range_folder) continue;
    
    $day_path = $base . '/' . $range_folder . '/day ' . $day;
    if (!is_dir($day_path)) {
        fputcsv($fp, ['Day ' . $day, '', '', '', '이미지 폴더 없음']);
        continue;
    }
    
    $verb_dirs = [];
    foreach (scandir($day_path) as $item) {
        if ($item === '.' || $item === '..') continue;
        if (is_dir($day_path . '/' . $item)) $verb_dirs[] = $item;
    }
    sort($verb_dirs);
    
    foreach ($verb_dirs as $vdir) {
        $verb_en = preg_replace('/^\d+\.\s*/', '', $vdir);
        $vpath = $day_path . '/' . $vdir;
        
        $files = [];
        foreach (scandir($vpath) as $f) {
            if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) !== 'png') continue;
            if ($f === 'desktop.ini') continue;
            $files[] = $f;
        }
        sort($files);
        
        foreach ($files as $f) {
            $slug = pathinfo($f, PATHINFO_FILENAME);
            $expr_en = str_replace('_', ' ', $slug);
            
            if (isset($db_index[$day][$slug])) {
                $kr = $db_index[$day][$slug];
                fputcsv($fp, ['Day ' . $day, $verb_en, $expr_en, $kr, '정상']);
                $summary['ok']++;
            } else {
                fputcsv($fp, ['Day ' . $day, $verb_en, $expr_en, '', '한글 번역 없음']);
                $summary['no_kr']++;
            }
        }
    }
}

fclose($fp);
echo "CSV 생성 완료: $out\n";
echo "정상: {$summary['ok']}, 한글 없음: {$summary['no_kr']}\n";
