<?php

namespace App\Tests\Api\Scholarship;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The public option lists (/api/external/scholarships/{options,keyword-options,organization-options})
 * answer without a login and return the same data as their /api/scholarships/* counterparts.
 * Read-only, so nothing to clean up.
 */
class ScholarshipExternalControllerTest extends WebTestCase
{
    public function testOptionsArePublic(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/external/scholarships/options');
        $this->assertResponseIsSuccessful();
        $options = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['gender', 'ethnicity', 'classStanding', 'housing', 'transfer', 'state'], array_keys($options));
        $this->assertContains('MI', $options['state']);
    }

    public function testKeywordOptionsArePublic(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/external/scholarships/keyword-options');
        $this->assertResponseIsSuccessful();
        $keywords = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($keywords);
        $this->assertNotEmpty($keywords);
        $this->assertSame(['id', 'keyword'], array_keys($keywords[0]));
    }

    public function testOrganizationOptionsArePublic(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/external/scholarships/organization-options');
        $this->assertResponseIsSuccessful();
        $organizations = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($organizations);
        $this->assertNotEmpty($organizations);
        $this->assertSame(['id', 'organization'], array_keys($organizations[0]));
    }
}
