<?php

declare(strict_types=1);

namespace Symbiotic\Core;

use Symbiotic\Container\ServiceContainerInterface;
use Symbiotic\Packages\PackagesRepositoryInterface;


class ProvidersRepository
{
    const EXCLUDE = 32;
    const ACTIVE = 1;
    const DEFER = 64;

    /**
     * @var array
     * [class => bool (active flag),... ]
     */
    protected array $providers = [];

    /**
     * @var array [serviceClassName => ProviderClassName]
     */
    protected array $defer_services = [];

    /**
     * @var bool
     */
    protected bool $loaded = false;

    /**
     * @param string|string[] $items
     * @param int             $flag
     */
    public function add(array $items, int $flag = self::ACTIVE): void
    {
        $providers = &$this->providers;
        foreach ($items as $v) {
            $v = ltrim($v, '\\');
            $providers[$v] = isset($providers[$v]) ? $providers[$v] | $flag : $flag;
        }
    }


    /**
     * @param string|string[] $items
     */
    public function exclude(array $items): void
    {
        $this->add($items, self::EXCLUDE);
    }

    /**
     * @param array $items = [ProviderClasName => [Service1,Service2]]
     */
    protected function defer(array $items): void
    {
        $providers = [];
        foreach ($items as $provider => $services) {
            $providers [] = $provider;
            foreach ($services as $v) {
                $this->defer_services[\ltrim($v)] = $provider;
            }
        }
        $this->add($providers, self::DEFER);
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->providers;
    }

    /**
     * Checking whether the deferred service is registered
     *
     * @param string $service
     *
     * @return bool
     */
    public function isDefer(string $service): bool
    {
        return isset($this->defer_services[\ltrim($service)]);
    }

    /**
     * @param ServiceContainerInterface $app
     * @param array                     $force_providers
     * @param array                     $force_exclude
     */
    public function load(ServiceContainerInterface $app, array $force_providers = [], array $force_exclude = []): void
    {
        if (!$this->loaded) {
            if($app->has(PackagesRepositoryInterface::class)) {
                foreach ($app[PackagesRepositoryInterface::class]->all() as $config) {
                    $this->add(isset($config['providers']) ? (array)$config['providers'] : []);
                    $this->defer(isset($config['defer']) ? (array)$config['defer'] : []);
                    $this->exclude(isset($config['providers_exclude']) ? (array)$config['providers_exclude'] : []);
                }
            }
        }

        foreach ($force_providers as $v) {
            $this->providers[ltrim($v, '\\')] = self::ACTIVE;
        }
        $this->exclude($force_exclude);
        /**
         * @var ServiceProviderInterface $provider
         */
        foreach ($this->providers as $provider => $mask) {
            if (!($mask & self::DEFER || $mask & self::EXCLUDE)) {
                $app->register($provider);
            }
        }
        $app->setDeferred($this->defer_services);
    }

    public function __wakeup()
    {
        $this->loaded = true;
    }

}