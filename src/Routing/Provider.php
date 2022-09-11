<?php

declare(strict_types=1);

namespace Symbiotic\Routing;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Core\{CoreInterface, ServiceProvider};


class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerFactory();
        $this->registerRoutesRepository();
        $this->registerRouter();
        $this->registerUriGenerator();
    }

    protected function registerFactory()
    {
        $this->app->singleton(RouterFactoryInterface::class, function (DIContainerInterface $app) {
            $class = $this->getFactoryClass();
            $router_params = [
                'domain' => ($app('config::default_host', 'localhost')),
                'secure' => false
            ];
            if ($app->has(ServerRequestInterface::class)) {
                /**
                 * @var  UriInterface $uri
                 */
                $uri = $app->get(ServerRequestInterface::class)->getUri();

                $router_params['domain'] = $uri->getHost() . (in_array($uri->getPort(), [80, 443, null]
                    ) ? '' : ':' . $uri->getPort());
                $router_params['secure'] = $uri->getScheme() === 'https';
            }
            return new $class(
                $app,
                $this->getRouterClass(),
                $this->routesLoaderCallback(),
                $router_params
            );
        });
    }

    protected function getFactoryClass(): string
    {
        return RouterFactory::class;
    }

    protected function getRouterClass(): string
    {
        return Router::class;
    }

    protected function routesLoaderCallback(): \Closure
    {
        return function (RouterInterface $router) {
            $app = $this->app;
            $backend_prefix = \trim($app('config::backend_prefix', 'backend'), '/');
            /**
             * @var AppRoutingInterface $provider
             * @var RouterInterface     $router
             */
            foreach ($app[AppsRoutesRepository::class]->getProviders() as $provider) {
                $app_id = $provider->getAppId();

                $router->group([
                                   'prefix' => $app_id,
                                   'as' => $app_id . '::',
                                   'app' => $app_id
                               ], function ($router) use ($provider) {
                    $provider->loadFrontendRoutes($router);
                });

                $router->group([
                                   'prefix' => 'api/' . $app_id,
                                   'as' => 'api:' . $app_id . '::',
                                   'app' => $app_id
                               ], function ($router) use ($provider) {
                    $provider->loadApiRoutes($router);
                });

                $router->group([
                                   'prefix' => $backend_prefix . '/' . $app_id,
                                   'as' => 'backend:' . $app_id . '::',
                                   'app' => $app_id
                               ]
                    , function ($router) use ($provider) {
                        $provider->loadBackendRoutes($router);
                    });

                $router->group([
                                   'as' => 'default:' . $app_id . '::',
                                   'app' => $app_id
                               ], function ($router) use ($provider) {
                    $provider->loadDefaultRoutes($router);
                });
            }
        };
    }

    protected function registerRoutesRepository()
    {
        $interface = AppsRoutesRepository::class;
        $this->app->singleton($interface)
            ->afterResolving($interface, static function (AppsRoutesRepository $repository, DIContainerInterface $app) {
                return $app['events']->dispatch($repository);
            });
    }

    protected function registerRouter()
    {
        $this->app->singleton(RouterInterface::class, static function ($app) {
            /**
             * @var RouterFactoryInterface $f
             */
            $f = $app[RouterFactoryInterface::class];
            $router = $f->factoryRouter(['name' => 'default']);
            $f->loadRoutes($router);

            return $router;
        },                    'router');
    }

    protected function registerUriGenerator()
    {
        $this->app->singleton(UrlGeneratorInterface::class, static function (CoreInterface $app) {
            $base_uri = $app['base_uri'];
            $prefix = $app['config::uri_prefix'];
            if (!empty($prefix)) {
                $base_uri = rtrim($base_uri, '\\/') . '/' . trim($prefix, '\\/');
            }
            return new UrlGenerator(
                $app['router'],
                $base_uri,
                trim($app('config::assets_prefix', 'assets'), '/')
            );
        },                    'url');
    }

    public function boot(): void
    {
        // in road runner
        $this->app[AppsRoutesRepository::class];
    }

    /**
     * @return RouterFactoryInterface
     */
    protected function getFactory():RouterFactoryInterface
    {
        return $this->app->make(RouterFactoryInterface::class);
    }
}