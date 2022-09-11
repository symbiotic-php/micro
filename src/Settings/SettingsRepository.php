<?php

declare(strict_types=1);

namespace Symbiotic\Settings;

class SettingsRepository implements SettingsRepositoryInterface
{

    public function __construct(protected SettingsStorageInterface $storage)
    {
    }

    /**
     * Save Settings to store
     *
     * @param string            $key
     * @param SettingsInterface $settings
     *
     * @return bool
     */
    public function save(string $key, SettingsInterface $settings): bool
    {
        return $this->storage->set($key, $settings->all());
    }

    /**
     * @param string $key
     *
     * @return array|null
     * @todo: Can build a settings object {@see SettingsInterface} and return it?
     */
    public function get(string $key): ?array
    {
        if (!$this->has($key)) {
            return null;
        }
        return $this->storage->get($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->storage->has($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function remove(string $key): bool
    {
        return $this->storage->remove($key);
    }
}