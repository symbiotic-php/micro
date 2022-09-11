<?php

declare(strict_types=1);

namespace Symbiotic\Auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symbiotic\Apps\AppConfigInterface;
use Symbiotic\Apps\AppsRepositoryInterface;
use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Routing\RouteInterface;


class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @param DIContainerInterface     $container
     * @param RouteInterface           $route
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(
        protected DIContainerInterface $container,
        protected RouteInterface $route,
        protected ResponseFactoryInterface $responseFactory
    ) {
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $container = $this->container;
        $path = $request->getUri()->getPath();
        $backend_prefix = $container->get('config')->get('backend_prefix', 'backend');
        if (empty($backend_prefix)) {
            throw new AuthException('Not configured Backend!');
        }
        // Check that the route of the admin panel is requested
        if (\preg_match('/^\/' . \preg_quote(trim($backend_prefix, "\\/"), '/') . '.*/uDs', $path) === 1) {
            $route = $this->route;
            $action = $route->getAction();
            $app_id = $action['app'] ?? null;
            /**
             * @var AppsRepositoryInterface $apps_repository
             * @var AppConfigInterface|null $app_config
             */
            $apps_repository = $container->get(AppsRepositoryInterface::class);
            $app_config = $apps_repository->getConfig($app_id);

            // a route without an application cannot be in the admin panel
            if (empty($app_id) || !($app_config instanceof AppConfigInterface)) {
                return $this->responseFactory->createResponse(403);// todo add message
            }
            /**
             * @var AuthServiceInterface $auth
             */
            $auth = $container->get(AuthServiceInterface::class);
            if (null === $auth->getIdentity()) {
                $auth->authenticate();
            }
            $user = $auth->getIdentity();
            if ($user instanceof UserInterface) {
                $group = $user->getAccessGroup();
                $app_access = $app_config->get('auth_access_group');
                if (
                    ($app_access === 'admin' && $group === UserInterface::GROUP_ADMIN)
                    || ($app_access !== 'admin' && ($group === UserInterface::GROUP_MANAGER || $group === UserInterface::GROUP_ADMIN))
                ) {
                    return $handler->handle($request);
                } else {
                    return $this->responseFactory->createResponse(403);// todo add message
                }
            }
            return $this->responseFactory->createResponse(403);// todo add message
        } else {
            return $handler->handle($request);
        }
    }

}