<?php

declare(strict_types=1);

namespace Symbiotic\Http\Kernel;

use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamInterface};
use Psr\Http\Server\RequestHandlerInterface;
use Symbiotic\Apps\{ApplicationInterface, AppsRepositoryInterface};
use Symbiotic\Core\{CoreInterface, Support\ArrayableInterface, Support\RenderableInterface};
use Symbiotic\Http\HttpException;
use Symbiotic\Http\ResponseMutable;
use Symbiotic\Routing\RouteInterface;


class RouteHandler implements RequestHandlerInterface
{
    /**
     * @var CoreInterface
     */
    protected CoreInterface $core;

    /**
     * @var RouteInterface
     */
    protected RouteInterface $route;

    /**
     * @param CoreInterface  $app
     * @param RouteInterface $route
     */
    public function __construct(CoreInterface $app, RouteInterface $route)
    {
        $this->core = $app;
        $this->route = $route;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $app = $this->core;

        /**
         * @var RouteInterface                     $route
         * @var CoreInterface|ApplicationInterface $container
         * @var AppsRepositoryInterface|null       $apps
         * @var callable|string|null               $handler
         */
        $route = $this->route;
        $apps = $app[AppsRepositoryInterface::class];
        $action = $route->getAction();

        $container = (isset($action['app']) && ($apps instanceof AppsRepositoryInterface)) ? $apps->getBootedApp(
            $action['app']
        ) : $this->core;

        $handler = $route->getHandler();
        if (!is_string($handler) && !is_callable($handler)) {
            throw new HttpException('Incorrect route handler for route ' . $route->getPath() . '!');
        }
        // Distributing the request to the handler
        $request_interface = ServerRequestInterface::class;
        $app->live($request_interface, function () use ($request) {
            return $request;
        },         'request');
        $app->alias($request_interface, \get_class($request));

        // Setting a mutable response object for the handler
        $response = new ResponseMutable($app[ResponseFactoryInterface::class]->createResponse());
        $app->live(ResponseInterface::class, function () use ($response) {
            return $response;
        },         'response');

        return $this->prepareResponse($container->call($handler, $route->getParams()), $response);
    }

    /**
     * @param                 $data
     * @param ResponseMutable $response
     *
     * @return ResponseInterface
     */
    protected function prepareResponse($data, ResponseMutable $response): ResponseInterface
    {
        if ($data instanceof ResponseInterface) {
            return $data;
        } elseif ($data instanceof StreamInterface) {
            return $response->withBody($data)->getRealInstance();
        }
        if (is_array(
                $data
            ) || $data instanceof \Traversable || $data instanceof ArrayableInterface || $data instanceof \JsonSerializable) {
            $response->withHeader('content-type', 'application/json'); // todo: to middleware with flag
            $data = \_S\collect($data)->__toString();
        } elseif ($data instanceof RenderableInterface
            || (is_object($data) && \method_exists($data, '__toString')) /*$data instanceof \Stringable*/) {
            $data = $data->__toString();
        }
        // we write in a line, if you need to give a large amount of content, then immediately give a Response from the handler
        $response->getBody()->write((string)$data);
        return $response->getRealInstance();
    }

}