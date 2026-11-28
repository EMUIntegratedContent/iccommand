<?php

namespace App\Tests\Support;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * POST /api/programs/ payload for a test program (the using class defines NAME_PREFIX).
 *
 * Creating a program reads its degree, type and catalog_id from rows that already exist
 * (ProgramsService::getCatalogIdFromName() looks at existing programs), so on an empty test
 * database one seed program, degree, type and delivery mode are inserted first. They are rolled
 * back with the rest of the test.
 */
trait ProgramFixtures
{
    private function programPayload(EntityManagerInterface $em): array
    {
        $conn = $em->getConnection();
        $this->ensureUndergraduateCatalog($conn);

        return [
            'catalog' => 'undergraduate',
            'program' => self::NAME_PREFIX.' Program',
            'full_name' => self::NAME_PREFIX.' Program Full',
            'degree_id' => (int) $conn->fetchOne('SELECT MIN(degree_id) FROM program_programs'),
            'type_id' => (int) $conn->fetchOne('SELECT MIN(type_id) FROM program_programs'),
            'is_active' => true,
            'delivery_ids' => [(int) $conn->fetchOne('SELECT MIN(delivery_id) FROM program_delivery')],
            'college_ids' => [],
            'department_ids' => [],
            'keyword_ids' => [],
        ];
    }

    private function ensureUndergraduateCatalog(Connection $conn): void
    {
        if ($conn->fetchOne("SELECT 1 FROM program_programs WHERE catalog = 'undergraduate' LIMIT 1")) {
            return;
        }

        $conn->insert('program_degrees', ['degree' => 'ZZ Seed Degree', 'degree_short' => 'ZZ']);
        $degreeId = (int) $conn->lastInsertId();
        $conn->insert('program_types', ['type' => 'ZZ Seed Type']);
        $typeId = (int) $conn->lastInsertId();
        $conn->insert('program_programs', [
            'catalog' => 'undergraduate',
            'catalog_id' => 1,
            'program' => 'ZZ Seed Program',
            'full_name' => 'ZZ Seed Program',
            'slug' => 'zz-seed-program',
            'degree_id' => $degreeId,
            'type_id' => $typeId,
        ]);
        // 1 = online (see ProgramsService); any id works for the tests.
        $conn->insert('program_delivery', ['program_id' => (int) $conn->lastInsertId(), 'delivery_id' => 1]);
    }
}
