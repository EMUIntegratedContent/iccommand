<?php

namespace App\Tests\Api\Admin;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Negative tests for PUT/GET /api/admin/users/{username} (#131, RoleAssignmentPolicy): nobody can
 * grant themselves or others more than their own admin scope allows, and the request is
 * refused before anything is saved.
 */
class UserRoleAssignmentTest extends WebTestCase
{
    public function testPlainUserCannotMakeThemselfGlobalAdmin(): void
    {
        $client = static::createClient();
        $user = UserFactory::withRoles([]);
        $client->loginUser($user);

        $this->putRoles($client, $user, ['ROLE_GLOBAL_ADMIN']);

        $this->assertResponseStatusCodeSame(403);
        $this->assertRolesUnchanged($user, []);
    }

    public function testPlainUserCannotChangeAnotherUser(): void
    {
        $client = static::createClient();
        $target = UserFactory::withRoles([]);
        $client->loginUser(UserFactory::withRoles([]));

        $this->putRoles($client, $target, ['ROLE_MAP_VIEW']);

        $this->assertResponseStatusCodeSame(403);
        $this->assertRolesUnchanged($target, []);
    }

    public function testModuleAdminCannotGrantAnotherModulesRole(): void
    {
        $client = static::createClient();
        $target = UserFactory::withRoles([]);
        $client->loginUser(UserFactory::withRoles(['ROLE_MAP_ADMIN']));

        $this->putRoles($client, $target, ['ROLE_REDIRECT_ADMIN']);
        $this->assertResponseStatusCodeSame(403);

        $this->putRoles($client, $target, ['ROLE_GLOBAL_ADMIN']);
        $this->assertResponseStatusCodeSame(403);

        $this->assertRolesUnchanged($target, []);
    }

    public function testModuleAdminCanGrantTheirOwnModulesRole(): void
    {
        $client = static::createClient();
        $target = UserFactory::withRoles([]);
        $client->loginUser(UserFactory::withRoles(['ROLE_MAP_ADMIN']));

        $this->putRoles($client, $target, ['ROLE_MAP_VIEW']);

        $this->assertResponseIsSuccessful();
        $this->assertRolesUnchanged($target, ['ROLE_MAP_VIEW']);
    }

    public function testModuleAdminCannotRevokeAnotherModulesRole(): void
    {
        $client = static::createClient();
        $target = UserFactory::withRoles(['ROLE_REDIRECT_USER']);
        $client->loginUser(UserFactory::withRoles(['ROLE_MAP_ADMIN']));

        $this->putRoles($client, $target, []);

        $this->assertResponseStatusCodeSame(403);
        $this->assertRolesUnchanged($target, ['ROLE_REDIRECT_USER']);
    }

    public function testGlobalAdminCannotGrantSuperAdmin(): void
    {
        $client = static::createClient();
        $target = UserFactory::withRoles([]);
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN']));

        $this->putRoles($client, $target, ['ROLE_GLOBAL_ADMIN_SUPER']);

        $this->assertResponseStatusCodeSame(403);
        $this->assertRolesUnchanged($target, []);
    }

    public function testUnknownRoleIsRejected(): void
    {
        $client = static::createClient();
        $target = UserFactory::withRoles([]);
        $client->loginUser(UserFactory::withRoles(['ROLE_GLOBAL_ADMIN']));

        $this->putRoles($client, $target, ['ROLE_DOES_NOT_EXIST']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertRolesUnchanged($target, []);
    }

    public function testOnlyGlobalAdminsCanDisableAccounts(): void
    {
        $client = static::createClient();
        $target = UserFactory::withRoles(['ROLE_MAP_VIEW']);
        $client->loginUser(UserFactory::withRoles(['ROLE_MAP_ADMIN']));

        $this->put($client, $target, ['roles' => $target->getRoles(), 'enabled' => false]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(1, (int) $this->reload($target)->getEnabled());
    }

    public function testUsersCanReadOnlyTheirOwnRecord(): void
    {
        $client = static::createClient();
        $user = UserFactory::withRoles([]);
        $other = UserFactory::withRoles([]);
        $client->loginUser($user);

        $client->request('GET', '/api/admin/users/'.$user->getUsername());
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/admin/users/'.$other->getUsername());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserListIsForGlobalAdminsOnly(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::withRoles(['ROLE_MAP_ADMIN']));

        $client->request('GET', '/api/admin/users');

        $this->assertResponseStatusCodeSame(403);
    }

    private function putRoles(KernelBrowser $client, User $target, array $roles): void
    {
        $this->put($client, $target, ['roles' => $roles]);
    }

    private function put(KernelBrowser $client, User $target, array $body): void
    {
        $client->request('PUT', '/api/admin/users/'.$target->getUsername(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($body));
    }

    private function reload(User $user): User
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em->getRepository(User::class)->find($user->getId());
    }

    /** @param string[] $roles the roles stored on the user, besides the implicit ROLE_USER */
    private function assertRolesUnchanged(User $user, array $roles): void
    {
        $this->assertEqualsCanonicalizing(array_merge($roles, ['ROLE_USER']), $this->reload($user)->getRoles());
    }
}
