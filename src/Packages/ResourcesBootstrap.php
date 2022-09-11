<?php

declare(strict_types=1);

namespace Symbiotic\Packages;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Core\AbstractBootstrap;
use Symbiotic\Core\CoreInterface;
use Symbiotic\Http\Kernel\PreloadKernelHandler;

class ResourcesBootstrap extends AbstractBootstrap
{

    /**
     * @param CoreInterface $core
     */
    public function bootstrap(DIContainerInterface $core): void
    {
        $core->singleton(TemplateCompiler::class);
        $res_interface = ResourcesRepositoryInterface::class;
        $core->alias($res_interface, TemplatesRepositoryInterface::class);
        $core->alias($res_interface, AssetsRepositoryInterface::class);

        $core->singleton(
            $res_interface,
            static function ($core) {
                /**
                 * @var ResourcesRepositoryInterface $repository
                 * @var PackagesRepositoryInterface  $packages_repository
                 */
                return new ResourcesRepository(
                    $core[TemplateCompiler::class],
                    $core[StreamFactoryInterface::class],
                    $core[PackagesRepositoryInterface::class]
                );
            },
            'resources'
        );

        $core['listeners']->add(
            PreloadKernelHandler::class,
            static function (PreloadKernelHandler $event, CoreInterface $core) {
                $event->prepend(
                    new AssetFileMiddleware(
                        $core('config::assets_prefix', 'assets'),
                        $core,
                        $core['resources'],
                        $core[ResponseFactoryInterface::class],

                    )
                );
            }
        );
    }
}