-- Seed program_college_link from existing college_id values on program_programs.
-- Table has composite PK (program_id, college_id) so INSERT IGNORE skips duplicates.
INSERT IGNORE INTO programs.program_college_link (program_id, college_id)
SELECT id, college_id FROM programs.program_programs WHERE college_id IS NOT NULL;

-- Seed program_inter_dept from existing department_id values on program_programs.
-- No PK on this table, so use NOT EXISTS to avoid duplicates.
INSERT INTO programs.program_inter_dept (program_id, department_id)
SELECT pp.id, pp.department_id
FROM programs.program_programs pp
WHERE pp.department_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM programs.program_inter_dept pid
    WHERE pid.program_id = pp.id AND pid.department_id = pp.department_id
  );
