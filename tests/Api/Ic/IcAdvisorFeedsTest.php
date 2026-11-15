<?php

namespace App\Tests\Api\Ic;

use App\Entity\Ic\IcAdvisor;
use App\Entity\Ic\IcAdvisorType;
use App\Entity\Map\MapBuilding;
use App\Entity\Scholarship\Scholarship;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Advisors set through each record's own API reach that record's public feed as the
 * icAdvisorsInfo list: name, email, phone and url only, sorted by name, [] for none. Test rows
 * are ZZ-prefixed and temp users zz_ictest_-prefixed; tearDown removes both.
 */
class IcAdvisorFeedsTest extends WebTestCase
{
    private const NAME_PREFIX = 'ZZ Ic Feed Test';
    private const USERNAME_PREFIX = 'zz_ictest_';

    /** Fields of the advisor that must never reach a public feed. */
    private const PRIVATE_VALUES = ['ZZPRIVATEROOM', 'ZZPRIVATEAREA', 'zzprivateforager', 'zzprivateimage'];

    /** The advisor with every field set. Sorts after SECOND_INFO by display name ("Last, Jane" after "Last, Adam"). */
    private const EXPECTED_INFO = [
        'name' => 'Jane ' . self::NAME_PREFIX,
        'email' => 'zz.feed@emich.edu',
        'phone' => '734-487-1234',
        'url' => 'https://www.emich.edu/zz-feed',
    ];

    /** A second advisor with only the required fields. */
    private const SECOND_INFO = [
        'name' => 'Adam ' . self::NAME_PREFIX,
        'email' => null,
        'phone' => null,
        'url' => null,
    ];

    public function testAdvisorsReachEveryPublicFeed(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em));

        $advisorId = $this->createAdvisor($client);
        $secondId = $this->createAdvisor($client, 'Adam', false);
        $advisorIds = [$advisorId, $secondId];

        $scholarshipId = $this->create($client, 'POST', '/api/scholarships/', $this->scholarshipPayload() + ['advisorIds' => $advisorIds]);
        $plainScholarshipId = $this->create($client, 'POST', '/api/scholarships/', $this->scholarshipPayload(' Plain'));
        $buildingId = $this->create($client, 'POST', '/api/mapitems', $this->buildingPayload($em) + ['advisorIds' => $advisorIds]);
        $programId = $this->create($client, 'POST', '/api/programs/', $this->programPayload($em) + ['advisor_ids' => $advisorIds]);

        // The public routes need no login.
        $client->getCookieJar()->clear();
        static::getContainer()->get('security.token_storage')->setToken(null);

        // Scholarships keep publishing the free-text contact beside the new key.
        $expected = [self::SECOND_INFO, self::EXPECTED_INFO];
        $scholarshipRows = [
            $this->rowById($this->publicJson($client, '/api/external/scholarships/all'), $scholarshipId),
            $this->rowById($this->publicJson($client, '/api/external/scholarships/search?title=' . urlencode(self::NAME_PREFIX)), $scholarshipId),
            $this->publicJson($client, '/api/external/scholarships/' . $scholarshipId),
        ];
        foreach ($scholarshipRows as $row) {
            $this->assertSame($expected, $row['icAdvisorsInfo']);
            $this->assertSame('<p>Free-text contact</p>', $row['contact']);
            $this->assertNoAdvisorIds($row);
        }
        $this->assertSame([], $this->rowById($this->publicJson($client, '/api/external/scholarships/all'), $plainScholarshipId)['icAdvisorsInfo']);

        $building = $this->rowById($this->publicJson($client, '/api/external/mapitems'), $buildingId);
        $this->assertSame($expected, $building['icAdvisorsInfo']);
        $this->assertNoAdvisorIds($building);

        $program = $this->rowById($this->publicJson($client, '/api/external/programs/programs'), $programId);
        $this->assertSame($expected, $program['icAdvisorsInfo']);
        $this->assertNoAdvisorIds($program);

        $search = $this->publicJson($client, '/api/external/programs/search?program=' . urlencode(self::NAME_PREFIX));
        $this->assertCount(1, $search['programs']);
        $this->assertSame($expected, $search['programs'][0]['icAdvisorsInfo']);
        $this->assertNoAdvisorIds($search['programs'][0]);
    }

    public function testAdvisorsCanBeReadBackAndChanged(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em));

        $advisorId = $this->createAdvisor($client);
        $secondId = $this->createAdvisor($client, 'Adam', false);
        $names = [self::NAME_PREFIX . ', Jane', self::NAME_PREFIX . ', Adam'];

        // Scholarship
        $scholarshipId = $this->create($client, 'POST', '/api/scholarships/', $this->scholarshipPayload() + ['advisorIds' => [$advisorId, $secondId]]);
        $client->request('GET', '/api/scholarships/' . $scholarshipId);
        $scholarship = json_decode($client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing([$advisorId, $secondId], $scholarship['advisorIds']);
        $this->assertEqualsCanonicalizing($names, $scholarship['advisorNames']);

        // The free-text contact stays required when advisors are picked.
        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['contact' => '']);
        $this->assertResponseStatusCodeSame(422);
        $em->clear();

        // Omitted key leaves the advisors alone; duplicates and blanks are dropped.
        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['title' => self::NAME_PREFIX . ' Scholarship']);
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(2, json_decode($client->getResponse()->getContent(), true)['advisorIds']);
        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['advisorIds' => [$advisorId, $advisorId, null, '']]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([$advisorId], json_decode($client->getResponse()->getContent(), true)['advisorIds']);
        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['advisorIds' => []]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true)['advisorIds']);

        // Building (other map item types never have advisors)
        $buildingId = $this->create($client, 'POST', '/api/mapitems', $this->buildingPayload($em) + ['advisorIds' => [$advisorId, $secondId]]);
        $client->request('GET', '/api/mapitems/' . $buildingId);
        $building = json_decode($client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing([$advisorId, $secondId], $building['advisorIds']);
        $this->assertEqualsCanonicalizing($names, $building['advisorNames']);

        $this->sendJson($client, 'PUT', '/api/mapitem', ['id' => $buildingId] + $this->buildingPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertCount(2, json_decode($client->getResponse()->getContent(), true)['advisorIds']);

        $this->sendJson($client, 'PUT', '/api/mapitem', ['id' => $buildingId, 'advisorIds' => [$secondId]] + $this->buildingPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([$secondId], json_decode($client->getResponse()->getContent(), true)['advisorIds']);

        $this->sendJson($client, 'PUT', '/api/mapitem', ['id' => $buildingId, 'advisorIds' => []] + $this->buildingPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true)['advisorIds']);

        // Program (raw SQL read: snake_case keys, ids as a comma-separated string)
        $programId = $this->create($client, 'POST', '/api/programs/', $this->programPayload($em) + ['advisor_ids' => [$advisorId, $secondId, $advisorId]]);
        $client->request('GET', '/api/programs/' . $programId);
        $program = json_decode($client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing([$advisorId, $secondId], array_map('intval', explode(',', $program['advisor_ids'])));

        $this->sendJson($client, 'PUT', '/api/programs/', ['id' => $programId] + $this->programPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertCount(2, explode(',', json_decode($client->getResponse()->getContent(), true)['advisor_ids']));

        $this->sendJson($client, 'PUT', '/api/programs/', ['id' => $programId, 'advisor_ids' => [$secondId]] + $this->programPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame((string) $secondId, json_decode($client->getResponse()->getContent(), true)['advisor_ids']);

        $this->sendJson($client, 'PUT', '/api/programs/', ['id' => $programId, 'advisor_ids' => []] + $this->programPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertNull(json_decode($client->getResponse()->getContent(), true)['advisor_ids']);

        // Unknown ids are refused on every save path.
        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['advisorIds' => [$advisorId, 999999999]]);
        $this->assertResponseStatusCodeSame(422);
        $em->clear();
        $this->sendJson($client, 'PUT', '/api/mapitem', ['id' => $buildingId, 'advisorIds' => [999999999]] + $this->buildingPayload($em));
        $this->assertResponseStatusCodeSame(422);
        $em->clear();
        $this->sendJson($client, 'PUT', '/api/programs/', ['id' => $programId, 'advisor_ids' => [999999999]] + $this->programPayload($em));
        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Deleting a program, scholarship or building drops its advisor links; the advisor stays.
     */
    public function testDeletingARecordDropsItsAdvisorLinks(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em));
        $conn = $em->getConnection();

        $advisorId = $this->createAdvisor($client);
        $programId = $this->create($client, 'POST', '/api/programs/', $this->programPayload($em) + ['advisor_ids' => [$advisorId]]);
        $buildingId = $this->create($client, 'POST', '/api/mapitems', $this->buildingPayload($em) + ['advisorIds' => [$advisorId]]);
        $this->assertSame(1, (int) $conn->fetchOne('SELECT COUNT(*) FROM program_advisor WHERE program_id = ?', [$programId]));
        $this->assertSame(1, (int) $conn->fetchOne('SELECT COUNT(*) FROM map_building_advisor WHERE building_id = ?', [$buildingId]));

        $client->request('DELETE', '/api/programs/' . $programId);
        $this->assertSame(0, (int) $conn->fetchOne('SELECT COUNT(*) FROM program_advisor WHERE program_id = ?', [$programId]));
        $client->request('DELETE', '/api/mapitems/' . $buildingId);
        $this->assertSame(0, (int) $conn->fetchOne('SELECT COUNT(*) FROM map_building_advisor WHERE building_id = ?', [$buildingId]));

        $this->assertSame(1, (int) $conn->fetchOne('SELECT COUNT(*) FROM ic_advisors WHERE id = ?', [$advisorId]));
    }

    /**
     * No current route serializes these entities without groups, but one that did must not
     * publish the whole advisor. Nothing is persisted.
     */
    public function testAdvisorIsNotSerializedWholeWithoutGroups(): void
    {
        static::bootKernel();
        $serializer = static::getContainer()->get('serializer');

        $advisor = (new IcAdvisor())
            ->setType(IcAdvisorType::ORGANIZATION)
            ->setOrganization(self::NAME_PREFIX)
            ->setRoom(self::PRIVATE_VALUES[0])
            ->setArea(self::PRIVATE_VALUES[1]);

        foreach ([new Scholarship(), new MapBuilding()] as $record) {
            $record->setIcAdvisors([$advisor]);
            $json = $serializer->serialize($record, 'json');
            $this->assertStringNotContainsString(self::PRIVATE_VALUES[0], $json, get_class($record));
            $this->assertStringNotContainsString(self::PRIVATE_VALUES[1], $json, get_class($record));
        }
    }

    /**
     * @param bool $full whether to set the public fields (EXPECTED_INFO) or only the name
     */
    private function createAdvisor(KernelBrowser $client, string $firstName = 'Jane', bool $full = true): int
    {
        return $this->create($client, 'POST', '/api/advisors', [
            'type' => 'Person',
            'lastName' => self::NAME_PREFIX,
            'firstName' => $firstName,
        ] + (!$full ? [] : [
            'email' => self::EXPECTED_INFO['email'],
            'phone' => self::EXPECTED_INFO['phone'],
            'url' => self::EXPECTED_INFO['url'],
        ]) + [
            'room' => self::PRIVATE_VALUES[0],
            'area' => self::PRIVATE_VALUES[1],
            'foragerUrl' => 'https://www.emich.edu/' . self::PRIVATE_VALUES[2],
            'imageUrl' => 'https://www.emich.edu/' . self::PRIVATE_VALUES[3],
        ]);
    }

    private function scholarshipPayload(string $suffix = ''): array
    {
        return [
            'title' => self::NAME_PREFIX . $suffix . ' Scholarship',
            'overview' => '<p>Overview</p>',
            'url' => 'https://www.emich.edu/zz',
            'description' => '<p>Description</p>',
            'contact' => '<p>Free-text contact</p>',
            'active' => true,
        ];
    }

    private function buildingPayload(EntityManagerInterface $em): array
    {
        return [
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
        ];
    }

    private function programPayload(EntityManagerInterface $em): array
    {
        $conn = $em->getConnection();

        return [
            'catalog' => 'undergraduate',
            'program' => self::NAME_PREFIX . ' Program',
            'full_name' => self::NAME_PREFIX . ' Program Full',
            'degree_id' => (int) $conn->fetchOne('SELECT MIN(degree_id) FROM program_programs'),
            'type_id' => (int) $conn->fetchOne('SELECT MIN(type_id) FROM program_programs'),
            'is_active' => true,
            'delivery_ids' => [(int) $conn->fetchOne('SELECT MIN(delivery_id) FROM program_delivery')],
            'college_ids' => [],
            'department_ids' => [],
            'keyword_ids' => [],
        ];
    }

    /**
     * POST/PUT that must succeed; returns the new record's id.
     */
    private function create(KernelBrowser $client, string $method, string $url, array $data): int
    {
        $this->sendJson($client, $method, $url, $data);
        $this->assertSame(201, $client->getResponse()->getStatusCode(), $url . ': ' . substr($client->getResponse()->getContent(), 0, 500));
        return (int) json_decode($client->getResponse()->getContent(), true)['id'];
    }

    private function sendJson(KernelBrowser $client, string $method, string $url, array $data): void
    {
        $client->request($method, $url, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    /**
     * GETs a public route and checks the whole response for the advisor's private fields.
     */
    private function publicJson(KernelBrowser $client, string $url): array
    {
        $client->request('GET', $url);
        $this->assertSame(200, $client->getResponse()->getStatusCode(), $url);
        $body = $client->getResponse()->getContent();
        foreach (self::PRIVATE_VALUES as $private) {
            $this->assertStringNotContainsString($private, $body, $url);
        }
        return json_decode($body, true);
    }

    private function rowById(array $rows, int $id): array
    {
        $matches = array_values(array_filter($rows, fn (array $row) => (int) $row['id'] === $id));
        $this->assertCount(1, $matches, 'Row ' . $id . ' not found.');
        return $matches[0];
    }

    private function assertNoAdvisorIds(array $row): void
    {
        foreach (['advisorId', 'advisorIds', 'advisor_id', 'advisor_ids', 'advisorName', 'advisorNames', 'advisor_name', 'icAdvisor', 'icAdvisors', 'icAdvisorInfo'] as $key) {
            $this->assertArrayNotHasKey($key, $row);
        }
    }

    private function persistUser(EntityManagerInterface $em): User
    {
        $user = new User();
        $user->setUsername(self::USERNAME_PREFIX . 'advisor_feeds');
        $user->setEmail(self::USERNAME_PREFIX . 'advisor_feeds@test.local');
        $user->setPassword('not-used-by-loginUser');
        $user->setEnabled(1);
        $user->setRoles(['ROLE_GLOBAL_ADMIN']);
        $em->persist($user);
        $em->flush();
        return $user;
    }

    protected function tearDown(): void
    {
        if (static::$booted) {
            $conn = static::getContainer()->get('doctrine')->getManager()->getConnection();
            $like = self::NAME_PREFIX . '%';

            $programs = 'SELECT id FROM program_programs WHERE program LIKE ?';
            foreach (['program_delivery', 'program_college_link', 'program_inter_dept', 'program_keyword_links', 'program_websites'] as $table) {
                $conn->executeStatement("DELETE FROM $table WHERE program_id IN ($programs)", [$like]);
            }
            $conn->executeStatement('DELETE FROM program_programs WHERE program LIKE ?', [$like]);
            $conn->executeStatement('DELETE FROM scholarships_scholarship WHERE schlrshp_title LIKE ?', [$like]);
            $conn->executeStatement('DELETE FROM map_item WHERE name LIKE ?', [$like]);
            $conn->executeStatement('DELETE FROM ic_advisors WHERE last_name LIKE ?', [$like]);
            $conn->executeStatement('DELETE FROM `user` WHERE username LIKE ?', [self::USERNAME_PREFIX . '%']);
        }
        parent::tearDown();
    }
}
