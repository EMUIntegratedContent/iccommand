-- Legacy tables with no Doctrine entity, for the CI test database.
--
-- CI builds a throwaway database with `php bin/console doctrine:schema:create`, which only
-- creates tables that have an entity in src/Entity. The tables below are queried with raw SQL
-- (mostly ProgramsRepository, ProgramsService and IcAdvisorRepository) but have no entity, so
-- CI loads this file AFTER doctrine:schema:create. Their FK parents (program_programs,
-- ic_colleges, ic_departments, ic_advisors) all come from schema:create; their ids are
-- INT UNSIGNED, so the FK columns here are INT UNSIGNED too.
--
-- This is NOT a dump of production. It was reconstructed from the raw SQL in src/, the
-- hand-written migrations in src/Migrations (final state after all of them), docs/*.md and
-- code comments. Where a key or FK couldn't be determined it is left out rather than guessed.
-- No rows are inserted.
--
-- To regenerate it from a real database (then restore the header and IF NOT EXISTS):
--   mysqldump --no-data --skip-add-drop-table <db> \
--     program_degrees program_types program_delivery \
--     program_college_link program_inter_dept program_advisor
--
-- program_colleges / program_departments are not here: they were renamed/retired to
-- ic_colleges / ic_departments (Version20261008000000, Version20261008010000) and live code
-- no longer queries them.

-- Degree lookup (programs.degree_id). Columns from ProgramsRepository::getDegrees() /
-- searchDegreePrograms() (de.degree, de.degree_short) and CasService::getGraduatePrograms().
CREATE TABLE IF NOT EXISTS program_degrees (
    id INT AUTO_INCREMENT NOT NULL,
    degree VARCHAR(255) NOT NULL,
    degree_short VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Program type lookup (programs.type_id). Columns from ProgramsRepository::getProgTypes()
-- (ORDER BY type) and ProgramForm.vue (selectedProgType.type); same shape as the Types entity.
CREATE TABLE IF NOT EXISTS program_types (
    id INT AUTO_INCREMENT NOT NULL,
    type VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Program -> delivery modes. Columns from ProgramsRepository::updateProgramDeliveryModes() /
-- getProgram() and ProgramsService::searchDegreePrograms(). delivery_id is a hard-coded code
-- (0 In-Person/Hybrid, 1 Online, 2 Hyflex in ProgramForm.vue), not an FK to a lookup table.
-- No unique key: the writer doesn't de-duplicate ids before inserting.
CREATE TABLE IF NOT EXISTS program_delivery (
    program_id INT UNSIGNED NOT NULL,
    delivery_id INT NOT NULL,
    INDEX IDX_program_delivery_program (program_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Program -> colleges. Composite PK from the old sql/seed_pivot_tables.sql and the comment in
-- ProgramsService::updateProgramColleges(); FK to ic_colleges from that comment and
-- Version20261008000000 (no ON DELETE action is stated, so the default RESTRICT is used).
CREATE TABLE IF NOT EXISTS program_college_link (
    program_id INT UNSIGNED NOT NULL,
    college_id INT UNSIGNED NOT NULL,
    INDEX IDX_program_college_link_college (college_id),
    PRIMARY KEY (program_id, college_id),
    CONSTRAINT fk_program_college_link_college FOREIGN KEY (college_id) REFERENCES ic_colleges (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Program -> departments. Exactly as rebuilt by Version20261008010000::rebuildInterDept().
CREATE TABLE IF NOT EXISTS program_inter_dept (
    program_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    INDEX IDX_program_inter_dept_department (department_id),
    PRIMARY KEY (program_id, department_id),
    CONSTRAINT fk_program_inter_dept_program FOREIGN KEY (program_id) REFERENCES program_programs (id) ON DELETE CASCADE,
    CONSTRAINT fk_program_inter_dept_department FOREIGN KEY (department_id) REFERENCES ic_departments (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Program -> advisors. Created as program_contact by Version20261102010000, renamed with these
-- index/FK names by Version20261115000000; see docs/ic-advisors.md.
CREATE TABLE IF NOT EXISTS program_advisor (
    program_id INT UNSIGNED NOT NULL,
    advisor_id INT UNSIGNED NOT NULL,
    INDEX IDX_9247312066D3AD77 (advisor_id),
    PRIMARY KEY (program_id, advisor_id),
    CONSTRAINT FK_924731203EB8070A FOREIGN KEY (program_id) REFERENCES program_programs (id) ON DELETE CASCADE,
    CONSTRAINT FK_9247312066D3AD77 FOREIGN KEY (advisor_id) REFERENCES ic_advisors (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
