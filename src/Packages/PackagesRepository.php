<?php

declare(strict_types=1);

namespace Symbiotic\Packages;


class PackagesRepository implements PackagesRepositoryInterface
{
    /**
     * @var PackagesLoaderInterface[]
     */
    protected array $loaders = [];

    /**
     * @var array
     */
    protected array $items = [];

    /**
     * @var bool
     */
    protected bool $loaded = false;

    /**
     * @var array
     */
    protected array $ids = [];

    /**
     * @var array
     */
    protected array $bootstraps = [];

    /**
     * Core events handlers
     *
     * @var array [\Core\EventClass => ['\my\HandlerClass',...]]
     */
    protected array $handlers = [];


    /**
     * You can only add from loaders up to {@see PackagesBootstrap} in the kernel config
     *
     * @param PackagesLoaderInterface $loader
     */
    public function addPackagesLoader(PackagesLoaderInterface $loader): void
    {
        $this->loaders[] = $loader;
    }

    /**
     * @param array $config
     *
     * @return void
     */
    public function addPackage(array $config): void
    {
        $app = $config['app'] ?? null;

        // if modules are supported
        if (is_array($app)) {
            // todo: something is too complicated!!!
            if (!isset($app['id']) && isset($config['id'])) {
                $app['id'] = $config['id'];
            } elseif(isset($app['id'])) {
                $config['id'] = $app['id'] = self::getAppId($app);
            }
            $config['app'] = $app;
        }
        $id = $config['id'] ?? \md5(\serialize($config));
        $this->ids[$id] = $id;
        $this->items[$id] = $config;
        // Core bootstraps
        if (!empty($config['bootstrappers'])) {
            $this->bootstraps = array_merge($this->bootstraps, (array)$config['bootstrappers']);
        }
        // Core listeners
        if (isset($config['events'])) {
            $this->handlers[$id] = array_merge($config['events'], ['id' => $id]);
        }
    }

    /**
     * @param string $id
     *
     * @return string
     */
    public static function normalizeId(string $id): string
    {
        //todo collision with composer packages
        return str_replace(['/', '-', '.'], ['-', '_', '_'], \strtolower($id));
    }

    /**
     * @param array $config Application config
     *
     * @return string
     * @throws \Exception
     */
    public static function getAppId(array $config): string
    {
        if (!isset($config['id'])) {
            throw new PackagesException('App id is required [' . \serialize($config) . ']!');
        }
        $id = self::normalizeId($config['id']);
        $parent_app = $config['parent_app'] ?? null;

        return $parent_app ? self::normalizeId($parent_app) . '.' . $id : $id;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return array
     */
    public function getBootstraps(): array
    {
        return $this->bootstraps;
    }

    /**
     * Core listeners for events
     * @return array[] = ['Events\EventClassName' => ['\My\Handler1','\Other\Handler3'], //....]
     * @see addPackage()
     */
    public function getEventsHandlers(): array
    {
        $handlers = $this->handlers;
        usort($handlers, function ($a, $b) {
            $a_after = isset($a['after']) ? (array)$a['after'] : [];
            $b_after = isset($b['after']) ? (array)$b['after'] : [];
            if (in_array($b['id'], $a_after)) {
                return -1;
            }
            if (in_array($a['id'], $b_after)) {
                return 1;
            }
            return 0;
        });

        $result = [];
        foreach ($handlers as $package) {
            if (isset($package['handlers'])) {
                foreach (((array)$package['handlers']) as $event => $listeners) {
                    $event = trim($event, '\\');
                    if (!isset($result[$event])) {
                        $result[$event] = [];
                    }
                    $result[$event] = array_merge($result[$event], (array)$listeners);
                }
            }
        }

        return $result;
    }

    /**
     * @param string $id
     *
     * @return PackageConfig|null
     * @throws \Exception
     */
    public function getPackageConfig(string $id): ?PackageConfig
    {
        if ($this->has($id)) {
            return new PackageConfig($this->get($id));
        }
        return null;
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public function has($id): bool
    {
        return isset($this->ids[$id]);
    }

    /**
     * @param string $id
     *
     * @return array
     *
     * @throws \Exception
     */
    public function get(string $id): array
    {
        if (!isset($this->items[$id])) {
            throw new PackagesException("Package [$id] not found!");
        }
        return $this->items[$id];
    }

    /**
     * @return array|string[]
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * @return void
     */
    public function load(): void
    {
        if (!$this->loaded) {
            foreach ($this->loaders as $loader) {
                $loader->load($this);
            }
            $this->loaded = true;
        }
    }
}