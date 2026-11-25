-- Run once after 001_rounds.sql on an existing database.
ALTER TABLE olympiads
  ADD COLUMN stage2_format VARCHAR(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  ADD COLUMN stage3_format VARCHAR(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  ADD COLUMN stage4_format VARCHAR(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';

UPDATE olympiads
SET stage1_format = CASE WHEN stages_count = 1 AND stage1_format = '' THEN final_format ELSE stage1_format END,
    stage2_format = CASE WHEN stages_count = 2 THEN final_format ELSE '' END,
    stage3_format = CASE WHEN stages_count = 3 THEN final_format ELSE '' END,
    stage4_format = CASE WHEN stages_count = 4 THEN final_format ELSE '' END;
