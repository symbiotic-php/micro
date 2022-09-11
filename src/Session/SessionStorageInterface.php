<?php

declare(strict_types=1);

namespace Symbiotic\Session;

use Symbiotic\Container\ArrayContainerInterface;


interface SessionStorageInterface extends ArrayContainerInterface
{

    /**
     * @todo: add touch function  \SessionUpdateTimestampHandlerInterface
     */
    /**
     * @return bool
     */
    public function isUpdated(): bool;

    /**
     * Start the session, reading the data from a handler.
     *
     * @return bool
     */
    public function start(): bool;

    /**
     * Get the current session ID.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set the session ID.
     *
     * @param string $id
     *
     * @return void
     */
    public function setId(string $id): void;

    /**
     * Get the name of the session.
     *
     * @return false|string
     */
    public function getName(): false|string;


    /**
     * Save the session data to storage.
     *
     * @return bool
     */
    public function save(): bool;

    /**
     * Destroy the session to storage.
     *
     * @return bool
     */
    public function destroy(): bool;


    /**
     * Remove all the items from the session.
     *
     * @return void
     */
    public function clear(): void;


    /**
     * Determine if the session has been started.
     *
     * @return bool
     */
    public function isStarted(): bool;
}