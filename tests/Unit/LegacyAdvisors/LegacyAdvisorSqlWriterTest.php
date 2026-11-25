<?php

namespace App\Tests\Unit\LegacyAdvisors;

use App\Entity\Ic\IcAdvisorType;
use App\Service\LegacyAdvisors\LegacyAdvisor;
use App\Service\LegacyAdvisors\LegacyAdvisorSqlWriter;
use App\Service\LegacyAdvisors\LegacyImportResult;
use PHPUnit\Framework\TestCase;

/**
 * The legacy import SQL: idempotent statements that find buildings, programs and advisors by
 * name, never by id.
 */
class LegacyAdvisorSqlWriterTest extends TestCase
{
    public function testStartsWithCharsetAndTransaction(): void
    {
        $sql = $this->write([$this->tang()]);

        $this->assertStringStartsWith('SET NAMES utf8mb4;', $this->withoutComments($sql));
        $this->assertStringContainsString("START TRANSACTION;\n", $sql);
        $this->assertLessThan(strrpos($sql, 'SELECT COUNT(*)'), strpos($sql, "COMMIT;\n"));
    }

    public function testInsertIsGuardedByNaturalKey(): void
    {
        $sql = $this->write([$this->tang(), $this->tang(email: null, key: 'person|tang|herman')]);

        $this->assertStringContainsString(
            "WHERE NOT EXISTS (SELECT 1 FROM ic_advisors WHERE type = 'Person' AND last_name = 'Tang' AND email <=> 'htang2@emich.edu');",
            $sql
        );
        $this->assertStringContainsString(
            "SET @advisor = (SELECT id FROM ic_advisors WHERE type = 'Person' AND last_name = 'Tang' AND email <=> 'htang2@emich.edu' ORDER BY id LIMIT 1);",
            $sql
        );
        $this->assertStringContainsString(
            "WHERE NOT EXISTS (SELECT 1 FROM ic_advisors WHERE type = 'Person' AND last_name = 'Tang' AND first_name = 'Herman');",
            $sql
        );
    }

    public function testInsertsTheAdvisorsFields(): void
    {
        $sql = $this->write([$this->tang()]);

        $this->assertStringContainsString(
            "INSERT INTO ic_advisors (type, first_name, last_name, organization, email, phone, room, building_id, url, created, created_by, updated, updated_by)\n"
            . "SELECT 'Person', 'Herman', 'Tang', NULL, 'htang2@emich.edu', '734.487.0226', '111', "
            . "(SELECT id FROM map_item WHERE discr = 'building' AND name = 'Sill Hall'), NULL, NOW(), 'legacy_import', NOW(), 'legacy_import'\n"
            . "FROM DUAL WHERE NOT EXISTS",
            $sql
        );
    }

    public function testOrganizationKeyAndNullBuilding(): void
    {
        $sql = $this->write([$this->office('College of Education Advising Center')]);

        $this->assertStringContainsString(
            "SELECT 'Organization', NULL, NULL, 'College of Education Advising Center', NULL, NULL, NULL, NULL, NULL, NOW()",
            $sql
        );
        $this->assertStringContainsString(
            "WHERE NOT EXISTS (SELECT 1 FROM ic_advisors WHERE type = 'Organization' AND organization = 'College of Education Advising Center');",
            $sql
        );
    }

    public function testQuoting(): void
    {
        $sql = $this->write([$this->office('O\'Neil \\ Center')]);

        $this->assertStringContainsString("'O''Neil \\\\ Center'", $sql);
        $this->assertStringNotContainsString("'O'Neil", $sql);
    }

    public function testLinksAndLegacyPrograms(): void
    {
        $sql = $this->write([$this->tang(
            programs: [['Dietetics [BS + MS]', 'undergraduate'], ['Dietetics [BS + MS]', 'graduate']],
            legacyPrograms: ['Dietetics [BS + MS]', 'Nutrition Minor'],
        )]);

        $this->assertStringContainsString(
            "UPDATE ic_advisors SET legacy_programs = 'Dietetics [BS + MS], Nutrition Minor' WHERE id = @advisor AND legacy_programs IS NULL;",
            $sql
        );
        $this->assertStringContainsString(
            "INSERT IGNORE INTO program_advisor (program_id, advisor_id)\n"
            . "SELECT p.id, @advisor FROM program_programs p WHERE (TRIM(p.full_name), p.catalog) IN "
            . "(('Dietetics [BS + MS]', 'undergraduate'), ('Dietetics [BS + MS]', 'graduate'));",
            $sql
        );
    }

    public function testHeaderHasExpectedCounts(): void
    {
        $sql = $this->write([
            $this->tang(programs: [['A', 'graduate'], ['B', 'graduate']]),
            $this->office('College of Education Advising Center', programs: [['C', 'undergraduate']]),
        ]);

        $this->assertStringContainsString('-- Expected: 2 advisors, 3 links', $sql);
        $this->assertStringContainsString('-- Source: test source', $sql);
    }

    /**
     * A building renamed or missing on the target database would import as NULL without changing
     * the advisor or link counts, so the checks count buildings and list the missing names.
     */
    public function testChecksCoverBuildings(): void
    {
        $sql = $this->write([
            $this->tang(),
            $this->office('College of Education Advising Center'),
        ]);

        $this->assertStringContainsString('-- Expected: 2 advisors, 1 links, 1 with a building', $sql);
        $this->assertStringContainsString(
            "SELECT COUNT(*) AS imported_with_building FROM ic_advisors WHERE created_by = 'legacy_import' AND building_id IS NOT NULL;",
            $sql
        );
        $this->assertStringContainsString(
            "SELECT b.name AS missing_building FROM (SELECT 'Sill Hall' AS name) b\n"
            . "WHERE NOT EXISTS (SELECT 1 FROM map_item m WHERE m.discr = 'building' AND m.name = b.name);",
            $sql
        );
    }

    /**
     * The file can't see later edits to the legacy tables on its own, so it ends by showing their
     * row counts and last update, to compare with the Source line.
     */
    public function testChecksShowTheLegacyTables(): void
    {
        $sql = $this->write([$this->tang()]);

        $this->assertStringContainsString(
            "SELECT 'advisor_programs' AS legacy_table, COUNT(*) AS row_count, MAX(updated_at) AS last_updated FROM advisor_programs\n"
            . "UNION ALL SELECT 'uacdc_advisor_programs', COUNT(*), MAX(updated_at) FROM uacdc_advisor_programs;",
            $sql
        );
        $this->assertLessThan(strpos($sql, 'FROM uacdc_advisor_programs;'), strpos($sql, "COMMIT;\n"));
    }

    public function testNoLinkStatementWithoutPrograms(): void
    {
        $sql = $this->withoutComments($this->write([$this->tang(programs: [], legacyPrograms: [])]));

        $this->assertStringNotContainsString('INSERT IGNORE', $sql);
        $this->assertStringNotContainsString('UPDATE ic_advisors', $sql);
    }

    private function write(array $advisors): string
    {
        return (new LegacyAdvisorSqlWriter())->write(new LegacyImportResult($advisors, [], [], []), 'test source');
    }

    private function withoutComments(string $sql): string
    {
        return ltrim(preg_replace('/^--.*\n/m', '', $sql));
    }

    private function tang(
        ?string $email = 'htang2@emich.edu',
        string $key = 'person|tang|htang2@emich.edu',
        array $programs = [['Quality Management [Graduate Certificate]', 'graduate']],
        array $legacyPrograms = ['Quality Management [Graduate Certificate]'],
    ): LegacyAdvisor {
        return new LegacyAdvisor($key, IcAdvisorType::PERSON, 'Herman', 'Tang', null, $email, '734.487.0226', '111', 'Sill Hall', null, $legacyPrograms, $programs);
    }

    private function office(string $name, array $programs = []): LegacyAdvisor
    {
        return new LegacyAdvisor('organization|' . mb_strtolower($name), IcAdvisorType::ORGANIZATION, null, null, $name, null, null, null, null, null, [], $programs);
    }
}
