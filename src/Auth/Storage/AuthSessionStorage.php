<?php

declare(strict_types=1);

namespace Symbiotic\Auth\Storage;

use Symbiotic\Auth\AuthStorageInterface;
use Symbiotic\Session\SessionStorageInterface;


class AuthSessionStorage implements AuthStorageInterface
{
    const DATA_KEY = 'auth_user';

    /**
     * @param SessionStorageInterface $session
     */
    public function __construct(protected SessionStorageInterface $session)
    {
    }

    /**
     * Returns true if and only if storage is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->session->get(static::DATA_KEY));
    }

    /**
     * Returns the contents of storage
     * Behavior is undefined when storage is empty.
     *
     * @return string|null
     */
    public function read(): ?string
    {
        return $this->session->get(static::DATA_KEY);
    }

    /**
     * Writes $contents to storage
     *
     * @param string $contents
     *
     * @return void
     */
    public function write(string $contents): void
    {
        $this->session->set(static::DATA_KEY, $contents);
    }

    /**
     * Clears contents from storage
     *
     * @return void
     */
    public function clear(): void
    {
        $this->session->delete(static::DATA_KEY);
    }
}