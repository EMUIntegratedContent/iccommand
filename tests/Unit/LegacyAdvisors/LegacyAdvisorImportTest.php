<?php

namespace App\Tests\Unit\LegacyAdvisors;

use App\Entity\Ic\IcAdvisorType;
use App\Service\LegacyAdvisors\LegacyAdvisor;
use App\Service\LegacyAdvisors\LegacyAdvisorImport;
use App\Service\LegacyAdvisors\LegacyImportResult;
use PHPUnit\Framework\TestCase;

/**
 * Grouping legacy advisor rows into advisors, picking each field's value, and matching their
 * programs and buildings. Fixture values are copied from the legacy tables.
 */
class LegacyAdvisorImportTest extends TestCase
{
    private const PROGRAMS = [
        ['full_name' => 'Dietetics [BS + MS] ', 'catalog' => 'undergraduate'],
        ['full_name' => 'Dietetics [BS + MS]', 'catalog' => 'graduate'],
        ['full_name' => 'Spanish Minor', 'catalog' => 'undergraduate'],
        ['full_name' => 'PreK-12 French Teaching [BA]', 'catalog' => 'undergraduate'],
        ['full_name' => 'Quality Management [Graduate Certificate]', 'catalog' => 'graduate'],
    ];

    private const BUILDINGS = ['Mark Jefferson Science Complex', 'Rackham Building', 'Sill Hall', 'Hill Hall', 'Porter Building', 'Pray-Harrold'];

    public function testSkippedNamesMakeNoAdvisor(): void
    {
        $result = $this->build([$this->row('Visit the English website for advising information.', 'Spanish Minor')]);

        $this->assertSame([], $result->advisors);
        $this->assertSame(['Visit the English website for advising information.' => 1], $result->skippedNames);
    }

    public function testVariantsOfOnePersonMerge(): void
    {
        $result = $this->build([
            $this->row('Herman Tang Ph.D.', 'Quality Management [Graduate Certificate]', email: 'htang2@emich.edu'),
            $this->row('Herman Tang, Ph.D.', 'Lean Six Sigma [Graduate Certificate]', email: 'htang2@emich.edu'),
        ]);

        $this->assertCount(1, $result->advisors);
        $advisor = $result->advisors[0];
        $this->assertSame('person|tang|htang2@emich.edu', $advisor->key);
        $this->assertSame(IcAdvisorType::PERSON, $advisor->type);
        $this->assertSame(['Herman', 'Tang', null], [$advisor->firstName, $advisor->lastName, $advisor->organization]);
    }

    public function testMostCommonValueWinsAndTieGoesToLatest(): void
    {
        $result = $this->build([
            $this->row('Brian Sellers, PhD', 'A', email: 'bseller3@emich.edu', address: '712 Pray-Harrold', phone: '734.487.0012', updated: '2025-08-12 14:15:08'),
            $this->row('Brian Sellers, PhD', 'B', email: 'bseller3@emich.edu', address: '712 Pray-Harrold', phone: '734.487.0013', updated: '2026-11-24 18:41:41'),
            $this->row('Brian Sellers, PhD', 'C', email: 'bseller3@emich.edu', address: '713B Pray-Harrold', phone: null, updated: '2026-11-24 18:41:41'),
        ]);

        $advisor = $result->advisors[0];
        $this->assertSame('712', $advisor->room);
        $this->assertSame('734.487.0013', $advisor->phone);
    }

    public function testSharedMailboxStaysSeparate(): void
    {
        $result = $this->build([
            $this->row('Cierra Presberry, PhD', 'A', email: 'coe_ldcn_spa_phd@emich.edu'),
            $this->row('Rachel Radina, Ph.D.', 'B', email: 'coe_ldcn_spa_phd@emich.edu'),
        ]);

        $this->assertSame(
            ['person|presberry|coe_ldcn_spa_phd@emich.edu', 'person|radina|coe_ldcn_spa_phd@emich.edu'],
            array_map(fn (LegacyAdvisor $a) => $a->key, $result->advisors)
        );
    }

    public function testMergeIntoAndFirstNameOverrides(): void
    {
        $import = new LegacyAdvisorImport(
            mergeInto: ['person|kautz|coe_ldcn_spa_phd@emich.edu' => 'person|kautz|coe_ldcn_k12_info@emich.edu'],
            firstNames: ['person|ross|bwadding@emich.edu' => 'Barbara'],
            programAliases: [],
            buildingAliases: [],
        );
        $result = $import->build([
            $this->row('Matt Kautz, PhD', 'Educational Leadership [Sp.A.]', email: 'coe_ldcn_spa_phd@emich.edu'),
            $this->row('Matt Kautz, PhD', 'Educational Leadership – K-12 Administration [M.A.]', email: 'coe_ldcn_k12_info@emich.edu'),
            $this->row('Matt Kautz, Ph.D.', 'Central Office/Superintendent Endorsement [Post-Master\'s Certificate]', email: 'coe_ldcn_k12_info@emich.edu'),
            $this->row('Barb Ross', 'Accounting [BBA + MS]', email: 'bwadding@emich.edu', updated: '2026-11-24 18:41:41'),
            $this->row('Barbara Ross Ph.D.', 'Accounting [BBA + MS]', email: 'bwadding@emich.edu', updated: '2025-08-12 14:15:08'),
        ], self::PROGRAMS, self::BUILDINGS);

        $this->assertCount(2, $result->advisors);
        [$kautz, $ross] = $result->advisors;
        $this->assertSame('person|kautz|coe_ldcn_k12_info@emich.edu', $kautz->key);
        $this->assertSame('coe_ldcn_k12_info@emich.edu', $kautz->email);
        $this->assertSame(
            "Central Office/Superintendent Endorsement [Post-Master's Certificate], Educational Leadership [Sp.A.], Educational Leadership – K-12 Administration [M.A.]",
            $kautz->legacyProgramsText()
        );
        $this->assertSame('Barbara', $ross->firstName);
    }

    public function testProgramsMatchAcrossCatalogs(): void
    {
        $result = $this->build([$this->row('Sandy Pernecky, M.S., R.D.N.', 'Dietetics [BS + MS]', email: 'dieteticsms_advising@emich.edu')]);

        $this->assertSame(
            [['Dietetics [BS + MS]', 'undergraduate'], ['Dietetics [BS + MS]', 'graduate']],
            $result->advisors[0]->programs
        );
        $this->assertSame([], $result->unmatchedPrograms);
    }

    public function testProgramAliasAddsTargets(): void
    {
        $import = new LegacyAdvisorImport(
            mergeInto: [],
            firstNames: [],
            programAliases: ['PK-12 French Teaching [BA]' => [['PreK-12 French Teaching [BA]', 'undergraduate']]],
            buildingAliases: [],
        );
        $result = $import->build([$this->row('Audrey Viguier, PhD', 'PK-12 French Teaching [BA]', email: 'aviguier@emich.edu')], self::PROGRAMS, self::BUILDINGS);

        $this->assertSame([['PreK-12 French Teaching [BA]', 'undergraduate']], $result->advisors[0]->programs);
        $this->assertSame([], $result->unmatchedPrograms);
    }

    public function testLegacyProgramsListsEveryNameOnce(): void
    {
        $result = $this->build([
            $this->row('Jennifer Farley', 'Spanish Minor', email: 'jfarley5@emich.edu'),
            $this->row('Jennifer Farley', 'spanish minor ', email: 'jfarley5@emich.edu'),
            $this->row('Jennifer Farley', 'Graduate Coordinator', email: 'jfarley5@emich.edu'),
        ]);

        $advisor = $result->advisors[0];
        $this->assertSame('Graduate Coordinator, Spanish Minor', $advisor->legacyProgramsText());
        $this->assertSame([['Spanish Minor', 'undergraduate']], $advisor->programs);
        $this->assertSame(['Graduate Coordinator' => ['person|farley|jfarley5@emich.edu']], $result->unmatchedPrograms);
    }

    public function testBuildingAndRoom(): void
    {
        $result = $this->build([
            $this->row('Deborah Heyl-Clegg, PhD', 'A', email: 'dheylcle@emich.edu', address: '541C Mark Jefferson'),
            $this->row('Renuku "Ray" Roche, PhD, MS, OTR/L, FNAP, Program Director', 'B', email: 'rroche@emich.edu', address: '251 Packham'),
        ]);

        [$heylClegg, $roche] = $result->advisors;
        $this->assertSame(['541C', 'Mark Jefferson Science Complex'], [$heylClegg->room, $heylClegg->buildingName]);
        $this->assertSame(['251 Packham', null], [$roche->room, $roche->buildingName]);
        $this->assertSame('Renuku "Ray"', $roche->firstName);
        $this->assertSame(['251 Packham'], $result->unmatchedAddresses);

        $aliased = (new LegacyAdvisorImport([], [], [], ['Packham' => 'Rackham Building']))
            ->build([$this->row('Renuku "Ray" Roche, PhD', 'B', email: 'rroche@emich.edu', address: '251 Packham')], self::PROGRAMS, self::BUILDINGS);
        $this->assertSame(['251', 'Rackham Building'], [$aliased->advisors[0]->room, $aliased->advisors[0]->buildingName]);
        $this->assertSame([], $aliased->unmatchedAddresses);
    }

    public function testOrganization(): void
    {
        $result = $this->build([$this->row(
            'College of Business Academic Services',
            'Spanish Minor',
            email: 'cob.undergraduate@emich.edu',
            address: '1 Hill Hall',
            website: '/cob/student-resources/advising/undergraduate-advising.php'
        )]);

        $advisor = $result->advisors[0];
        $this->assertSame('organization|college of business academic services', $advisor->key);
        $this->assertSame(IcAdvisorType::ORGANIZATION, $advisor->type);
        $this->assertSame(['College of Business Academic Services', null, null], [$advisor->organization, $advisor->firstName, $advisor->lastName]);
        $this->assertSame(['1', 'Hill Hall'], [$advisor->room, $advisor->buildingName]);
        $this->assertSame('https://www.emich.edu/cob/student-resources/advising/undergraduate-advising.php', $advisor->url);
    }

    public function testMojibakeIsRepairedBeforeGrouping(): void
    {
        $result = $this->build([
            $this->row("MÃ³nica MillÃ¡n, PhD", 'Spanish Minor', email: 'mmillan@emich.edu'),
            $this->row('Mónica Millán, PhD', 'Spanish Minor', email: 'mmillan@emich.edu'),
        ]);

        $this->assertCount(1, $result->advisors);
        $this->assertSame(['Mónica', 'Millán'], [$result->advisors[0]->firstName, $result->advisors[0]->lastName]);
    }

    private function build(array $rows): LegacyImportResult
    {
        return (new LegacyAdvisorImport([], [], [], []))->build($rows, self::PROGRAMS, self::BUILDINGS);
    }

    private function row(
        ?string $name,
        ?string $program,
        ?string $email = null,
        ?string $address = null,
        ?string $phone = null,
        ?string $website = null,
        string $updated = '2026-01-01 00:00:00',
        string $source = 'advisor_programs',
    ): array {
        return [
            'source' => $source,
            'program' => $program,
            'advisor_name' => $name,
            'advisor_address' => $address,
            'advisor_phone' => $phone,
            'advisor_email' => $email,
            'website' => $website,
            'updated_at' => $updated,
        ];
    }
}
