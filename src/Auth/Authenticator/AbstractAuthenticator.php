<?php

declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace Symbiotic\Auth\Authenticator;

use Symbiotic\Auth\AuthenticatorInterface;
use Symbiotic\Auth\AuthException;
use Symbiotic\Auth\User;
use Symbiotic\Auth\UserInterface;


abstract class AbstractAuthenticator implements AuthenticatorInterface
{
    /**
     * @param array $data
     *
     * @return UserInterface
     * @throws AuthException
     */
    protected function initUser(array $data): UserInterface
    {
        if (!isset($data['access_group'])) {
            throw new AuthException("The user's group is not defined!");
        }
        return new User(
            (int)$data['access_group'],
            $data['full_name'] ?? $this->getDefaultUserName($data['access_group']),
            $data['id'] ?? null
        );
    }

    /**
     * @param int $accessGroup
     *
     * @return string
     */
    protected function getDefaultUserName(int $accessGroup): string
    {
        return match ($accessGroup) {
            UserInterface::GROUP_GUEST => 'Guest',
            UserInterface::GROUP_MANAGER => 'Manager',
            UserInterface::GROUP_ADMIN => 'Admin',
            default => 'No Name',
        };
    }
}