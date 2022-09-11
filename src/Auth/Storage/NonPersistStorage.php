<?php

declare(strict_types=1);

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace Symbiotic\Auth\Storage;


use Symbiotic\Auth\AuthStorageInterface;

/**
 * Non-Persistent Authentication Storage
 *
 * Since HTTP Authentication happens again on each request, this will always be
 * re-populated. So there's no need to use sessions, this simple value class
 * will hold the data for rest of the current request.
 */
class NonPersistStorage implements AuthStorageInterface
{
    /**
     * Holds the actual auth data
     * @var string|null
     */
    protected ?string $content = null;

    /**
     * Returns true if and only if storage is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->content);
    }

    /**
     * Returns the contents of storage
     * Behavior is undefined when storage is empty.
     *
     * @return string|null
     */
    public function read(): ?string
    {
        return $this->content;
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
        $this->content = $contents;
    }

    /**
     * Clears contents from storage
     *
     * @return void
     */
    public function clear(): void
    {
        $this->content = null;
    }
}