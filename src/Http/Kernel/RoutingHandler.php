<?php

declare(strict_types=1);

namespace Symbiotic\Http\Kernel;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use Symbiotic\Apps\AppsRepositoryInterface;
use Symbiotic\Core\CoreInterface;
use Symbiotic\Http\Middleware\{MiddlewaresDispatcher};
use Symbiotic\Core\HttpKernelInterface;
use Symbiotic\Http\HttpException;
use Symbiotic\Routing\RouteInterface;


class RoutingHandler implements RequestHandlerInterface
{
    /**
     * @var CoreInterface
     */
    protected CoreInterface $core;

    /**
     * @param CoreInterface $core
     */
    public function __construct(CoreInterface $core)
    {
        $this->core = $core;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $core = $this->core;
        /**
         * @var RouteInterface|null $route
         */
        $path = $request->getUri()->getPath();
        if ($this->core->has(RouteInterface::class)) {
            /**
             * In order not to load all core services, we first define the router.
             * If the route is not found, there is no point in loading the kernel.
             * The intermediary duplicates the behavior of this handler, but only when routing settlements
             * @see \Symbiotic\Routing\KernelPreloadFindRouteMiddleware::process()
             */
            $route = $core[RouteInterface::class];
        } else {
            /**
             * Set request for define domain in router
             */
            $core->instance(ServerRequestInterface::class, $request);
            $route = $core['router']->match($request->getMethod(), $path);
            $core->delete(ServerRequestInterface::class);
            $core->instance(RouteInterface::class, $route);
            $core->setLive(RouteInterface::class);
        }

        if ($route) {
            foreach ($route->getParams() as $k => $v) {
                $request = $request->withAttribute($k, $v);
            }

            $middlewares = $route->getMiddlewares();
            $action = $route->getAction();
            $application = $core;
            if (isset($action['app'])) {
               $application = $core[AppsRepositoryInterface::class]->getBootedApp($action['app']);
            }
            if (!empty($middlewares)) {
                $middlewaresDispatcher = new MiddlewaresDispatcher($application);
                $middlewares = $middlewaresDispatcher->factoryCollection($middlewares);
            } else {
                $middlewares = [];
            }
            return $core['events']->dispatch(new RouteMiddlewares($middlewares))
                ->process(
                    $request,
                    new RouteHandler(
                        $core, $route
                    )
                );
        } else {
            $core['destroy_response'] = true;
            return $core[HttpKernelInterface::class]->response(
                404,
                new HttpException(
                    'Route not found for path [' . $path . ']', 7623
                )
            );
        }
    }

}