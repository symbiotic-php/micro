<?php

declare(strict_types=1);

namespace Symbiotic\Session;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symbiotic\Http\Cookie\CookiesInterface;


class SessionMiddleware implements MiddlewareInterface
{

    public function __construct(protected ContainerInterface $container)
    {
    }


    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
         * @var SessionManagerInterface $sessionManager
         * @var SessionStorageInterface $session
         * @var CookiesInterface        $cookieService
         */
        $sessionManager = $this->container->get(SessionManagerInterface::class);
        $config = $sessionManager->getConfig();
        $cookies = $request->getCookieParams();

        $session = $this->container->get(SessionStorageInterface::class);


        if (isset($cookies[$config['name']])) {
            $session->setId($cookies[$config['name']]);
        }
        $response = $handler->handle($request);
        if ($session->isStarted() && $session->isUpdated()) {
            ///todo: delete empty session ? ($session->isEmpty()) {} need a test with a symbiosis mode
            // todo: update timestamp
            $cookieService = $this->container->get(CookiesInterface::class);
            $session->save();
            $cookieService->setCookie(
                $config['name'],
                $session->getId(),
                time() + ($config['minutes'] * 60),
                (bool)$config['httponly'] ?? true,
                $config['secure'] ?? false,
                null,
                null,
                isset($config['same_site']) ? ['same_site' => $config['same_site']] : []
            );
        }

        return $response;
    }
}