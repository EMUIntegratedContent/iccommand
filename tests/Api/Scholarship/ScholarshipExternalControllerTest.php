<?php

namespace App\Tests\Api\Scholarship;

use App\Entity\Scholarship\ScholarshipKeyword;
use App\Entity\Scholarship\ScholarshipOrganization;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The public option lists (/api/external/scholarships/{options,keyword-options,organization-options})
 * answer without a login and return the same data as their /api/scholarships/* counterparts.
 * The list tests seed one row each (rolled back).
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
        $this->persist((new ScholarshipKeyword())->setKeyword('ZZ Test Keyword'));

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
        $this->persist((new ScholarshipOrganization())->setOrganization('ZZ Test Organization'));

        $client->request('GET', '/api/external/scholarships/organization-options');
        $this->assertResponseIsSuccessful();
        $organizations = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($organizations);
        $this->assertNotEmpty($organizations);
        $this->assertSame(['id', 'organization'], array_keys($organizations[0]));
    }

    private function persist(object $entity): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($entity);
        $em->flush();
    }
}
