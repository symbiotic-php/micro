<?php

declare(strict_types=1);

namespace Symbiotic\Http\Kernel;

use Symbiotic\Core\CoreInterface;
use Symbiotic\Core\HttpKernelInterface;
use Symbiotic\View\ViewFactory;
use Symbiotic\Http\Middleware\MiddlewareCallback;
use Symbiotic\Http\Middleware\MiddlewaresCollection;
use Symbiotic\Http\Middleware\MiddlewaresDispatcher;
use Symbiotic\Http\Middleware\MiddlewaresHandler;
use Symbiotic\Http\Middleware\RequestPrefixMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;


class HttpKernel implements HttpKernelInterface
{
    /**
     * @var CoreInterface
     */
    protected CoreInterface $core;
    /**
     * @var string[]  Names of classes implements from {@uses \Symbiotic\Core\BootstrapInterface}
     */
    protected array $bootstrappers = [];

    /**
     * @var bool|null
     */
    protected ?bool $mod_rewrite = null;


    public function __construct(CoreInterface $container)
    {
        $this->core = $container;
        if ($container->has('config')) {
            $config = $container->get('config');
            $this->mod_rewrite = $config->has('mod_rewrite') ? $config->get('mod_rewrite') : null;
        }
    }

    /**
     * Initializes the kernel
     */
    public function bootstrap(): void
    {
        if (!$this->core->isBooted()) {
            $this->core->addBootstraps($this->bootstrappers);
            $this->core->bootstrap();
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $core = $this->core;

        /**
         * Through the event, you can add intermediaries before loading the Http core and all providers
         * This is convenient when you need to respond quickly, it is recommended to use it in an emergency
         */
        $handler = $core->get(PreloadKernelHandler::class);

        // we put the prefix check at the beginning
        $handler->prepend(
            new RequestPrefixMiddleware(
                $core,
                $core('config::uri_prefix')
            )
        );
        // we put at the end the loading of the Http Core providers.
        $handler->append(
            new MiddlewareCallback(
                function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($core
                ): ResponseInterface {
                    $core->runBefore();
                    if ($handler instanceof MiddlewaresHandler) {
                        $real = $handler->getRealHandler();
                        if ($real instanceof HttpKernelInterface) {
                            $real->bootstrap();
                        }
                    }

                    return (new MiddlewaresCollection(
                        $core->get(MiddlewaresDispatcher::class)
                            ->factoryGroup(MiddlewaresDispatcher::GROUP_GLOBAL)

                    ))->process($request, $core->make(RoutingHandler::class));
                }
            )
        );
        return $handler->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface|null $response
     *
     * @return void
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response = null): void
    {
        $this->core[EventDispatcherInterface::class]->dispatch(
            new HttpKernelTerminate($this->core, $request, $response)
        );
    }

    /**
     * @param int              $code
     * @param \Throwable |null $exception
     *
     * @return ResponseInterface
     */
    public function response(int $code = 200, \Throwable $exception = null): ResponseInterface
    {
        $app = $this->core;
        /**
         * @var ResponseInterface $response
         */
        $response = $app[ResponseFactoryInterface::class]->createResponse($code);
        if ($code >= 400) {
            $path = $app('templates_package', 'ui_http_kernel') . '::';
            if ($exception && $app('config::debug')) {
                $view = $this->core->get(ViewFactory::class)->make($path . "exception", ['error' => $exception]);
            } else {
                $view = $this->core->get(ViewFactory::class)->make($path . "error", ['response' => $response]);
            }
            $response->getBody()->write($view->__toString());
        }

        return $response;
    }

    /**
     * @inheritDoc
     *
     * @param ContainerInterface|null $container
     *
     * @return $this|null
     */
    public function cloneInstance(?ContainerInterface $container): ?object
    {
        /**
         * @var CoreInterface $container
         */
        $new = clone $this;
        $new->core = $container;
        return $new;
    }


}