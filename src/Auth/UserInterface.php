<?php

declare(strict_types=1);

namespace Symbiotic\Auth;


interface UserInterface
{
    /**
     * User group without rights
     */
    const GROUP_GUEST = 0;

    /**
     * A group with access to the admin panel that allows all applications,
     * except for applications with access level - admin
     */
    const GROUP_MANAGER = 1;

    /**
     * Superuser with unlimited rights
     */
    const GROUP_ADMIN = 69696969;

    /**
     * User role
     *
     * @return int
     */
    public function getAccessGroup(): int;

    /**
     * Combined first and last name of the user
     *
     * @return string
     */
    public function getFullName(): string;
}