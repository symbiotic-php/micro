<?php

declare(strict_types=1);

namespace Symbiotic\Auth;


interface ResultInterface
{
    /**
     * @return array|object|string|null
     */
    public function getIdentity(): object|array|string|null;

    /**
     * Is the authorization successful
     * @return bool
     */
    public function isValid(): bool;
}