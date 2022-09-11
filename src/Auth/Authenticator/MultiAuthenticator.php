<?php

declare(strict_types=1);

namespace Symbiotic\Auth\Authenticator;

use Symbiotic\Auth\AuthenticatorInterface;
use Symbiotic\Auth\AuthResult;
use Symbiotic\Auth\ResultInterface;


class MultiAuthenticator implements AuthenticatorInterface
{
    /**
     * @var array|AuthenticatorInterface[]
     */
    protected array $authenticators = [];

    /**
     * @param AuthenticatorInterface $authenticator
     * @param bool                   $prepend
     *
     * @return void
     */
    public function addAuthenticator(AuthenticatorInterface $authenticator, bool $prepend = false): void
    {
        if ($prepend) {
            array_unshift($this->authenticators, $authenticator);
        } else {
            $this->authenticators[] = $authenticator;
        }
    }

    /**
     * @return ResultInterface
     * @throws \Exception
     */
    public function authenticate(): ResultInterface
    {
        foreach ($this->authenticators as $authenticator) {
            $result = $authenticator->authenticate();
            if ($result->isValid()) {
                return $result;
            }
        }
        return (new AuthResult())->setError('Not auth!');
    }
}