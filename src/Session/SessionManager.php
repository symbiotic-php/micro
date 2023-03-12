<?php

declare(strict_types=1);

namespace Symbiotic\Session;

use Psr\Container\ContainerInterface;


class SessionManager implements SessionManagerInterface
{
    /**
     * @var array
     */
    protected array $config = [];

    /**
     * @var array|array[]
     */
    protected array $drivers = [];

    public function __construct(protected ContainerInterface $container)
    {
        $this->config = $container->get('config::session', []);
        if (!isset($this->config['name'])) {
            $this->config['name'] = session_name();
        }
        if (!isset($this->config['minutes'])) {
            $this->config['minutes'] = 60 * 24;
        }
        if(!isset($this->config['namespace']) && $container->get('config')->get('symbiosis')) {
            $this->config['namespace'] = '5a8309dedb810d2322b6024d536832ba';
        }
        $this->drivers = [
            'native' => [$this, 'createNativeDriver']
        ];
    }

    /**
     * @return SessionStorageInterface
     * @throws \Exception
     */
    public function store(): SessionStorageInterface
    {
        $driver = $this->getDefaultDriver();
        if (!isset($this->drivers[$driver])) {
            throw new SessionException("Session driver ($driver) not found!");
        }
        return \call_user_func($this->drivers[$driver], $this->config, $this->container);
    }

    /**
     * @param string   $name
     * @param callable $builder
     *
     * @return void
     */
    public function addDriver(string $name, callable $builder): void
    {
        $this->drivers[$name] = $builder;
    }

    /**
     * Create an instance of the file session driver.
     *
     * @return SessionStorageInterface
     */
    public function createNativeDriver(array $config, ContainerInterface $container): SessionStorageInterface
    {
        $symbiosis = (bool)$container->get('config')->get('symbiosis');
        return new SessionStorageNative(
            $symbiosis,
            $config['namespace'] ?? null,
        );
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get the default session driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->config['driver'] ?? 'native';
    }

    /**
     * Set the default session driver name.
     *
     * @param string $name
     *
     * @return void
     */
    public function setDefaultDriver(string $name): void
    {
        $this->config['driver'] = $name;
    }
}
