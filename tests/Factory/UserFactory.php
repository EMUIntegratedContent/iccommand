<?php

namespace App\Tests\Factory;

use App\Entity\User;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Enabled local users for tests. Log in with $client->loginUser(UserFactory::withRoles([...])).
 * Rows are rolled back after each test (dama/doctrine-test-bundle), so names only have to be
 * unique within one test; the zz_test_ prefix keeps them recognisable if one ever leaks.
 *
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return User::class;
    }

    /**
     * A persisted, enabled user with exactly these roles (User::getRoles() always adds ROLE_USER).
     * Pass a username when the test asserts on it (e.g. Blameable createdBy/updatedBy).
     */
    public static function withRoles(array $roles, ?string $username = null): User
    {
        $attributes = ['roles' => $roles];
        if (null !== $username) {
            $attributes['username'] = $username;
            $attributes['email'] = $username.'@test.local';
        }

        return self::createOne($attributes);
    }

    protected function defaults(): array
    {
        $username = 'zz_test_'.self::faker()->unique()->userName();

        return [
            'username' => substr($username, 0, 50),
            'email' => substr($username, 0, 38).'@test.local',
            // loginUser() skips the password check; this is never a usable hash.
            'password' => 'not-a-password-hash',
            'enabled' => 1,
            'roles' => [],
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
        ];
    }
}
