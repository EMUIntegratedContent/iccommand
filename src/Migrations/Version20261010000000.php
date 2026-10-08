<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drops program_programs.department_id and program_programs.college_id. A program's
 * departments and colleges live in program_inter_dept and program_college_link (which allow
 * several of each); the single-value columns only ever held the first selected one.
 *
 * Before dropping, each column's value is copied into its link table (INSERT IGNORE, so values
 * already linked are skipped). This replaces running sql/seed_pivot_tables.sql by hand on a
 * database whose link tables were never seeded. A college_id of 0 means "none" and is skipped;
 * any other value that isn't an ic_colleges id stops the migration before anything changes.
 *
 * down() re-adds both columns as nullable and backfills them with the lowest linked id, which
 * is not necessarily the value that was dropped.
 */
final class Version20261010000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop program_programs.department_id and college_id in favor of the link tables';
    }

    public function up(Schema $schema): void
    {
        $hasDepartment = $this->columnExists('department_id');
        $hasCollege = $this->columnExists('college_id');
        if (!$hasDepartment && !$hasCollege) {
            $this->warnIf(true, 'program_programs.department_id and college_id already dropped, skipping');
            return;
        }

        // department_id has an FK to ic_departments, so every value can be linked; college_id
        // has none, so check it can be. Under --dry-run the colleges table may not be renamed
        // to ic_colleges yet (same ids either way).
        if ($hasCollege) {
            $collegeTable = $this->tableExists('ic_colleges') ? 'ic_colleges' : 'program_colleges';
            $unknown = $this->connection->fetchFirstColumn(
                "SELECT p.id FROM program_programs p
                 WHERE p.college_id IS NOT NULL AND p.college_id <> 0
                   AND NOT EXISTS (SELECT 1 FROM $collegeTable c WHERE c.id = p.college_id)"
            );
            $this->abortIf($unknown !== [], 'college_id is not an ic_colleges id for programs: ' . implode(', ', $unknown));
        }

        if ($hasDepartment) {
            $this->addSql('INSERT IGNORE INTO program_inter_dept (program_id, department_id)
                SELECT p.id, p.department_id
                FROM program_programs p
                JOIN ic_departments d ON d.id = p.department_id');
        }
        if ($hasCollege) {
            $this->addSql('INSERT IGNORE INTO program_college_link (program_id, college_id)
                SELECT p.id, p.college_id
                FROM program_programs p
                JOIN ic_colleges c ON c.id = p.college_id');
        }

        if ($this->foreignKeyExists('fk_program_departments')) {
            $this->addSql('ALTER TABLE program_programs DROP FOREIGN KEY fk_program_departments');
        }
        if ($hasDepartment) {
            $this->addSql('ALTER TABLE program_programs DROP COLUMN department_id');
        }
        if ($hasCollege) {
            $this->addSql('ALTER TABLE program_programs DROP COLUMN college_id');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$this->columnExists('college_id')) {
            $this->addSql('ALTER TABLE program_programs ADD college_id INT UNSIGNED DEFAULT NULL AFTER `catalog`');
            $this->addSql('UPDATE program_programs p
                SET p.college_id = (SELECT MIN(l.college_id) FROM program_college_link l WHERE l.program_id = p.id)');
        }
        if (!$this->columnExists('department_id')) {
            $this->addSql('ALTER TABLE program_programs ADD department_id INT UNSIGNED DEFAULT NULL AFTER college_id');
            $this->addSql('UPDATE program_programs p
                SET p.department_id = (SELECT MIN(x.department_id) FROM program_inter_dept x WHERE x.program_id = p.id)');
            $this->addSql('ALTER TABLE program_programs ADD CONSTRAINT fk_program_departments FOREIGN KEY (department_id) REFERENCES ic_departments (id)');
        }
    }

    private function columnExists(string $column): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'program_programs'
               AND COLUMN_NAME = ?",
            [$column]
        );
    }

    private function tableExists(string $table): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?",
            [$table]
        );
    }

    private function foreignKeyExists(string $constraint): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = 'program_programs'
               AND CONSTRAINT_NAME = ?",
            [$constraint]
        );
    }
}
