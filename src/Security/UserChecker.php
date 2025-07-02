<?php
/**
 * On login, checks that the user's 'enabled' field is set to 1. If not, throws an exception and doesn't allow
 * the user to log in.
 */
namespace App\Security;

use App\Entity\User as AppUser;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        if (!$user->getEnabled()) {
            // the message passed to this exception is meant to be displayed to the user
            throw new CustomUserMessageAccountStatusException('Your user account has been disabled.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        // user account is disabled, the user may be notified
        if (!$user->getEnabled()) {
            throw new CustomUserMessageAccountStatusException('Your user account has been disabled.');
        }
    }
}
