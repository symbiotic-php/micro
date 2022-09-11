<?php

declare(strict_types=1);

namespace Symbiotic\Packages;

use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Symbiotic\Core\CoreInterface;
use Symbiotic\Mimetypes\MimeTypesMini;


class AssetFileMiddleware implements MiddlewareInterface
{

    /**
     * The base directory for intercepting requests
     *
     * @var string
     */
    protected string $path;


    /**
     * @param string                    $path            The base directory for intercepting requests
     * @param AssetsRepositoryInterface $resources       Packages File Repository
     * @param ResponseFactoryInterface  $responseFactory Response Factory
     * @param CoreInterface             $container       Core Container for write `destroy_response` flag
     */
    public function __construct(
        string $path,
        protected CoreInterface $container,
        protected AssetsRepositoryInterface $resources,
        protected ResponseFactoryInterface $responseFactory
    ) {
        $this->path = $path;
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pattern = '~^' . preg_quote(trim($this->path, '/'), '~') . '/(.[^/]+)(.+)~i';

        $assets_repository = $this->resources;
        // we check the correspondence of the path
        if (preg_match($pattern, ltrim($request->getUri()->getPath(), '/'), $match)) {
            $responseFactory = $this->responseFactory;
            try {
                $file = $assets_repository->getAssetFileStream($match[1], $match[2]);
                $mime_types = new MimeTypesMini();
                $mime = $mime_types->getMimeType($match[2]);
                if (!$mime) {
                    $mime = 'text/plain';
                }
                return $responseFactory->createResponse(200)
                    ->withBody($file)
                    ->withHeader('content-type', $mime)
                    ->withHeader('Cache-Control', 'max-age=86400')
                    ->withHeader('content-length', $file->getSize());
            } catch (\Throwable $e) {
                // if a file is requested, but it cannot be returned, we will return an error response
                $this->container->instance('destroy_response', true);
                return $responseFactory->createResponse($e instanceof ResourceExceptionInterface ? 404 : 500);
            }
        }

        return $handler->handle($request);
    }
}