<?php

declare(strict_types=1);

namespace Symbiotic\Apps;

use Exception;
use Psr\Container\ContainerInterface;
use Symbiotic\Container\FactoryInterface;


class AppsRepository implements AppsRepositoryInterface
{

    /**
     * @var ApplicationInterface[]
     */
    protected array $apps = [];

    /**
     * List of application plugins
     *
     * @var array[]
     * [ 'app_id' => ['plugin_id',....] ]
     */
    protected array $apps_plugins = [];

    /**
     * Application configuration from composer
     * section (symbiotic -> app)
     * @var array ['app_id'=> [config]....]
     */
    protected array $apps_config = [];

    /**
     * List of disabled applications
     *
     * @var array ['app_id' => 'app_id',...]
     */
    protected array $disabled_apps = [];


    /**
     * @param FactoryInterface $container
     */
    public function __construct(protected FactoryInterface $container)
    {
    }

    /**
     * Disabling applications by id
     *
     * @param array $ids applications ids
     */
    public function disableApps(array $ids): void
    {
        $this->disabled_apps = array_merge($this->disabled_apps, array_combine($ids, $ids));
    }

    /**
     *
     * The framework accepts compositor packages as applications and components,
     *  or just functionality registered in the system as an application.
     *
     * The system assumes a multi-level dependency of applications and packages.
     * As an example: there is a Tiny visual editor application, there is an image editing plugin for it,
     * there is a feature (button) for the image editor, for example, blurring faces in a photo.
     * The plugin inherits the parent application, which also has a parent application.
     *
     * @param array $config = [
     *                      'id' => 'app_id_string', // Register short app id or use composer package name
     *                      'name' => 'App title',
     *                      'parent_app' => 'parent_app_id', //  Parent app id or package name
     *                      'description' => 'App description....',
     *                      'routing' => '\\Symbiotic\\App\\Core\\Routing', // class name implements
     *                      {@see \Symbiotic\Routing\AppRoutingInterface}
     *                      'controllers_namespace' => '\\Symbiotic\\App\\Core\\Controllers', // Your base controllers
     *                      namespace
     *
     *                      'providers' => [    // Providers of your app
     *                      '\\Symbiotic\\App\\Core\\Providers\\FilesProvider',
     *                      '\\Symbiotic\\App\\Core\\Providers\\AppsUpdaterProvider',
     *                      ],
     *
     *     // .... and your advanced params
     * ];
     *
     * @return void
     * @throws
     */
    public function addApp(array $config): void
    {
        if (empty($config['id'])) {
            throw  new AppsException('Empty app id!');
        }
        $id = $config['id'];
        $this->apps_config[$id] = $config;
        $parent_app = $config['parent_app'] ?? null;
        if ($parent_app) {
            $this->apps_plugins[$parent_app][$id] = 1;
        }
    }

    /**
     * Returns the downloaded application
     *
     * @param string $id
     *
     * @return ApplicationInterface
     *
     * @throws AppNotFoundException if not found application
     */
    public function getBootedApp(string $id): ApplicationInterface
    {
        $app = $this->get($id);
        $app->bootstrap();

        return $app;
    }

    /**
     * @param string $id
     *
     * @return ApplicationInterface
     * @throws AppNotFoundException
     */
    public function get(string $id): ApplicationInterface
    {
        if (isset($this->apps[$id])) {
            return $this->apps[$id];
        }
        if ($config = $this->getConfig($id)) {
            $app = $this->container->make(
                (isset($config['app_class'])) ? $config['app_class'] : ApplicationInterface::class,
                [
                    'app' => isset($config['parent_app']) ? $this->get($config['parent_app']) : $this->container,
                    'config' => $config
                ]
            );
            return $this->apps[$id] = $app;
        }
        throw new AppNotFoundException("Application with id [$id] is not exists!");
    }

    /**
     * @param string $id
     *
     * @return AppConfigInterface|null
     */
    public function getConfig(string $id): ?AppConfigInterface
    {
        return isset($this->apps_config[$id]) ? new AppConfig($this->apps_config[$id]) : null;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->apps_config[$id]);
    }

    /**
     * @return string[]
     */
    public function getIds(): array
    {
        return array_keys($this->apps_config);
    }

    /**
     * @return array[] apps configs
     */
    public function enabled(): array
    {
        return \array_diff_key($this->apps_config, $this->disabled_apps);
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->apps_config;
    }

    /**
     * @param string $app_id
     *
     * @return array|[string]
     */
    public function getPluginsIds(string $app_id): array
    {
        return (isset($this->apps_plugins[$app_id])) ? array_keys($this->apps_plugins[$app_id]) : [];
    }

    public function flush(): void
    {
        $this->apps = [];
    }

    /**
     * @param ContainerInterface|null $container
     *
     * @return $this|null
     */
    public function cloneInstance(?ContainerInterface $container): ?AppsRepositoryInterface
    {
        /**
         * @var FactoryInterface $container
         */
        $new = clone $this;
        $new->container = $container;
        // clean instances with old container
        $new->flush();

        return $new;
    }

}