<?php

declare(strict_types=1);

namespace Symbiotic\Routing;


class AppsRoutesRepository
{
    /**
     * @var array[]
     */
    protected array $providers = [];

    /**
     * Add Provider
     *
     * @param string      $appId
     * @param string      $class
     * @param string|null $baseNamespace
     *
     * @return void
     * @see \Symbiotic\Apps\Bootstrap::bootstrap() event
     */
    public function append(string $appId, string $class, ?string $baseNamespace = null): void
    {
        $this->providers[$appId] = [$class, $baseNamespace];
    }

    /**
     * @param string $appId
     *
     * @return bool
     */
    public function has(string $appId): bool
    {
        return isset($this->providers[$appId]);
    }

    /**
     * @param $appId
     *
     * @return AppRoutingInterface|null
     */
    public function getByAppId($appId): ?AppRoutingInterface
    {
        return isset($this->providers[$appId]) ? $this->resolveProvider($appId, $this->providers[$appId]) : null;
    }

    /**
     * @param string $appId
     * @param array  $config [0 => 'ProviderClassName', 1 => 'baseNamespace']
     *
     * @return AppRoutingInterface
     *
     */
    protected function resolveProvider(string $appId, array $config): AppRoutingInterface
    {
        return new ($config[0])($appId, $config[1]);
    }

    /**
     * @return array|AppRoutingInterface[]
     */
    public function getProviders(): array
    {
        $providers = [];
        foreach ($this->providers as $k => $v) {
            $providers[$k] = $this->resolveProvider($k, $v);
        }

        return $providers;
    }
}