<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Replaces the per-catalog program_departments rows with the shared ic_departments table
 * (one row per department, each belonging to an ic_colleges row), seeded from the 2026
 * department list plus the legacy rows that list has no counterpart for.
 *
 * program_programs, program_inter_dept and scholarships_scholarship are remapped to
 * ic_departments ids and get real FKs. program_inter_dept is rebuilt with a primary key,
 * which also folds per-catalog duplicates and drops links to programs that no longer exist.
 * program_departments records its new ic id per row and is kept as program_departments_bk.
 *
 * Irreversible: merging the per-catalog duplicates loses which legacy row a program used.
 * Roll back by restoring a dump taken before migrating.
 */
final class Version20261008010000 extends AbstractMigration
{
    /**
     * [ic id, college id, department, legacy program_departments ids]. Each ic id reuses the
     * lowest legacy id of its group, so most existing references and public ?department=
     * URLs keep resolving. 65-72 are new departments with no legacy counterpart.
     */
    private const DEPARTMENTS = [
        // College of Arts & Sciences
        [1, 1, 'Africology & African American Studies', [1, 40]],
        [2, 1, 'Art & Design', [2, 41]],
        [3, 1, 'Biology', [3]],
        [4, 1, 'Chemistry', [4]],
        [5, 1, 'Communication, Media & Theatre Arts', [5, 42]],
        [6, 1, 'Computer Science', [6]],
        [7, 1, 'Economics', [7]],
        [8, 1, 'English Language & Literature', [8, 43]],
        [9, 1, 'Geography & Geology', [9, 44]],
        [10, 1, 'History & Philosophy', [10, 45]],
        [11, 1, 'Mathematics & Statistics', [11, 46]],
        [12, 1, 'Music & Dance', [12, 47]],
        [13, 1, 'Physics & Astronomy', [13, 48]],
        [14, 1, 'Political Science', [14]],
        [15, 1, 'Psychology', [15]],
        [16, 1, 'Sociology, Anthropology & Criminology', [16, 49]],
        [17, 1, "Women's & Gender Studies", [17, 50]],
        [18, 1, 'World Languages', [18]],
        [19, 1, 'Interdisciplinary (CAS)', [19]],
        [65, 1, 'Behavioral Science & Social Inquiry', []],
        [66, 1, 'Computing, Data & Quantitative Sciences', []],
        [67, 1, 'Interdisciplinary & Emerging Inquiry', []],
        [68, 1, 'Justice, Governance & Communities', []],
        [69, 1, 'Language, Media & Communication', []],
        [70, 1, 'Natural Sciences', []],
        [71, 1, 'Performing Arts', []],
        [72, 1, 'Social, Behavioral & Complex Systems', []],
        // College of Business
        [20, 2, 'Accounting, Finance, & Information Systems', [20]],
        [21, 2, 'Management', [21]],
        [22, 2, 'Marketing', [22]],
        [23, 2, 'Interdisciplinary (COB)', [23]],
        // College of Education
        [24, 3, 'Leadership & Counseling', [24, 51]],
        [25, 3, 'Special Education & Communication Sciences & Disorders', [25]],
        [26, 3, 'Teacher Education', [26]],
        [27, 3, 'Interdisciplinary (COE)', [27]],
        // GameAbove College of Engineering & Technology
        [28, 4, 'Military Science & Leadership', [28]],
        [29, 4, 'Engineering', [29, 52]],
        [30, 4, 'Information Security & Applied Computing', [30, 53]],
        [31, 4, 'Technology & Professional Services Management', [31, 54]],
        [32, 4, 'Visual & Built Environments', [32, 55]],
        [33, 4, 'Interdisciplinary (COET)', [33, 56]],
        // College of Health & Human Services
        [34, 5, 'Health Promotion & Human Performance', [34, 57]],
        [35, 5, 'Health Sciences', [35, 58]],
        [36, 5, 'Nursing', [36, 59]],
        [37, 5, 'Social Work', [37, 61]],
        [38, 5, 'Interdisciplinary (CHHS)', [38]],
        [60, 5, 'Physician Assistant Studies', [60]],
        // Honors College
        [63, 6, 'Pre-professional Studies', [63]],
        // Academic Programming
        [39, 8, 'Interdisciplinary Programs', [39]],
        [62, 8, 'University Advising & Career Development Center', [62]],
    ];

    /** Legacy departments with no ic counterpart (64: defunct "Environmental Science and Society - HIDDEN"). */
    private const SKIPPED_LEGACY_IDS = [64];

    /**
     * The program_departments rows DEPARTMENTS was built from (id => department). Another
     * database (staging, prod) must hold the same id/name pairs, or the id-based mapping above
     * would silently attach programs to the wrong department.
     */
    private const LEGACY_NAMES = [
        1 => 'Africology & African American Studies',
        2 => 'Art & Design',
        3 => 'Biology',
        4 => 'Chemistry',
        5 => 'Communication, Media & Theatre Arts',
        6 => 'Computer Science',
        7 => 'Economics',
        8 => 'English Language & Literature',
        9 => 'Geography & Geology',
        10 => 'History & Philosophy',
        11 => 'Mathematics & Statistics',
        12 => 'Music & Dance',
        13 => 'Physics & Astronomy',
        14 => 'Political Science',
        15 => 'Psychology',
        16 => 'Sociology, Anthropology & Criminology',
        17 => "Women's & Gender Studies",
        18 => 'World Languages',
        19 => 'Interdisciplinary (CAS)',
        20 => 'Accounting, Finance, and Information Systems',
        21 => 'Management',
        22 => 'Marketing',
        23 => 'Interdisciplinary (COB)',
        24 => 'Leadership & Counseling',
        25 => 'Special Education & Communication Sciences and Disorders',
        26 => 'Teacher Education',
        27 => 'Interdisciplinary (COE)',
        28 => 'Military Science & Leadership',
        29 => 'Engineering',
        30 => 'Information Security & Applied Computing',
        31 => 'Technology & Professional Services Management',
        32 => 'Visual & Built Environments',
        33 => 'Interdisciplinary (CET)',
        34 => 'Health Promotion & Human Performance',
        35 => 'Health Sciences',
        36 => 'Nursing',
        37 => 'Social Work',
        38 => 'Interdisciplinary (CHHS)',
        39 => 'Interdisciplinary Programs',
        40 => 'Africology & African American Studies',
        41 => 'School of Art & Design',
        42 => 'Communication, Media & Theatre Arts',
        43 => 'English Language & Literature',
        44 => 'Geography & Geology',
        45 => 'History & Philosophy',
        46 => 'Mathematics & Statistics',
        47 => 'Music & Dance',
        48 => 'Physics & Astronomy',
        49 => 'Sociology, Anthropology & Criminology',
        50 => "Women's & Gender Studies",
        51 => 'Leadership & Counseling',
        52 => 'Engineering',
        53 => 'Information Security & Applied Computing',
        54 => 'Technology & Professional Services Management',
        55 => 'School of Visual & Built Environments',
        56 => 'Interdisciplinary (COET)',
        57 => 'Health Promotion & Human Performance',
        58 => 'School of Health Sciences',
        59 => 'School of Nursing',
        60 => 'School of Physician Assistant Studies',
        61 => 'School of Social Work',
        62 => 'University Advising & Career Development Center',
        63 => 'Pre-professional Studies',
        64 => 'Environmental Science and Society - HIDDEN',
    ];

    public function getDescription(): string
    {
        return 'Create shared ic_departments, repoint program/scholarship departments to it, retire program_departments';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('program_departments')) {
            $this->warnIf(true, 'program_departments already retired, skipping');
            return;
        }
        $this->abortIf(!$this->tableExists('ic_colleges'), 'ic_colleges is missing; run Version20261008000000 first');
        $this->abortIf($this->tableExists('program_departments_bk'), 'program_departments_bk already exists');
        $this->abortOnUnmappedIds();
        $this->abortOnRenamedDepartments();

        $this->addSql('CREATE TABLE IF NOT EXISTS ic_departments (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            college_id INT UNSIGNED NOT NULL,
            department VARCHAR(255) NOT NULL,
            UNIQUE INDEX UNIQ_ic_departments_department (department),
            INDEX IDX_73CF737B770124B2 (college_id),
            PRIMARY KEY (id),
            CONSTRAINT FK_73CF737B770124B2 FOREIGN KEY (college_id) REFERENCES ic_colleges (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        foreach (self::DEPARTMENTS as [$id, $collegeId, $department]) {
            $this->addSql(
                'INSERT IGNORE INTO ic_departments (id, college_id, department) VALUES (?, ?, ?)',
                [$id, $collegeId, $department]
            );
        }

        // Record each legacy row's ic id. This drives the remaps below and stays on the backup.
        if (!$this->columnExists('program_departments', 'department_id')) {
            $this->addSql('ALTER TABLE program_departments ADD department_id INT UNSIGNED DEFAULT NULL AFTER catalog_id');
        }
        foreach (self::DEPARTMENTS as [$id, , , $legacyIds]) {
            if ($legacyIds !== []) {
                $this->addSql(
                    'UPDATE program_departments SET department_id = ? WHERE id IN (' . implode(',', $legacyIds) . ')',
                    [$id]
                );
            }
        }

        // Every ic id maps to itself in program_departments, so these remaps are safe to re-run.
        $programFkTarget = $this->foreignKeyTarget('program_programs', 'fk_program_departments');
        if ($programFkTarget === 'program_departments') {
            $this->addSql('ALTER TABLE program_programs DROP FOREIGN KEY fk_program_departments');
        }
        $this->addSql('UPDATE program_programs p
            JOIN program_departments pd ON pd.id = p.department_id
            SET p.department_id = pd.department_id
            WHERE pd.department_id IS NOT NULL');
        if ($programFkTarget !== 'ic_departments') {
            $this->addSql('ALTER TABLE program_programs ADD CONSTRAINT fk_program_departments FOREIGN KEY (department_id) REFERENCES ic_departments (id)');
        }

        $this->rebuildInterDept();

        $this->addSql('UPDATE scholarships_scholarship s
            JOIN program_departments pd ON pd.id = s.schlrshp_department_id
            SET s.schlrshp_department_id = pd.department_id');
        if (!$this->columnIsUnsigned('scholarships_scholarship', 'schlrshp_department_id')) {
            $this->addSql('ALTER TABLE scholarships_scholarship MODIFY schlrshp_department_id INT UNSIGNED DEFAULT NULL');
        }
        if ($this->foreignKeyTarget('scholarships_scholarship', 'fk_scholarship_department') === null) {
            $this->addSql('ALTER TABLE scholarships_scholarship ADD CONSTRAINT fk_scholarship_department FOREIGN KEY (schlrshp_department_id) REFERENCES ic_departments (id) ON DELETE SET NULL');
        }

        $this->addSql('RENAME TABLE program_departments TO program_departments_bk');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Per-catalog department rows were merged into ic_departments; restore a pre-migration dump instead.'
        );
    }

    /**
     * Rebuilds program_inter_dept with a primary key and FKs, remapped to ic ids. The
     * INSERT's joins drop links to skipped legacy departments and to deleted programs, and
     * INSERT IGNORE folds pairs that collapse onto the same ic id (e.g. legacy 24 + 51).
     */
    private function rebuildInterDept(): void
    {
        // A previous run stopped between dropping the old table and renaming the new one.
        if (!$this->tableExists('program_inter_dept') && $this->tableExists('program_inter_dept_new')) {
            $this->addSql('RENAME TABLE program_inter_dept_new TO program_inter_dept');
            return;
        }
        if ($this->primaryKeyExists('program_inter_dept')) {
            $this->warnIf(true, 'program_inter_dept already rebuilt, skipping');
            return;
        }

        $this->addSql('DROP TABLE IF EXISTS program_inter_dept_new');
        $this->addSql('CREATE TABLE program_inter_dept_new (
            program_id INT UNSIGNED NOT NULL,
            department_id INT UNSIGNED NOT NULL,
            INDEX IDX_program_inter_dept_department (department_id),
            PRIMARY KEY (program_id, department_id),
            CONSTRAINT fk_program_inter_dept_program FOREIGN KEY (program_id) REFERENCES program_programs (id) ON DELETE CASCADE,
            CONSTRAINT fk_program_inter_dept_department FOREIGN KEY (department_id) REFERENCES ic_departments (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT IGNORE INTO program_inter_dept_new (program_id, department_id)
            SELECT DISTINCT x.program_id, pd.department_id
            FROM program_inter_dept x
            JOIN program_departments pd ON pd.id = x.department_id
            JOIN program_programs p ON p.id = x.program_id
            WHERE pd.department_id IS NOT NULL');
        $this->addSql('DROP TABLE program_inter_dept');
        $this->addSql('RENAME TABLE program_inter_dept_new TO program_inter_dept');
    }

    /**
     * Stops before any change if a legacy department has no mapping, or a program or
     * scholarship points at a department that would not survive the remap.
     */
    private function abortOnUnmappedIds(): void
    {
        $mappedLegacyIds = array_merge(...array_column(self::DEPARTMENTS, 3));
        $legacyIds = array_map('intval', $this->connection->fetchFirstColumn('SELECT id FROM program_departments'));
        $unmapped = array_diff($legacyIds, $mappedLegacyIds, self::SKIPPED_LEGACY_IDS);
        $this->abortIf($unmapped !== [], 'Unmapped program_departments ids: ' . implode(', ', $unmapped));

        $allowed = implode(',', array_unique(array_merge($mappedLegacyIds, array_column(self::DEPARTMENTS, 0))));
        $programs = $this->connection->fetchFirstColumn(
            "SELECT id FROM program_programs WHERE department_id NOT IN ($allowed)"
        );
        $this->abortIf($programs !== [], 'Programs with an unmappable department: ' . implode(', ', $programs));

        $scholarships = $this->connection->fetchFirstColumn(
            "SELECT id FROM scholarships_scholarship
             WHERE schlrshp_department_id IS NOT NULL AND schlrshp_department_id NOT IN ($allowed)"
        );
        $this->abortIf($scholarships !== [], 'Scholarships with an unmappable department: ' . implode(', ', $scholarships));
    }

    /**
     * Stops before any change if a program_departments id holds a different department than
     * the one the mapping was built from (names compared trimmed and case-insensitively).
     */
    private function abortOnRenamedDepartments(): void
    {
        $mismatches = [];
        foreach ($this->connection->fetchAllAssociative('SELECT id, department FROM program_departments ORDER BY id') as $row) {
            $expected = self::LEGACY_NAMES[(int) $row['id']] ?? null;
            if ($expected !== null && strcasecmp(trim((string) $row['department']), $expected) !== 0) {
                $mismatches[] = sprintf('%d: "%s" (expected "%s")', $row['id'], $row['department'], $expected);
            }
        }
        $this->abortIf(
            $mismatches !== [],
            'program_departments ids hold different departments than the mapping expects; update DEPARTMENTS/LEGACY_NAMES first: '
                . implode('; ', $mismatches)
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

    private function columnExists(string $table, string $column): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?",
            [$table, $column]
        );
    }

    private function columnIsUnsigned(string $table, string $column): bool
    {
        $type = $this->connection->fetchOne(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?",
            [$table, $column]
        );
        return is_string($type) && str_contains($type, 'unsigned');
    }

    private function primaryKeyExists(string $table): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_TYPE = 'PRIMARY KEY'",
            [$table]
        );
    }

    /**
     * The table a named FK references, or null when the FK does not exist.
     */
    private function foreignKeyTarget(string $table, string $constraint): ?string
    {
        $target = $this->connection->fetchOne(
            "SELECT REFERENCED_TABLE_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?",
            [$table, $constraint]
        );
        return $target === false ? null : $target;
    }
}
