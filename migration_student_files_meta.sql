-- Run this in phpMyAdmin on database `ca_project` if these columns are missing:
-- (Needed for branch / year / section / semester on student file uploads)

ALTER TABLE student_files
  ADD COLUMN branch VARCHAR(10) NULL COMMENT 'BCA or MCA' AFTER reg_no,
  ADD COLUMN academic_year VARCHAR(30) NULL COMMENT 'e.g. 1st Year' AFTER branch,
  ADD COLUMN section VARCHAR(5) NULL COMMENT 'A/B for MCA; NULL for BCA' AFTER academic_year,
  ADD COLUMN semester VARCHAR(10) NULL COMMENT 'I or II' AFTER section;
