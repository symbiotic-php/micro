<?php

declare(strict_types=1);

namespace Symbiotic\Auth;

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */
interface AuthenticatorInterface
{
    /**
     * Performs an authentication attempt
     *
     * @return ResultInterface
     * @throws AuthException If authentication cannot be performed
     */
    public function authenticate(): ResultInterface;
}