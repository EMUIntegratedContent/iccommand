<?php

namespace App\Tests\Api\Ic;

use App\Entity\Ic\IcCollege;
use App\Entity\Ic\IcContact;
use App\Entity\Ic\IcContactType;
use App\Entity\Ic\IcDepartment;
use App\Entity\Map\MapBuilding;
use App\Entity\Scholarship\Scholarship;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * A contact set through each record's own API reaches that record's public feed as
 * icContactInfo: name, email, phone and url only. Test rows are ZZ-prefixed and temp users
 * zz_ictest_-prefixed; tearDown removes both.
 */
class IcContactFeedsTest extends WebTestCase
{
    private const NAME_PREFIX = 'ZZ Ic Feed Test';
    private const USERNAME_PREFIX = 'zz_ictest_';

    /** Fields of the contact that must never reach a public feed. */
    private const PRIVATE_VALUES = ['ZZPRIVATEROOM', 'ZZPRIVATEAREA', 'zzprivateforager', 'zzprivateimage'];

    private const EXPECTED_INFO = [
        'name' => 'Jane ' . self::NAME_PREFIX,
        'email' => 'zz.feed@emich.edu',
        'phone' => '734-487-1234',
        'url' => 'https://www.emich.edu/zz-feed',
    ];

    public function testContactReachesEveryPublicFeed(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em));

        $contactId = $this->createContact($client);

        $collegeId = $this->create($client, 'POST', '/api/admin/colleges', [
            'college' => self::NAME_PREFIX . ' College',
            'contactId' => $contactId,
        ]);
        $plainCollegeId = $this->create($client, 'POST', '/api/admin/colleges', ['college' => self::NAME_PREFIX . ' Plain College']);
        $departmentId = $this->create($client, 'POST', '/api/admin/departments', [
            'department' => self::NAME_PREFIX . ' Department',
            'collegeId' => $collegeId,
            'contactId' => $contactId,
        ]);
        $scholarshipId = $this->create($client, 'POST', '/api/scholarships/', [
            'title' => self::NAME_PREFIX . ' Scholarship',
            'overview' => '<p>Overview</p>',
            'url' => 'https://www.emich.edu/zz',
            'description' => '<p>Description</p>',
            'contact' => '<p>Free-text contact</p>',
            'active' => true,
            'contactId' => $contactId,
        ]);
        $buildingId = $this->create($client, 'POST', '/api/mapitems', $this->buildingPayload($em) + ['contactId' => $contactId]);
        $programId = $this->create($client, 'POST', '/api/programs/', $this->programPayload($em) + ['contact_id' => $contactId]);

        // The public routes need no login.
        $client->getCookieJar()->clear();
        static::getContainer()->get('security.token_storage')->setToken(null);

        $colleges = $this->publicJson($client, '/api/external/ic/colleges');
        $college = $this->rowById($colleges, $collegeId);
        $this->assertSame(['id', 'college', 'url', 'icContactInfo'], array_keys($college));
        $this->assertSame(self::EXPECTED_INFO, $college['icContactInfo']);
        $this->assertNull($this->rowById($colleges, $plainCollegeId)['icContactInfo']);

        $department = $this->rowById($this->publicJson($client, '/api/external/ic/departments'), $departmentId);
        $this->assertSame(['id', 'department', 'college_id', 'icContactInfo'], array_keys($department));
        $this->assertSame(self::EXPECTED_INFO, $department['icContactInfo']);

        // Scholarships keep publishing the free-text contact beside the new key.
        $scholarshipRows = [
            $this->rowById($this->publicJson($client, '/api/external/scholarships/all'), $scholarshipId),
            $this->rowById($this->publicJson($client, '/api/external/scholarships/search?title=' . urlencode(self::NAME_PREFIX)), $scholarshipId),
            $this->publicJson($client, '/api/external/scholarships/' . $scholarshipId),
        ];
        foreach ($scholarshipRows as $row) {
            $this->assertSame(self::EXPECTED_INFO, $row['icContactInfo']);
            $this->assertSame('<p>Free-text contact</p>', $row['contact']);
            $this->assertNoContactId($row);
        }

        $building = $this->rowById($this->publicJson($client, '/api/external/mapitems'), $buildingId);
        $this->assertSame(self::EXPECTED_INFO, $building['icContactInfo']);
        $this->assertNoContactId($building);

        $program = $this->rowById($this->publicJson($client, '/api/external/programs/programs'), $programId);
        $this->assertSame(self::EXPECTED_INFO, $program['icContactInfo']);
        $this->assertNoContactId($program);

        $search = $this->publicJson($client, '/api/external/programs/search?program=' . urlencode(self::NAME_PREFIX));
        $this->assertCount(1, $search['programs']);
        $this->assertSame(self::EXPECTED_INFO, $search['programs'][0]['icContactInfo']);
        $this->assertNoContactId($search['programs'][0]);
    }

    public function testContactCanBeReadBackAndCleared(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        $client->loginUser($this->persistUser($em));

        $contactId = $this->createContact($client);
        $displayName = self::NAME_PREFIX . ', Jane';

        // Scholarship
        $scholarshipId = $this->create($client, 'POST', '/api/scholarships/', [
            'title' => self::NAME_PREFIX . ' Scholarship',
            'overview' => '<p>Overview</p>',
            'url' => 'https://www.emich.edu/zz',
            'description' => '<p>Description</p>',
            'contact' => '<p>Free-text contact</p>',
            'contactId' => $contactId,
        ]);
        $client->request('GET', '/api/scholarships/' . $scholarshipId);
        $scholarship = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($contactId, $scholarship['contactId']);
        $this->assertSame($displayName, $scholarship['contactName']);

        // The free-text contact stays required when a contact is picked.
        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['contact' => '']);
        $this->assertResponseStatusCodeSame(422);
        $em->clear();

        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['contactId' => null, 'contact' => '<p>Free-text contact</p>']);
        $this->assertResponseStatusCodeSame(200);
        $this->assertNull(json_decode($client->getResponse()->getContent(), true)['contactId']);

        // A map item saved without the key keeps its contact (other item types never have one).
        $buildingId = $this->create($client, 'POST', '/api/mapitems', $this->buildingPayload($em) + ['contactId' => $contactId]);
        $client->request('GET', '/api/mapitems/' . $buildingId);
        $building = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($contactId, $building['contactId']);
        $this->assertSame($displayName, $building['contactName']);

        $this->sendJson($client, 'PUT', '/api/mapitem', ['id' => $buildingId] + $this->buildingPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame($contactId, json_decode($client->getResponse()->getContent(), true)['contactId']);

        $this->sendJson($client, 'PUT', '/api/mapitem', ['id' => $buildingId, 'contactId' => null] + $this->buildingPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertNull(json_decode($client->getResponse()->getContent(), true)['contactId']);

        // Program (raw SQL read: snake_case keys)
        $programId = $this->create($client, 'POST', '/api/programs/', $this->programPayload($em) + ['contact_id' => $contactId]);
        $client->request('GET', '/api/programs/' . $programId);
        $program = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($contactId, (int) $program['contact_id']);
        $this->assertSame($displayName, $program['contact_name']);

        $this->sendJson($client, 'PUT', '/api/programs/', ['id' => $programId, 'contact_id' => null] + $this->programPayload($em));
        $this->assertResponseStatusCodeSame(201);
        $this->assertNull(json_decode($client->getResponse()->getContent(), true)['contact_id']);

        // Unknown ids are refused on every save path.
        $this->sendJson($client, 'PUT', '/api/scholarships/' . $scholarshipId, ['contactId' => 999999999]);
        $this->assertResponseStatusCodeSame(422);
        $em->clear();
        $this->sendJson($client, 'PUT', '/api/mapitem', ['id' => $buildingId, 'contactId' => 999999999] + $this->buildingPayload($em));
        $this->assertResponseStatusCodeSame(422);
        $em->clear();
        $this->sendJson($client, 'PUT', '/api/programs/', ['id' => $programId, 'contact_id' => 999999999] + $this->programPayload($em));
        $this->assertResponseStatusCodeSame(422);
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

        foreach ([new IcCollege(), new IcDepartment(), new Scholarship(), new MapBuilding()] as $record) {
            $record->setIcContact($contact);
            $json = $serializer->serialize($record, 'json');
            $this->assertStringNotContainsString(self::PRIVATE_VALUES[0], $json, get_class($record));
            $this->assertStringNotContainsString(self::PRIVATE_VALUES[1], $json, get_class($record));
        }
    }

    private function createContact(KernelBrowser $client): int
    {
        return $this->create($client, 'POST', '/api/admin/contacts', [
            'type' => 'Person',
            'lastName' => self::NAME_PREFIX,
            'firstName' => 'Jane',
            'email' => self::EXPECTED_INFO['email'],
            'phone' => self::EXPECTED_INFO['phone'],
            'url' => self::EXPECTED_INFO['url'],
            'room' => self::PRIVATE_VALUES[0],
            'area' => self::PRIVATE_VALUES[1],
            'foragerUrl' => 'https://www.emich.edu/' . self::PRIVATE_VALUES[2],
            'imageUrl' => 'https://www.emich.edu/' . self::PRIVATE_VALUES[3],
        ]);
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

    private function assertNoContactId(array $row): void
    {
        foreach (['contactId', 'contact_id', 'contactName', 'contact_name', 'icContact'] as $key) {
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
            $conn->executeStatement('DELETE FROM ic_departments WHERE department LIKE ?', [$like]);
            $conn->executeStatement('DELETE FROM ic_colleges WHERE college LIKE ?', [$like]);
            $conn->executeStatement('DELETE FROM ic_contacts WHERE last_name LIKE ?', [$like]);
            $conn->executeStatement('DELETE FROM `user` WHERE username LIKE ?', [self::USERNAME_PREFIX . '%']);
        }
        parent::tearDown();
    }
}
