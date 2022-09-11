<?php

declare(strict_types=1);

namespace Symbiotic\Http\Middleware;

use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Http\UriHelper;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class RequestPrefixMiddleware implements MiddlewareInterface
{
    /**
     * @var null|string
     */
    protected ?string $uri_prefix;

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * RequestPrefixMiddleware constructor.
     *
     * @param ContainerInterface $container
     * @param string|null        $uri_prefix - set in the Core container constructor config $app['config::uri_prefix']
     *                                       {@see config.php}
     */
    public function __construct(ContainerInterface $container, string $uri_prefix = null)
    {
        $this->uri_prefix = $uri_prefix;
        $this->container = $container;
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uriHelper = new UriHelper();
        $prefix = $this->uri_prefix;
        $container = $this->container;
        if (!empty($prefix)) {
            $prefix = $uriHelper->normalizePrefix($this->uri_prefix);
            if (!preg_match('/^' . preg_quote($prefix, '/') . '/', $request->getUri()->getPath())) {
                if ($container instanceof DIContainerInterface) {
                    $container->set(
                        'destroy_response',
                        true
                    );// при режиме симбиоза не отдаем ответ, дальше скрипты отработают
                }
                return $container->get(ResponseFactoryInterface::class)->createResponse(404);
            }
        }
        return $handler->handle(
            empty($prefix)
                ? $request
                : $request->withUri($uriHelper->deletePrefix($prefix, $request->getUri()))
        );
    }
}