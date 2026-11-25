-- Run once after 002_round_formats.sql on an existing database.
ALTER TABLE olympiads
  ADD COLUMN stage1_end DATETIME DEFAULT NULL,
  ADD COLUMN stage2_end DATETIME DEFAULT NULL,
  ADD COLUMN stage3_end DATETIME DEFAULT NULL,
  ADD COLUMN stage4_end DATETIME DEFAULT NULL;
