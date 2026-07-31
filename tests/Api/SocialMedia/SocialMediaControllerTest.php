<?php

namespace App\Tests\Api\SocialMedia;

use App\Entity\SocialMedia;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * End-to-end CRUD test for the Social Media Links API.
 * Uses loginUser() so a security token is present (required for the Gedmo
 * Blameable created_by/updated_by columns, which are NOT NULL).
 */
class SocialMediaControllerTest extends WebTestCase
{
    private const TEST_NAME = 'ZZ Automated Test Entity';

    public function testCrudRoundTrip(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = $em->getRepository(User::class)->findOneBy(['email' => 'cpuzzuol@emich.edu']);
        $this->assertNotNull($user, 'Expected a seed user to log in with.');
        $client->loginUser($user);

        // CREATE
        $client->request('POST', '/api/social-media/', [
            'name' => self::TEST_NAME,
            'facebook_url' => 'https://facebook.com/zztest',
            'x_url' => '', // blank should be stored as null
        ]);
        $this->assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $created);
        $this->assertSame(self::TEST_NAME, $created['name']);
        $this->assertSame('https://facebook.com/zztest', $created['facebook_url']);
        $this->assertNull($created['x_url'], 'Blank URL should serialize as null.');
        $this->assertArrayHasKey('created', $created, 'Gedmo created field should serialize.');
        $this->assertArrayHasKey('createdBy', $created, 'Gedmo createdBy should serialize.');
        $id = $created['id'];

        // GET one
        $client->request('GET', '/api/social-media/' . $id);
        $this->assertResponseStatusCodeSame(200);

        // LIST — verify the {socialMedia, totalRows} shape the Vue list consumes
        $client->request('GET', '/api/social-media/list?page=1&limit=10&search=ZZ+Automated');
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $list = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('socialMedia', $list);
        $this->assertArrayHasKey('totalRows', $list);
        $this->assertGreaterThanOrEqual(1, (int) $list['totalRows']);

        // SEARCH typeahead
        $client->request('GET', '/api/social-media/search?searchterm=ZZ+Automated');
        $this->assertResponseStatusCodeSame(200);
        $search = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($search);

        // UPDATE (id in body, not the path)
        $client->request('PUT', '/api/social-media/', [
            'id' => $id,
            'name' => self::TEST_NAME . ' Renamed',
            'instagram_url' => 'https://instagram.com/zz',
        ]);
        $this->assertResponseStatusCodeSame(201);
        $updated = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(self::TEST_NAME . ' Renamed', $updated['name']);
        $this->assertSame('https://instagram.com/zz', $updated['instagram_url']);

        // VALIDATION: blank name -> 422
        $client->request('PUT', '/api/social-media/', ['id' => $id, 'name' => '']);
        $this->assertResponseStatusCodeSame(422);

        // VALIDATION: malformed URL -> 422
        $client->request('POST', '/api/social-media/', [
            'name' => 'ZZ Bad URL',
            'facebook_url' => 'not-a-valid-url',
        ]);
        $this->assertResponseStatusCodeSame(422);

        // DELETE
        $client->request('DELETE', '/api/social-media/' . $id);
        $this->assertResponseStatusCodeSame(204);

        // confirm gone
        $client->request('GET', '/api/social-media/' . $id);
        $this->assertResponseStatusCodeSame(404);
    }

    protected function tearDown(): void
    {
        // Remove any rows this test may have left behind (name prefixed with "ZZ ").
        if (static::$booted) {
            $em = static::getContainer()->get('doctrine')->getManager();
            $leftovers = $em->getRepository(SocialMedia::class)->createQueryBuilder('s')
                ->where('s.name LIKE :prefix')
                ->setParameter('prefix', 'ZZ %')
                ->getQuery()
                ->getResult();
            foreach ($leftovers as $row) {
                $em->remove($row);
            }
            if ($leftovers) {
                $em->flush();
            }
        }
        parent::tearDown();
    }
}
