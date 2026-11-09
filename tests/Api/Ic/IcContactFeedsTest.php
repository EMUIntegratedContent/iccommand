<?php

namespace App\Tests\Api\Ic;

use App\Entity\Ic\IcContact;
use App\Entity\Ic\IcContactType;
use App\Entity\Map\MapBuilding;
use App\Entity\Scholarship\Scholarship;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Contacts set through each record's own API reach that record's public feed as the
 * icContactsInfo list: name, email, phone and url only, sorted by name, [] for none. Test rows
 * are ZZ-prefixed and temp users zz_ictest_-prefixed; tearDown removes both.
 */
class IcContactFeedsTest extends WebTestCase
{
    private const NAME_PREFIX = 'ZZ Ic Feed Test';
    private const USERNAME_PREFIX = 'zz_ictest_';

    /** Fields of the contact that must never reach a public feed. */
    private const PRIVATE_VALUES = ['ZZPRIVATEROOM', 'ZZPRIVATEAREA', 'zzprivateforager', 'zzprivateimage'];

    /** The contact with every field set. Sorts after SECOND_INFO by display name ("Last, Jane" after "Last, Adam"). */
    private const EXPECTED_INFO = [
        'name' => 'Jane ' . self::NAME_PREFIX,
        'email' => 'zz.feed@emich.edu',
        'phone' => '734-487-1234',
        'url' => 'https://www.emich.edu/zz-feed',
    ];

    /** A second contact with only the required fields. */
    private const SECOND_INFO = [
        'name' => 'Adam ' . self::NAME_PREFIX,
        'email' => null,
        'phone' => null,
        'url' => null,
    ];

    public function testContactsReachEveryPublicFeed(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em));

        $contactId = $this->createContact($client);
        $secondId = $this->createContact($client, 'Adam', false);
        $contactIds = [$contactId, $secondId];

        $scholarshipId = $this->create($client, 'POST', '/api/scholarships/', $this->scholarshipPayload() + ['contactIds' => $contactIds]);
        $plainScholarshipId = $this->create($client, 'POST', '/api/scholarships/', $this->scholarshipPayload(' Plain'));
        $buildingId = $this->create($client, 'POST', '/api/mapitems', $this->buildingPayload($em) + ['contactIds' => $contactIds]);
        $programId = $this->create($client, 'POST', '/api/programs/', $this->programPayload($em) + ['contact_ids' => $contactIds]);

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
            $this->assertSame($expected, $row['icContactsInfo']);
            $this->assertSame('<p>Free-text contact</p>', $row['contact']);
            $this->assertNoContactIds($row);
        }
        $this->assertSame([], $this->rowById($this->publicJson($client, '/api/external/scholarships/all'), $plainScholarshipId)['icContactsInfo']);

        $building = $this->rowById($this->publicJson($client, '/api/external/mapitems'), $buildingId);
        $this->assertSame($expected, $building['icContactsInfo']);
        $this->assertNoContactIds($building);

        $program = $this->rowById($this->publicJson($client, '/api/external/programs/programs'), $programId);
        $this->assertSame($expected, $program['icContactsInfo']);
        $this->assertNoContactIds($program);

        $search = $this->publicJson($client, '/api/external/programs/search?program=' . urlencode(self::NAME_PREFIX));
        $this->assertCount(1, $search['programs']);
        $this->assertSame($expected, $search['programs'][0]['icContactsInfo']);
        $this->assertNoContactIds($search['programs'][0]);
    }

    public function testContactsCanBeReadBackAndChanged(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em));

        $contactId = $this->createContact($client);
        $secondId = $this->createContact($client, 'Adam', false);
        $names = [self::NAME_PREFIX . ', Jane', self::NAME_PREFIX . ', Adam'];

        // Scholarship
        $scholarshipId = $this->create($client, 'POST', '/api/scholarships/', $this->scholarshipPayload() + ['contactIds' => [$contactId, $secondId]]);
        $client->request('GET', '/api/scholarships/' . $scholarshipId);
        $scholarship = json_decode($client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing([$contactId, $secondId], $scholarship['contactIds']);
        $this->assertEqualsCanonicalizing($names, $scholarship['contactNames']);

        // The free-text contact stays required when contacts are picked.
        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['contact' => '']);
        $this->assertResponseStatusCodeSame(422);
        $em->clear();

        // Omitted key leaves the contacts alone; duplicates and blanks are dropped.
        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['title' => self::NAME_PREFIX . ' Scholarship']);
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(2, json_decode($client->getResponse()->getContent(), true)['contactIds']);
        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['contactIds' => [$contactId, $contactId, null, '']]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([$contactId], json_decode($client->getResponse()->getContent(), true)['contactIds']);
        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['contactIds' => []]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true)['contactIds']);

        // Building (other map item types never have contacts)
        $buildingId = $this->create($client, 'POST', '/api/mapitems', $this->buildingPayload($em) + ['contactIds' => [$contactId, $secondId]]);
        $client->request('GET', '/api/mapitems/' . $buildingId);
        $building = json_decode($client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing([$contactId, $secondId], $building['contactIds']);
        $this->assertEqualsCanonicalizing($names, $building['contactNames']);

        $this->sendJson($client, 'PUT', '/api/mapitem', ['id' => $buildingId] + $this->buildingPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertCount(2, json_decode($client->getResponse()->getContent(), true)['contactIds']);

        $this->sendJson($client, 'PUT', '/api/mapitem', ['id' => $buildingId, 'contactIds' => [$secondId]] + $this->buildingPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([$secondId], json_decode($client->getResponse()->getContent(), true)['contactIds']);

        $this->sendJson($client, 'PUT', '/api/mapitem', ['id' => $buildingId, 'contactIds' => []] + $this->buildingPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true)['contactIds']);

        // Program (raw SQL read: snake_case keys, ids as a comma-separated string)
        $programId = $this->create($client, 'POST', '/api/programs/', $this->programPayload($em) + ['contact_ids' => [$contactId, $secondId, $contactId]]);
        $client->request('GET', '/api/programs/' . $programId);
        $program = json_decode($client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing([$contactId, $secondId], array_map('intval', explode(',', $program['contact_ids'])));

        $this->sendJson($client, 'PUT', '/api/programs/', ['id' => $programId] + $this->programPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertCount(2, explode(',', json_decode($client->getResponse()->getContent(), true)['contact_ids']));

        $this->sendJson($client, 'PUT', '/api/programs/', ['id' => $programId, 'contact_ids' => [$secondId]] + $this->programPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame((string) $secondId, json_decode($client->getResponse()->getContent(), true)['contact_ids']);

        $this->sendJson($client, 'PUT', '/api/programs/', ['id' => $programId, 'contact_ids' => []] + $this->programPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertNull(json_decode($client->getResponse()->getContent(), true)['contact_ids']);

        // Unknown ids are refused on every save path.
        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['contactIds' => [$contactId, 999999999]]);
        $this->assertResponseStatusCodeSame(422);
        $em->clear();
        $this->sendJson($client, 'PUT', '/api/mapitem', ['id' => $buildingId, 'contactIds' => [999999999]] + $this->buildingPayload($em));
        $this->assertResponseStatusCodeSame(422);
        $em->clear();
        $this->sendJson($client, 'PUT', '/api/programs/', ['id' => $programId, 'contact_ids' => [999999999]] + $this->programPayload($em));
        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Deleting a program, scholarship or building drops its contact links; the contact stays.
     */
    public function testDeletingARecordDropsItsContactLinks(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em));
        $conn = $em->getConnection();

        $contactId = $this->createContact($client);
        $programId = $this->create($client, 'POST', '/api/programs/', $this->programPayload($em) + ['contact_ids' => [$contactId]]);
        $buildingId = $this->create($client, 'POST', '/api/mapitems', $this->buildingPayload($em) + ['contactIds' => [$contactId]]);
        $this->assertSame(1, (int) $conn->fetchOne('SELECT COUNT(*) FROM program_contact WHERE program_id = ?', [$programId]));
        $this->assertSame(1, (int) $conn->fetchOne('SELECT COUNT(*) FROM map_building_contact WHERE building_id = ?', [$buildingId]));

        $client->request('DELETE', '/api/programs/' . $programId);
        $this->assertSame(0, (int) $conn->fetchOne('SELECT COUNT(*) FROM program_contact WHERE program_id = ?', [$programId]));
        $client->request('DELETE', '/api/mapitems/' . $buildingId);
        $this->assertSame(0, (int) $conn->fetchOne('SELECT COUNT(*) FROM map_building_contact WHERE building_id = ?', [$buildingId]));

        $this->assertSame(1, (int) $conn->fetchOne('SELECT COUNT(*) FROM ic_contacts WHERE id = ?', [$contactId]));
    }

    /**
     * No current route serializes these entities without groups, but one that did must not
     * publish the whole contact. Nothing is persisted.
     */
    public function testContactIsNotSerializedWholeWithoutGroups(): void
    {
        static::bootKernel();
        $serializer = static::getContainer()->get('serializer');

        $contact = (new IcContact())
            ->setType(IcContactType::ORGANIZATION)
            ->setOrganization(self::NAME_PREFIX)
            ->setRoom(self::PRIVATE_VALUES[0])
            ->setArea(self::PRIVATE_VALUES[1]);

        foreach ([new Scholarship(), new MapBuilding()] as $record) {
            $record->setIcContacts([$contact]);
            $json = $serializer->serialize($record, 'json');
            $this->assertStringNotContainsString(self::PRIVATE_VALUES[0], $json, get_class($record));
            $this->assertStringNotContainsString(self::PRIVATE_VALUES[1], $json, get_class($record));
        }
    }

    /**
     * @param bool $full whether to set the public fields (EXPECTED_INFO) or only the name
     */
    private function createContact(KernelBrowser $client, string $firstName = 'Jane', bool $full = true): int
    {
        return $this->create($client, 'POST', '/api/admin/contacts', [
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
     * GETs a public route and checks the whole response for the contact's private fields.
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

    private function assertNoContactIds(array $row): void
    {
        foreach (['contactId', 'contactIds', 'contact_id', 'contact_ids', 'contactName', 'contactNames', 'contact_name', 'icContact', 'icContacts', 'icContactInfo'] as $key) {
            $this->assertArrayNotHasKey($key, $row);
        }
    }

    private function persistUser(EntityManagerInterface $em): User
    {
        $user = new User();
        $user->setUsername(self::USERNAME_PREFIX . 'contact_feeds');
        $user->setEmail(self::USERNAME_PREFIX . 'contact_feeds@test.local');
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
            $conn->executeStatement('DELETE FROM ic_contacts WHERE last_name LIKE ?', [$like]);
            $conn->executeStatement('DELETE FROM `user` WHERE username LIKE ?', [self::USERNAME_PREFIX . '%']);
        }
        parent::tearDown();
    }
}
