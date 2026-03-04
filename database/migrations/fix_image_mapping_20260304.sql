-- ============================================================
-- 이미지 매핑 오류 수정 (2026-03-04)
-- ============================================================
-- 수정 항목:
--   ① 파일명 숫자 suffix 제거 (파일 rename + DB 업데이트)
--   ② 하이픈 파일명 불일치 (DB image_path 직접 지정)
--   ③ DB 오타 수정 (expression_en + image_path)
--
-- 파일 rename (bash):
--   mv "asset/img/day 4/12. think in/think_in_Korean_37.png"
--      "asset/img/day 4/12. think in/think_in_Korean.png"
--   mv "asset/img/day 5/14. plant/plant_the_tree_50.png"
--      "asset/img/day 5/14. plant/plant_the_tree.png"
-- ============================================================

-- ① Day 4 - think in Korean (파일명 _37 suffix 제거)
UPDATE expressions SET image_path = 'asset/img/day 4/12. think in/think_in_Korean.png'
WHERE expression_en = 'think in Korean';

-- ① Day 5 - plant the tree (파일명 _50 suffix 제거)
UPDATE expressions SET image_path = 'asset/img/day 5/14. plant/plant_the_tree.png'
WHERE expression_en = 'plant the tree';

-- ② Day 14 - do (하이픈 제거된 파일명으로 image_path 직접 지정)
UPDATE expressions SET image_path = 'asset/img/day 14/42. do/do_pushups.png'
WHERE expression_en = 'do push-ups';

UPDATE expressions SET image_path = 'asset/img/day 14/42. do/do_situps.png'
WHERE expression_en = 'do sit-ups';

UPDATE expressions SET image_path = 'asset/img/day 14/42. do/do_pullups.png'
WHERE expression_en = 'do pull-ups';

UPDATE expressions SET image_path = 'asset/img/day 14/42. do/do_warmup.png'
WHERE expression_en = 'do warm-up';

-- ③ Day 46 - make a triangle (DB 오타 수정: 'makea triangle' → 'make a triangle')
UPDATE expressions SET
  expression_en = 'make a triangle',
  image_path    = 'asset/img/day 46/137. make/make_a_triangle.png'
WHERE expression_en = 'makea triangle';
