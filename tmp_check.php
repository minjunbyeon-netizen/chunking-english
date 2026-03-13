<?php
require 'config/db.php';
$stmt = $pdo->query("
    SELECT v.verb_en, v.order_num, COUNT(e.id) as cnt 
    FROM verbs v 
    JOIN expressions e ON e.verb_id = v.id 
    JOIN days d ON v.day_id = d.id 
    WHERE d.day_number = 1 
    GROUP BY v.id, v.verb_en, v.order_num 
    ORDER BY v.order_num
");
echo "=== Day 1 DB expressions count ===\n";
$total = 0;
foreach ($stmt as $r) {
    echo $r['verb_en'] . ': ' . $r['cnt'] . " expressions\n";
    $total += $r['cnt'];
}
echo "Total: $total\n";
