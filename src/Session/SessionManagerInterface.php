<?php

declare(strict_types=1);

namespace Symbiotic\Session;

interface SessionManagerInterface
{

    /**
     * Add Driver for building session storage
     *
     * @param string   $name
     * @param callable $builder
     *
     * @return void
     */
    public function addDriver(string $name, callable $builder): void;

    /**
     * @return SessionStorageInterface
     */
    public function store(): SessionStorageInterface;


    /**
     * Session config
     *
     * @return array
     */
    public function getConfig(): array;
}