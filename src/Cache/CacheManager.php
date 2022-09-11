<?php

declare(strict_types=1);

namespace Symbiotic\Cache;

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Symbiotic\Container\DIContainerInterface;


class CacheManager implements CacheManagerInterface
{

    /**
     * @var callable[] [driver_name => function($config, $container): CacheInterface;,...]
     */
    protected array $drivers = [];

    /**
     * @var array $config
     * [
     *   'default' => 'file',
     *   'stores' => [name => store_config]
     * ]
     */
    protected array $config = [];

    public function __construct(protected ContainerInterface $container)
    {
        $config = $container->get('config');
        $cache_path = $container->get('cache_path');
        if ($config->has('cache')) {
            $this->config = $config->get('cache');
        } elseif ($cache_path) {
            $this->config = [
                'default' => 'file',
                'stores' => ['file' => ['driver' => 'file', 'path' => $cache_path]]
            ];
        }
        // Adding default filesystem driver
        $this->drivers['file'] = [$this, 'createFileStore'];
    }

    /**
     * Adding a Storage Builder
     *
     * @param string   $name
     * @param callable $builder function(array $config, ContainerInterface $container): CacheInterface;
     *
     * @return void
     */
    public function addDriver(string $name, callable $builder): void
    {
        $this->drivers[$name] = $builder;
    }

    /**
     * Returns the storage built on the basis of its config
     *
     * @param string|null $name
     *
     * @return CacheInterface
     * @throws \Exception
     */
    public function store(string $name = null): CacheInterface
    {
        if (!$name) {
            $name = $this->config['default'];
        }
        if (!isset($this->config['stores']) || !isset($this->config['stores'][$name])) {
            throw new CacheException("Cache storage ($name) not found!");
        }
        $config = $this->config['stores'][$name];

        if (isset($this->drivers[$config['driver']])) {
            return call_user_func($this->drivers[$config['driver']], $config, $this->container);
        }

        throw new CacheException("Cache driver ({$config['driver']}) not found!");
    }

    /**
     * @param array                $config
     * @param DIContainerInterface $app
     *
     * @return CacheInterface
     * @throws \Symbiotic\Container\BindingResolutionException
     * @throws \Symbiotic\Container\NotFoundException
     */
    protected function createFileStore(array $config, DIContainerInterface $app): CacheInterface
    {
        return $app->make(FilesystemCache::class, ['cache_directory' => $config['path'], 'default_ttl' => 9999999999]);
    }
}