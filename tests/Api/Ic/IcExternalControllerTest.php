<?php

namespace App\Tests\Api\Ic;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The public college and department lists (/api/external/ic/*) answer without a login and
 * return the same rows as the Scholarships dropdown routes. Read-only, so nothing to clean up.
 */
class IcExternalControllerTest extends WebTestCase
{
    public function testCollegesArePublic(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/external/ic/colleges');
        $this->assertResponseIsSuccessful();
        $colleges = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($colleges);
        $this->assertNotEmpty($colleges);
        $this->assertSame(['id', 'college', 'url'], array_keys($colleges[0]));
    }

    public function testDepartmentsArePublic(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/external/ic/departments');
        $this->assertResponseIsSuccessful();
        $departments = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($departments);
        $this->assertNotEmpty($departments);
        $this->assertSame(['id', 'department', 'college_id'], array_keys($departments[0]));
    }
}
