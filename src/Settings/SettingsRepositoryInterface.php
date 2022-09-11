<?php

declare(strict_types=1);

namespace Symbiotic\Settings;


interface SettingsRepositoryInterface
{
    /**
     * @param string            $key
     * @param SettingsInterface $settings
     *
     * @return bool
     */
    public function save(string $key, SettingsInterface $settings): bool;

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * @param string $key
     *
     * @return array|null
     * @todo: Can build a settings object {@see SettingsInterface} and return it?
     */
    public function get(string $key): ?array;

    /**
     * @param string $key
     *
     * @return bool
     */
    public function remove(string $key): bool;
}