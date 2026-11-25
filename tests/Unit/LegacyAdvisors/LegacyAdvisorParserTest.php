<?php

namespace App\Tests\Unit\LegacyAdvisors;

use App\Entity\Ic\IcAdvisorType;
use App\Service\LegacyAdvisors\LegacyAdvisorParser;
use PHPUnit\Framework\TestCase;

/**
 * Cleaning and parsing single values from the legacy advisor tables. The cases are real values
 * from advisor_programs and uacdc_advisor_programs.
 */
class LegacyAdvisorParserTest extends TestCase
{
    public static function cleanTextCases(): array
    {
        return [
            'double-encoded accents' => ["MÃ³nica MillÃ¡n, PhD", 'Mónica Millán, PhD'],
            'double-encoded zero-width space' => ["Aaron Strumingerâ€‹, Ph.D.", 'Aaron Struminger, Ph.D.'],
            'double-encoded no-break space' => ["RebeccaÂ\u{00A0}Spragg", 'Rebecca Spragg'],
            'trailing tab' => ["jdesider@emich.edu\t", 'jdesider@emich.edu'],
            'blank' => ['   ', null],
            'null' => [null, null],
            'already clean' => ['Mónica', 'Mónica'],
            'double-encoded en dash' => ['Educational Leadership â€“ K-12 Administration [M.A.]', 'Educational Leadership – K-12 Administration [M.A.]'],
        ];
    }

    /** @dataProvider cleanTextCases */
    public function testCleanText(?string $raw, ?string $expected): void
    {
        $this->assertSame($expected, LegacyAdvisorParser::cleanText($raw));
    }

    public static function classifyCases(): array
    {
        return [
            ['Visit the CMTA website for advising information.', null],
            ['Contact department for information', null],
            ['Please contact the school for advisor information.', null],
            ['Program Advising', null],
            ['Contact Program for Advising', null],
            ['College of Business Academic Services', IcAdvisorType::ORGANIZATION],
            ['University Advising & Career Development Center', IcAdvisorType::ORGANIZATION],
            ['Joy Beatty, Ph.D., Department Head', IcAdvisorType::PERSON],
            ['Hannah Seidel, Ph.D.', IcAdvisorType::PERSON],
        ];
    }

    /** @dataProvider classifyCases */
    public function testClassifyName(string $name, ?IcAdvisorType $expected): void
    {
        $this->assertSame($expected, LegacyAdvisorParser::classifyName($name));
    }

    public static function personNameCases(): array
    {
        return [
            ['Alice Jo Rainville Ph.D., RD, CHE, SNS, FAND', 'Alice Jo', 'Rainville'],
            ['Lynne M. Shetron-Rama PhD MT (ASCP)', 'Lynne M.', 'Shetron-Rama'],
            ['Sharon Holt MHS OTRL', 'Sharon', 'Holt'],
            ['Jennifer Desiderio Ed. S.', 'Jennifer', 'Desiderio'],
            ['Lidia Lee Ph.D. CCC-A', 'Lidia', 'Lee'],
            ['Ildiko (Ildi) Porter-Szucs, PhD', 'Ildiko (Ildi)', 'Porter-Szucs'],
            ['Myung-Sook Koh, Ed.D', 'Myung-Sook', 'Koh'],
            ['Robert Peavler, DMA', 'Robert', 'Peavler'],
            ['Tanweer Shapla,  PhD', 'Tanweer', 'Shapla'],
        ];
    }

    /** @dataProvider personNameCases */
    public function testSplitPersonName(string $name, string $first, string $last): void
    {
        $this->assertSame(['first' => $first, 'last' => $last], LegacyAdvisorParser::splitPersonName($name));
    }

    public static function addressCases(): array
    {
        return [
            ['319-M Porter', '319-M', 'Porter'],
            ['206 J Marshall', '206J', 'Marshall'],
            ['N101 Alexander', 'N101', 'Alexander'],
            ['1 Hill Hall', '1', 'Hill Hall'],
            ['319N Porter Building', '319N', 'Porter Building'],
            ['Hill Hall', null, 'Hill Hall'],
            [null, null, null],
        ];
    }

    /** @dataProvider addressCases */
    public function testParseAddress(?string $address, ?string $room, ?string $building): void
    {
        $this->assertSame(['room' => $room, 'building' => $building], LegacyAdvisorParser::parseAddress($address));
    }

    public static function phoneCases(): array
    {
        return [
            ['734-487-3300', '734.487.3300'],
            [' 734.487.3300', '734.487.3300'],
            ["734.487.0255\t", '734.487.0255'],
            ['N/A', null],
            ['Stephanie Newell, Ph.D', null],
            [null, null],
        ];
    }

    /** @dataProvider phoneCases */
    public function testCleanPhone(?string $phone, ?string $expected): void
    {
        $this->assertSame($expected, LegacyAdvisorParser::cleanPhone($phone));
    }

    public static function emailCases(): array
    {
        return [
            [' ccroxall@emich.edu', 'ccroxall@emich.edu'],
            ['COE_advising@emich.edu', 'coe_advising@emich.edu'],
            ['-', null],
            ['Contact department for advisor information', null],
            [null, null],
        ];
    }

    /** @dataProvider emailCases */
    public function testCleanEmail(?string $email, ?string $expected): void
    {
        $this->assertSame($expected, LegacyAdvisorParser::cleanEmail($email));
    }

    public static function urlCases(): array
    {
        return [
            ['/cob/faculty/b_ross.php', 'https://www.emich.edu/cob/faculty/b_ross.php'],
            ["\t/history-philosophy/x.php", 'https://www.emich.edu/history-philosophy/x.php'],
            ['https://example.edu/a', 'https://example.edu/a'],
            [null, null],
            ['  ', null],
        ];
    }

    /** @dataProvider urlCases */
    public function testAbsoluteUrl(?string $url, ?string $expected): void
    {
        $this->assertSame($expected, LegacyAdvisorParser::absoluteUrl($url));
    }

    public function testProgramKeyIgnoresQuotesCaseAndEdgeSpace(): void
    {
        $this->assertSame(
            LegacyAdvisorParser::programKey("Children's and Young Adult Literature [BA]"),
            LegacyAdvisorParser::programKey('Children’s and Young Adult Literature [BA] ')
        );
        $this->assertSame(LegacyAdvisorParser::programKey('Spanish Minor'), LegacyAdvisorParser::programKey('spanish  minor'));
        $this->assertNotSame(LegacyAdvisorParser::programKey('Spanish Minor'), LegacyAdvisorParser::programKey('Spanish [BA]'));
    }

    public function testBuildingKey(): void
    {
        $this->assertSame(LegacyAdvisorParser::buildingKey('Pray-Harrold'), LegacyAdvisorParser::buildingKey('Pray Harrold'));
        $this->assertSame('alexander', LegacyAdvisorParser::buildingKey('Alexander Hall'));
        $this->assertSame('porter', LegacyAdvisorParser::buildingKey('Porter Building'));
        $this->assertSame('mark jefferson science complex', LegacyAdvisorParser::buildingKey('Mark Jefferson Science Complex'));
    }
}
