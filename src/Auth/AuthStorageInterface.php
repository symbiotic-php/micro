<?php

declare(strict_types=1);

namespace Symbiotic\Auth;


interface AuthStorageInterface
{
    /**
     * Checking the availability of user data in the authorization store
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Returns the contents of storage
     *
     * Behavior is undefined when storage is empty.
     *
     * @return string|null  serialized string {@uses \Symbiotic\Auth\UserInterface} or null
     */
    public function read(): ?string;

    /**
     * Writes $contents to storage
     *
     * @param string $contents serialized string {@uses \Symbiotic\Auth\UserInterface}
     *
     * @return void
     * @throws
     */
    public function write(string $contents): void;

    /**
     * Clears contents from storage
     *
     * @return void
     * @throws
     */
    public function clear(): void;
}