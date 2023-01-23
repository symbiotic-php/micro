<?php

declare(strict_types=1);

namespace Symbiotic\Apps;

use Symbiotic\Container\{DIContainerInterface, ServiceContainerTrait, SubContainerTrait};
use Psr\Container\ContainerInterface;
use Symbiotic\Packages\AssetsRepositoryInterface;
use Symbiotic\Packages\ResourcesRepositoryInterface;


class Application implements ApplicationInterface
{
    use ServiceContainerTrait,
        SubContainerTrait;


    /**
     * @param DIContainerInterface    $app
     * @param AppConfigInterface|null $config
     */
    public function __construct(DIContainerInterface $app, AppConfigInterface $config = null)
    {
        $this->app = $app;

        $this->instance(AppConfigInterface::class, $config, 'config');
        $config_class = get_class($config);
        if ($config_class !== AppConfig::class) {
            $this->alias(AppConfigInterface::class, AppConfig::class);
        }
        $this->dependencyInjectionContainer = $this;

        $class = get_class($this);
        $this->instance(ApplicationInterface::class, $this);

        $this->alias(ApplicationInterface::class, $class);
        $this->alias(ApplicationInterface::class, ContainerInterface::class);
        if ($class !== self::class) {
            $this->alias($class, self::class);
        }
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getAppName(): string
    {
        return $this['config']->getAppName();
    }

    /**
     * @inheritDoc
     *
     * @return string|null
     */
    public function getRoutingProvider(): ?string
    {
        return $this['config']->getRoutingProvider();
    }

    /**
     * @inheritDoc
     *
     * @param \Closure[]|null $bootstraps
     *
     * @return void
     */
    public function bootstrap(array $bootstraps = null): static
    {
        if (!is_array($bootstraps)) {
            $bootstraps = [];
        }
        if (!$this->booted) {
            $bootstraps[] = $this->getBootstrapCallback();
        }

        // If there is a parent module, we pass our loader
        if (!$this->booted && $parent_app = $this->getParentApp()) {
            $parent_app->bootstrap($bootstraps);
        } else {
            // We start the download, starting from the root module itself
            foreach (array_reverse($bootstraps) as $boot) {
                $boot();
            }
        }

        $this->booted = true;

        return $this;
    }

    /**
     * Application Loader
     *
     * @return \Closure
     */
    protected function getBootstrapCallback(): \Closure
    {
        return function () {
            $this->registerProviders();
            $this->boot();
        };
    }

    /**
     * Launching the registration of application providers
     *
     * @return void
     */
    protected function registerProviders(): void
    {
        foreach ($this('config::providers', []) as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Retrieves the parent application object
     *
     * @return ApplicationInterface|null
     */
    protected function getParentApp(): ?ApplicationInterface
    {
        return $this->hasParentApp() ? $this[AppsRepositoryInterface::class]->get($this->getParentAppId()) : null;
    }

    /**
     * @inheritDoc
     *
     * @return bool
     */
    public function hasParentApp(): bool
    {
        return $this['config']->hasParentApp();
    }

    /**
     * @inheritDoc
     *
     * @return string|null
     */
    public function getParentAppId(): ?string
    {
        return $this['config']->getParentAppId();
    }

    /**
     * @inheritDoc
     *
     * @param string $path
     * @param bool   $absolute
     *
     * @return string
     *
     * @throws
     * @uses \Symbiotic\Routing\UrlGeneratorInterface
     */
    public function asset(string $path, bool $absolute = true): string
    {
        return $this->get('url')->asset($this->getId() . '::' . $path, $absolute);
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getId(): string
    {
        return $this['config']->getId();
    }

    /**
     * @inheritDoc
     *
     * @param string|null $path
     *
     * @return string|null
     */
    public function getBasePath(string $path = null): ?string
    {
        return $this['config']->getBasePath($path);
    }

    /**
     * @inheritDoc
     *
     * @return string|null
     * @throws
     * @deprecated
     */
    public function getResourcesPath(): ?string
    {
        return $this->get(ResourcesRepositoryInterface::class)->getResourcesPath($this->getId());
    }

    /**
     * @return string|null
     * @throws
     * @uses \Symbiotic\Packages\AssetsRepositoryInterface
     * @deprecated
     */
    public function getAssetsPath(): ?string
    {
        return $this->get(AssetsRepositoryInterface::class)->getAssetsPath($this->getId());
    }

    /**
     * @inheritDoc
     *
     * @param DIContainerInterface|null $container
     *
     * @return ApplicationInterface
     */
    public function cloneInstance(?ContainerInterface $container): ?static
    {
        /**
         * Before the first cloning, we load all the single services
         */
        $cloned_key = '__cloned_' . $this->getId();
        if (!$this->has($cloned_key)) {
            foreach ($this->getBindings() as $abstract => $bind) {
                if ($bind['shared'] === true) {
                    $this->resolve($abstract);
                }
            }
            $this->instance($cloned_key, true);
        }

        /**
         * Clear live services
         */
        $this->clearLive();

        $new = clone $this;
        $new->dependencyInjectionContainer = $new;

        /**
         * Update parent container
         */
        $new->app = ($this->app instanceof ApplicationInterface)
            /**
             * If there is a parent application, then we request its new instance
             * @uses \Symbiotic\Apps\AppsCloningRepository::get()
             */
            ? $container->get(AppsRepositoryInterface::class)->get($this->app->getId()) : $container;


        return $new;
    }
}
 