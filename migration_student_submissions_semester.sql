-- Add semester to project submissions (run once in phpMyAdmin on `ca_project`)
-- If column already exists, skip this.

ALTER TABLE student_submissions
  ADD COLUMN semester VARCHAR(10) NULL COMMENT 'I or II' AFTER section;
