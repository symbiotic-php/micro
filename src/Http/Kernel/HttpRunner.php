<?php
declare(strict_types=1);

namespace Symbiotic\Http\Kernel;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Symbiotic\Core\{HttpKernelInterface, Runner};
use Symbiotic\Http\{PsrHttpFactory, ResponseSender, UriHelper};


class HttpRunner extends Runner
{

    /**
     * @var string
     * @see prepareBaseUrl()
     */
    protected string $public_path = '';

    /**
     * @return bool
     */
    public function isHandle(): bool
    {
        return $this->core['env'] === 'web';
    }

    /**
     * @param ServerRequestInterface|null $global_request
     *
     * @return bool
     * @throws \Symbiotic\Container\BindingResolutionException
     * @throws \Symbiotic\Container\NotFoundException
     */
    public function run(ServerRequestInterface $global_request = null): bool
    {

        $core = $this->core;
        $symbiosis = $core('config::symbiosis', true);

        $core['original_request'] = $global_request = $global_request?:$core[PsrHttpFactory::class]->createServerRequestFromGlobals();
        $http_kernel = $this->getHttpKernel();
        try {
            // deleting the path to the script
            $base_uri = $this->prepareBaseUrl($global_request);
            $core['base_uri'] = $base_uri;
            $core['public_path'] = $this->public_path;

            $local_request = $global_request->withUri(
                (new UriHelper())->deletePrefix($base_uri, $global_request->getUri())
            );

            $response = $http_kernel->handle($local_request);

            // Determining whether to give an answer
            if (!$core('destroy_response', false) || !$symbiosis) {
                $this->sendResponse($response);
                $http_kernel->terminate($global_request);
                // in symbiosis mode, we do not allow other scripts to continue working, because they gave our answer
                return true;
            } else {
                $http_kernel->terminate($global_request);
            }
        } catch (\Throwable $e) {
            // in symbiosis mode, we do not give an answer with an error, we will write it in the log
            if (!$symbiosis) {
                $this->sendResponse($response = $http_kernel->response(500, $e));
                $http_kernel->terminate($global_request, $response);
                return true;
            } else {
                $http_kernel->terminate($global_request);
            }
        }
        return false;
    }


    /**
     * @return HttpKernelInterface
     * @throws \Symbiotic\Container\BindingResolutionException
     * @throws \Symbiotic\Container\NotFoundException
     */
    protected function getHttpKernel(): HttpKernelInterface
    {
        return $this->core->make(HttpKernelInterface::class);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    protected function prepareBaseUrl(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();
        $baseUrl = '/';
        $app = $this->core;
        if (PHP_SAPI !== 'cli') {
            foreach (['PHP_SELF', 'SCRIPT_NAME', 'ORIG_SCRIPT_NAME'] as $v) {
                $value = $server[$v];

                if (!empty($value) && basename($value) === basename($server['SCRIPT_FILENAME'])) {
                    //  $this->file = basename($value);
                    $this->public_path = str_replace($value, '', $server['SCRIPT_FILENAME']);
                    $request_uri = $request->getUri()->getPath();
                    $value = '/' . ltrim($value, '/');
                    if ($request_uri === preg_replace('~^' . preg_quote($value, '~') . '~i', '', $request_uri)) {
                        if (is_null($app('mod_rewrite'))) {
                            $app['mod_rewrite'] = true;
                        }
                        $value = dirname($value);
                    }
                    $baseUrl = $value;
                    break;
                }
            }
        }

        return rtrim($baseUrl, '/\\');
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function sendResponse(ResponseInterface $response):void
    {
        $sender = new ResponseSender($response);
        $sender->render();
        if (\function_exists('fastcgi_finish_request')) {
            \register_shutdown_function(function () {
                \fastcgi_finish_request();
            });
        } elseif (!\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            static::closeOutputBuffers(0, true);
        }
    }

    /**
     * Laravel close buffers
     * @param int $targetLevel
     * @param bool $flush
     */
    public static function closeOutputBuffers(int $targetLevel, bool $flush): void
    {
        $status = \ob_get_status(true);
        $level = \count($status);
        $flags = \PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? \PHP_OUTPUT_HANDLER_FLUSHABLE : \PHP_OUTPUT_HANDLER_CLEANABLE);

        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }


}