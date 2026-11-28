<?php

namespace App\Tests\Api\Advisors;

use App\Entity\Ic\IcAdvisor;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Tests\Support\ProgramFixtures;

/**
 * End-to-end test for the advisors API (/api/advisors), including an advisor's building, linking
 * programs and scholarships from the advisor side, the form's option routes, the rule that an
 * advisor can't be deleted while a program or scholarship uses it, and the picker's options
 * route. Test rows are ZZ-prefixed and temp users zz_ictest_-prefixed; each test is rolled back (dama/doctrine-test-bundle).
 */
class AdvisorControllerTest extends WebTestCase
{
    use ProgramFixtures;

    private const NAME_PREFIX = 'ZZ Ic Advisor Test';
    private const USERNAME_PREFIX = 'zz_ictest_';

    public function testCrudRoundTrip(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'advisor_admin'));
        $buildingId = $this->createBuilding($client, $em);

        // The form's building options: id and name only.
        $client->request('GET', '/api/advisors/buildings');
        $this->assertResponseStatusCodeSame(200);
        $buildings = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['id' => $buildingId, 'name' => self::NAME_PREFIX . ' Building'], current(array_filter($buildings, fn (array $b) => $b['id'] === $buildingId)));

        // CREATE
        $this->json($client, 'POST', '/api/advisors', [
            'type' => 'Person',
            'lastName' => self::NAME_PREFIX . ' Doe',
            'firstName' => 'Jane',
            'email' => 'zz.jane@emich.edu',
            'phone' => '734-487-1234',
            'room' => '101',
            'area' => 'Admissions',
            'buildingId' => $buildingId,
            'url' => 'https://www.emich.edu/jane',
            'foragerUrl' => 'https://www.emich.edu/forager',
            'imageUrl' => 'https://www.emich.edu/jane.jpg',
        ]);
        $this->assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Person', $created['type']);
        $this->assertSame(self::NAME_PREFIX . ' Doe, Jane', $created['displayName']);
        $this->assertSame('https://www.emich.edu/forager', $created['foragerUrl']);
        $this->assertSame($buildingId, $created['buildingId']);
        $this->assertSame(self::NAME_PREFIX . ' Building', $created['buildingName']);
        $this->assertArrayNotHasKey('building', $created);
        // Audit fields hold the username, like the other Blameable entities.
        $this->assertSame(self::USERNAME_PREFIX . 'advisor_admin', $created['createdBy']);
        $this->assertSame(self::USERNAME_PREFIX . 'advisor_admin', $created['updatedBy']);
        $this->assertNotEmpty($created['created']);
        $id = $created['id'];

        // VALIDATION -> 422
        $invalid = [
            'unknown type' => ['type' => 'Robot', 'lastName' => 'Doe', 'firstName' => 'Jane'],
            'missing type' => ['lastName' => 'Doe', 'firstName' => 'Jane'],
            'person without last name' => ['type' => 'Person', 'firstName' => 'Jane'],
            'person without first name' => ['type' => 'Person', 'lastName' => 'Doe'],
            'organization without a name' => ['type' => 'Organization', 'lastName' => 'Doe', 'firstName' => 'Jane'],
            'bad email' => ['type' => 'Organization', 'organization' => 'ZZ', 'email' => 'nope'],
            'bad url' => ['type' => 'Organization', 'organization' => 'ZZ', 'url' => 'nope'],
            'script url' => ['type' => 'Organization', 'organization' => 'ZZ', 'url' => 'javascript:alert(1)'],
            'bad forager url' => ['type' => 'Organization', 'organization' => 'ZZ', 'foragerUrl' => 'nope'],
            'bad image url' => ['type' => 'Organization', 'organization' => 'ZZ', 'imageUrl' => 'nope'],
            'unknown building' => ['type' => 'Organization', 'organization' => 'ZZ', 'buildingId' => 999999999],
            'non-numeric building' => ['type' => 'Organization', 'organization' => 'ZZ', 'buildingId' => 'abc'],
            'boolean building' => ['type' => 'Organization', 'organization' => 'ZZ', 'buildingId' => true],
        ];
        foreach ($invalid as $label => $payload) {
            $this->json($client, 'POST', '/api/advisors', $payload);
            $this->assertSame(422, $client->getResponse()->getStatusCode(), $label);
        }

        // UPDATE: a person can become an organization
        $this->json($client, 'PUT', '/api/advisors/' . $id, [
            'type' => 'Organization',
            'organization' => self::NAME_PREFIX . ' Office',
            'lastName' => null,
            'firstName' => null,
            'email' => '',
        ]);
        $this->assertResponseStatusCodeSame(200);
        $updated = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(self::NAME_PREFIX . ' Office', $updated['displayName']);
        $this->assertNull($updated['email']);
        $this->assertSame('734-487-1234', $updated['phone']); // Omitted keys are left alone.
        $this->assertSame($buildingId, $updated['buildingId']);

        // LIST + GET one carry the display name, counts and usage lists
        $client->request('GET', '/api/advisors?page=1&limit=10&searchterm=' . urlencode(self::NAME_PREFIX . ' Office'));
        $this->assertResponseStatusCodeSame(200);
        $list = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $list['totalRows']);
        $this->assertSame(self::NAME_PREFIX . ' Office', $list['advisors'][0]['display_name']);
        $this->assertSame(self::NAME_PREFIX . ' Building', $list['advisors'][0]['building_name']);
        foreach (['program', 'scholarship'] as $noun) {
            $this->assertSame(0, $list['advisors'][0][$noun . '_count']);
        }
        $this->assertArrayNotHasKey('building_count', $list['advisors'][0]);

        $client->request('GET', '/api/advisors/' . $id);
        $this->assertResponseStatusCodeSame(200);
        $one = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Organization', $one['type']);
        $this->assertSame('https://www.emich.edu/forager', $one['forager_url']);
        $this->assertSame(self::USERNAME_PREFIX . 'advisor_admin', $one['updated_by']);
        $this->assertSame($buildingId, $one['building_id']);
        $this->assertSame(self::NAME_PREFIX . ' Building', $one['building_name']);
        foreach (['programs', 'scholarships'] as $key) {
            $this->assertSame([], $one[$key]);
        }
        $this->assertArrayNotHasKey('buildings', $one);

        // A null buildingId clears the building.
        $this->json($client, 'PUT', '/api/advisors/' . $id, ['buildingId' => null]);
        $this->assertResponseStatusCodeSame(200);
        $cleared = json_decode($client->getResponse()->getContent(), true);
        $this->assertNull($cleared['buildingId']);
        $this->assertNull($cleared['buildingName']);

        // DELETE (unused) -> 204, then 404
        $client->request('DELETE', '/api/advisors/' . $id);
        $this->assertResponseStatusCodeSame(204);
        $client->request('GET', '/api/advisors/' . $id);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAdvisorInUseCannotBeDeleted(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'advisor_inuse'));

        $advisorId = $this->createOrganization($client, self::NAME_PREFIX . ' In Use');
        $otherId = $this->createOrganization($client, self::NAME_PREFIX . ' Other');

        // A scholarship can have several advisors.
        $this->json($client, 'POST', '/api/scholarships/', $this->scholarshipPayload(['advisorIds' => [$advisorId, $otherId]]));
        $this->assertResponseStatusCodeSame(201);
        $scholarship = json_decode($client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing([$advisorId, $otherId], $scholarship['advisorIds']);
        $this->assertEqualsCanonicalizing([self::NAME_PREFIX . ' In Use', self::NAME_PREFIX . ' Other'], $scholarship['advisorNames']);

        $client->request('DELETE', '/api/advisors/' . $advisorId);
        $this->assertResponseStatusCodeSame(409);
        $conflict = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('in_use', $conflict['error']);
        $this->assertStringContainsString('1 scholarship', $conflict['message']);

        $client->request('GET', '/api/advisors/' . $advisorId);
        $one = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $one['scholarship_count']);
        $this->assertSame(self::NAME_PREFIX . ' Scholarship', $one['scholarships'][0]['title']);

        // Removing one advisor frees only that advisor.
        $this->json($client, 'PUT', '/api/scholarships/' . $scholarship['id'], ['advisorIds' => [$otherId]]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([$otherId], json_decode($client->getResponse()->getContent(), true)['advisorIds']);
        $client->request('DELETE', '/api/advisors/' . $advisorId);
        $this->assertResponseStatusCodeSame(204);
        $client->request('DELETE', '/api/advisors/' . $otherId);
        $this->assertResponseStatusCodeSame(409);

        // Deleting the scholarship drops its links, which frees the other advisor.
        $client->request('DELETE', '/api/scholarships/' . $scholarship['id']);
        $this->assertSame(204, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $client->request('DELETE', '/api/advisors/' . $otherId);
        $this->assertResponseStatusCodeSame(204);
    }

    /**
     * Advisor editors can link programs and scholarships from the advisor, without access to
     * those apps, and the records see the same links.
     */
    public function testProgramsAndScholarshipsCanBeLinkedFromTheAdvisor(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'links_admin'));

        $programId = $this->createRecord($client, '/api/programs/', $this->programPayload($em));
        $scholarshipId = $this->createRecord($client, '/api/scholarships/', $this->scholarshipPayload([]));
        $otherScholarshipId = $this->createRecord($client, '/api/scholarships/', $this->scholarshipPayload(['title' => self::NAME_PREFIX . ' Other Scholarship', 'active' => false]));

        // An editor with no programs or scholarship role.
        $client->loginUser(UserFactory::withRoles(['ROLE_ADVISORS_EDIT'], self::USERNAME_PREFIX . 'links_editor'));

        // The add searches' options: every record, in the usage lists' row shape.
        $client->request('GET', '/api/advisors/programs');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(
            ['id' => $programId, 'full_name' => self::NAME_PREFIX . ' Program Full', 'catalog' => 'undergraduate', 'active' => true],
            current(array_filter(json_decode($client->getResponse()->getContent(), true), fn (array $r) => $r['id'] === $programId))
        );
        $client->request('GET', '/api/advisors/scholarships');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(
            ['id' => $otherScholarshipId, 'title' => self::NAME_PREFIX . ' Other Scholarship', 'active' => false],
            current(array_filter(json_decode($client->getResponse()->getContent(), true), fn (array $r) => $r['id'] === $otherScholarshipId))
        );

        // Create already linked. Duplicates and blanks are dropped.
        $this->json($client, 'POST', '/api/advisors', [
            'type' => 'Organization',
            'organization' => self::NAME_PREFIX . ' Linked',
            'programIds' => [$programId, (string) $programId, null, ''],
            'scholarshipIds' => [$scholarshipId],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $advisorId = json_decode($client->getResponse()->getContent(), true)['id'];
        $this->assertLinks($client, $advisorId, [$programId], [$scholarshipId]);

        // The records see the link too.
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'links_admin2'));
        $client->request('GET', '/api/scholarships/' . $scholarshipId);
        $this->assertSame([$advisorId], json_decode($client->getResponse()->getContent(), true)['advisorIds']);
        $client->request('GET', '/api/programs/' . $programId);
        $this->assertSame((string) $advisorId, json_decode($client->getResponse()->getContent(), true)['advisor_ids']);

        // The edit role can remove links. A missing key leaves that kind alone; [] clears it.
        // A links-only change still records who made it, on the advisor and the scholarship.
        $client->loginUser(UserFactory::withRoles(['ROLE_ADVISORS_EDIT'], self::USERNAME_PREFIX . 'links_editor2'));
        $this->json($client, 'PUT', '/api/advisors/' . $advisorId, ['scholarshipIds' => []]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(self::USERNAME_PREFIX . 'links_editor2', json_decode($client->getResponse()->getContent(), true)['updatedBy']);
        $one = $this->assertLinks($client, $advisorId, [$programId], []);
        $this->assertSame(self::USERNAME_PREFIX . 'links_editor2', $one['updated_by']);
        $this->assertSame(
            self::USERNAME_PREFIX . 'links_editor2',
            $em->getConnection()->fetchOne('SELECT updated_by FROM scholarships_scholarship WHERE id = ?', [$scholarshipId])
        );

        // Sending the same lists again changes nothing, so the history stays.
        $client->loginUser(UserFactory::withRoles(['ROLE_ADVISORS_EDIT'], self::USERNAME_PREFIX . 'links_editor3'));
        $this->json($client, 'PUT', '/api/advisors/' . $advisorId, ['programIds' => [$programId], 'scholarshipIds' => []]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(self::USERNAME_PREFIX . 'links_editor2', $this->assertLinks($client, $advisorId, [$programId], [])['updated_by']);

        $this->json($client, 'PUT', '/api/advisors/' . $advisorId, ['programIds' => [], 'scholarshipIds' => [$otherScholarshipId, $scholarshipId]]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertLinks($client, $advisorId, [], [$otherScholarshipId, $scholarshipId]);

        // Unknown, non-numeric or boolean ids are refused with a message, and nothing is saved.
        foreach ([[999999999], ['abc'], [true]] as $ids) {
            $this->json($client, 'PUT', '/api/advisors/' . $advisorId, ['organization' => self::NAME_PREFIX . ' Renamed', 'programIds' => $ids]);
            $this->assertSame(422, $client->getResponse()->getStatusCode(), json_encode($ids));
            $this->assertSame('That program no longer exists.', json_decode($client->getResponse()->getContent(), true)['violations'][0]['title']);
        }
        $this->json($client, 'PUT', '/api/advisors/' . $advisorId, ['scholarshipIds' => [$scholarshipId, 999999999]]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('That scholarship no longer exists.', json_decode($client->getResponse()->getContent(), true)['violations'][0]['title']);

        $one = $this->assertLinks($client, $advisorId, [], [$otherScholarshipId, $scholarshipId]);
        $this->assertSame(self::NAME_PREFIX . ' Linked', $one['display_name']);

        // A view-only user can't change links.
        $client->loginUser(UserFactory::withRoles(['ROLE_ADVISORS_VIEW'], self::USERNAME_PREFIX . 'links_viewer'));
        $this->json($client, 'PUT', '/api/advisors/' . $advisorId, ['scholarshipIds' => []]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnknownAdvisorIsRejected(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'advisor_unknown'));

        $this->json($client, 'POST', '/api/scholarships/', $this->scholarshipPayload(['advisorIds' => [999999999]]));
        $this->assertResponseStatusCodeSame(422);
        $this->json($client, 'POST', '/api/scholarships/', $this->scholarshipPayload(['advisorIds' => ['abc']]));
        $this->assertResponseStatusCodeSame(422);
        // true isn't advisor #1.
        $this->json($client, 'POST', '/api/scholarships/', $this->scholarshipPayload(['advisorIds' => [true]]));
        $this->assertResponseStatusCodeSame(422);
        $this->json($client, 'POST', '/api/programs/', $this->programPayload($em) + ['advisor_ids' => [true]]);
        $this->assertResponseStatusCodeSame(422);

        // The same payload without the bad id saves, so the 422s above were the advisor.
        $this->json($client, 'POST', '/api/scholarships/', $this->scholarshipPayload(['advisorIds' => []]));
        $this->assertResponseStatusCodeSame(201);
    }

    public function testOptionsRouteIsOpenToProgramAndScholarshipUsers(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'advisor_seed'));
        $advisorId = $this->createOrganization($client, self::NAME_PREFIX . ' Option');

        // Program and scholarship users, viewers included (their read-only cards show the
        // names), can read the options, but not the advisors API.
        foreach (['ROLE_PROGRAMS_VIEW', 'ROLE_SCHOLARSHIP_VIEW', 'ROLE_PROGRAMS_CREATE', 'ROLE_SCHOLARSHIP_ADMIN'] as $index => $role) {
            $client->loginUser(UserFactory::withRoles([$role], self::USERNAME_PREFIX . 'advisor_editor' . $index));

            $client->request('GET', '/api/ic/advisors/options');
            $this->assertSame(200, $client->getResponse()->getStatusCode(), $role);
            $options = json_decode($client->getResponse()->getContent(), true);
            $option = current(array_filter($options, fn (array $row) => $row['id'] === $advisorId));
            $this->assertSame(['id', 'displayName', 'type', 'email'], array_keys($option));
            $this->assertSame(self::NAME_PREFIX . ' Option', $option['displayName']);

            $client->request('GET', '/api/advisors');
            $this->assertSame(403, $client->getResponse()->getStatusCode(), $role);
        }

        // Other modules don't show advisor cards (buildings no longer have advisors).
        foreach (['ROLE_ADVISORS_VIEW', 'ROLE_MAP_CREATE', 'ROLE_MAP_ADMIN'] as $index => $role) {
            $client->loginUser(UserFactory::withRoles([$role], self::USERNAME_PREFIX . 'advisor_noopts' . $index));
            $client->request('GET', '/api/ic/advisors/options');
            $this->assertSame(403, $client->getResponse()->getStatusCode(), $role);
        }
    }

    /**
     * @return array<string, array{0: string[], 1: bool, 2: bool, 3: bool}> roles => can [read, write, delete]
     */
    public static function roleMatrix(): array
    {
        return [
            'view' => [['ROLE_ADVISORS_VIEW'], true, false, false],
            'edit' => [['ROLE_ADVISORS_EDIT'], true, true, false],
            'delete' => [['ROLE_ADVISORS_DELETE'], true, true, true],
            'admin' => [['ROLE_ADVISORS_ADMIN'], true, true, true],
            'global admin alone' => [['ROLE_GLOBAL_ADMIN'], true, true, true],
            'other module admin' => [['ROLE_MAP_ADMIN'], false, false, false],
        ];
    }

    #[DataProvider('roleMatrix')]
    public function testRoleMatrix(array $roles, bool $canRead, bool $canWrite, bool $canDelete): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'seed_admin'));
        $seedId = $this->createOrganization($client, self::NAME_PREFIX . ' Seed');

        $client->loginUser(UserFactory::withRoles($roles, self::USERNAME_PREFIX . 'matrix_user'));

        $client->request('GET', '/api/advisors?page=1&limit=10');
        $this->assertSame($canRead ? 200 : 403, $client->getResponse()->getStatusCode(), 'list');
        $client->request('GET', '/api/advisors/' . $seedId);
        $this->assertSame($canRead ? 200 : 403, $client->getResponse()->getStatusCode(), 'get');
        foreach (['buildings', 'programs', 'scholarships'] as $options) {
            $client->request('GET', '/api/advisors/' . $options);
            $this->assertSame($canWrite ? 200 : 403, $client->getResponse()->getStatusCode(), $options . ' options');
        }

        $this->json($client, 'POST', '/api/advisors', ['type' => 'Organization', 'organization' => self::NAME_PREFIX . ' Made']);
        $this->assertSame($canWrite ? 201 : 403, $client->getResponse()->getStatusCode(), 'create');

        $this->json($client, 'PUT', '/api/advisors/' . $seedId, ['type' => 'Organization', 'organization' => self::NAME_PREFIX . ' Renamed']);
        $this->assertSame($canWrite ? 200 : 403, $client->getResponse()->getStatusCode(), 'update');

        $client->request('DELETE', '/api/advisors/' . $seedId);
        $this->assertSame($canDelete ? 204 : 403, $client->getResponse()->getStatusCode(), 'delete');
    }

    public function testDeleteRoleGetsConflictForAdvisorInUse(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'inuse_seed'));
        $advisorId = $this->createOrganization($client, self::NAME_PREFIX . ' Busy');
        $this->json($client, 'POST', '/api/scholarships/', $this->scholarshipPayload(['advisorIds' => [$advisorId]]));
        $this->assertResponseStatusCodeSame(201);

        $client->loginUser(UserFactory::withRoles(['ROLE_ADVISORS_DELETE'], self::USERNAME_PREFIX . 'inuse_deleter'));
        $client->request('DELETE', '/api/advisors/' . $advisorId);

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('in_use', json_decode($client->getResponse()->getContent(), true)['error']);
    }

    public function testOldAdminApiIsGone(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'old_api'));

        $client->request('GET', '/api/admin/advisors');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnauthenticatedIs401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/advisors');

        $this->assertResponseStatusCodeSame(401);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testOptionsRouteNeedsALogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/ic/advisors/options');
        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * legacy_programs is filled only by the legacy import SQL: the API shows it but never
     * writes it, and the public feeds don't publish it.
     */
    public function testLegacyProgramsIsReadOnly(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN'], self::USERNAME_PREFIX . 'legacy_admin'));
        $id = $this->createOrganization($client, self::NAME_PREFIX . ' Legacy Office');
        $em->getConnection()->executeStatement(
            'UPDATE ic_advisors SET legacy_programs = ? WHERE id = ?',
            ['Biology Minor, Chemistry [BS]', $id]
        );

        $client->request('GET', '/api/advisors/' . $id);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Biology Minor, Chemistry [BS]', json_decode($client->getResponse()->getContent(), true)['legacy_programs']);

        $this->json($client, 'PUT', '/api/advisors/' . $id, [
            'type' => 'Organization',
            'organization' => self::NAME_PREFIX . ' Legacy Office',
            'legacyPrograms' => 'changed',
            'legacy_programs' => 'changed',
        ]);
        $this->assertResponseStatusCodeSame(200);

        $client->request('GET', '/api/advisors/' . $id);
        $this->assertSame('Biology Minor, Chemistry [BS]', json_decode($client->getResponse()->getContent(), true)['legacy_programs']);

        $em->clear();
        $public = $em->getRepository(IcAdvisor::class)->find($id)->toPublicArray();
        $this->assertArrayNotHasKey('legacyPrograms', $public);
        $this->assertArrayNotHasKey('legacy_programs', $public);
    }

    private function scholarshipPayload(array $extra): array
    {
        return $extra + [
            'title' => self::NAME_PREFIX . ' Scholarship',
            'overview' => '<p>Overview</p>',
            'url' => 'https://www.emich.edu/zz',
            'description' => '<p>Description</p>',
            'contact' => '<p>Free-text contact</p>',
            'active' => true,
        ];
    }

    /**
     * Reads the advisor and checks its program and scholarship lists (ids in list order) and
     * counts. Returns the advisor.
     *
     * @param int[] $programIds
     * @param int[] $scholarshipIds
     */
    private function assertLinks(KernelBrowser $client, int $advisorId, array $programIds, array $scholarshipIds): array
    {
        $client->request('GET', '/api/advisors/' . $advisorId);
        $this->assertResponseStatusCodeSame(200);
        $one = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($programIds, array_column($one['programs'], 'id'), 'programs');
        $this->assertSame($scholarshipIds, array_column($one['scholarships'], 'id'), 'scholarships');
        $this->assertSame(count($programIds), $one['program_count']);
        $this->assertSame(count($scholarshipIds), $one['scholarship_count']);

        return $one;
    }

    /**
     * POSTs a program or scholarship that must save; returns its id.
     */
    private function createRecord(KernelBrowser $client, string $url, array $data): int
    {
        $this->json($client, 'POST', $url, $data);
        $this->assertSame(201, $client->getResponse()->getStatusCode(), $url . ': ' . substr($client->getResponse()->getContent(), 0, 500));
        return (int) json_decode($client->getResponse()->getContent(), true)['id'];
    }

    private function createBuilding(KernelBrowser $client, EntityManagerInterface $em): int
    {
        $this->json($client, 'POST', '/api/mapitems', [
            'itemType' => 'building',
            'name' => self::NAME_PREFIX . ' Building',
            'description' => 'A building',
            'hours' => '',
            'address' => '1 Test St',
            'alias' => '',
            'admissionsTour' => 0,
            'latitudeIllustration' => 0,
            'longitudeIllustration' => 0,
            'latitudeSatellite' => 0,
            'longitudeSatellite' => 0,
            'buildingType' => ['id' => (int) $em->getConnection()->fetchOne('SELECT MIN(id) FROM map_building_type')],
            'bathrooms' => [],
            'diningOptions' => [],
            'emergencyDevices' => [],
            'exhibits' => [],
            'services' => [],
            'dispensers' => [],
        ]);
        $this->assertSame(201, $client->getResponse()->getStatusCode(), substr($client->getResponse()->getContent(), 0, 500));
        return (int) json_decode($client->getResponse()->getContent(), true)['id'];
    }

    private function createOrganization(KernelBrowser $client, string $name): int
    {
        $this->json($client, 'POST', '/api/advisors', ['type' => 'Organization', 'organization' => $name]);
        $this->assertResponseStatusCodeSame(201);
        return json_decode($client->getResponse()->getContent(), true)['id'];
    }

    private function json(KernelBrowser $client, string $method, string $url, array $data): void
    {
        $client->request($method, $url, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }
}
