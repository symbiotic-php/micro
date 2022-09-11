<?php

declare(strict_types=1);

namespace Symbiotic\Settings;


interface SettingsStorageInterface
{
    /**
     * @param string $name
     * @param array  $data
     *
     * @return bool
     */
    public function set(string $name, array $data): bool;

    /**
     * @param string $name
     *
     * @return array
     */
    public function get(string $name): array;

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * @param string $name
     *
     * @return bool
     */
    public function remove(string $name): bool;
}