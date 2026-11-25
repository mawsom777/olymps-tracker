-- Run once on a database initialized with the previous olympiads schema.
ALTER TABLE olympiads
  ADD COLUMN stage2_date DATETIME DEFAULT NULL,
  ADD COLUMN stage3_date DATETIME DEFAULT NULL,
  ADD COLUMN stage4_date DATETIME DEFAULT NULL,
  ADD COLUMN stage_attendance TINYINT UNSIGNED NOT NULL DEFAULT 0;

UPDATE olympiads
SET stage1_date = CASE WHEN stages_count = 1 THEN COALESCE(stage1_date, final_date) ELSE stage1_date END,
    stage2_date = CASE WHEN stages_count = 2 THEN final_date END,
    stage3_date = CASE WHEN stages_count = 3 THEN final_date END,
    stage4_date = CASE WHEN stages_count = 4 THEN final_date END;
