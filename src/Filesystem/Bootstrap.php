<?php
declare(strict_types=1);

namespace Symbiotic\Filesystem;

use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Core\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    public function bootstrap(DIContainerInterface $core): void
    {
        $core->singleton(FilesystemManagerInterface::class, static function ($app) {
            return new FilesystemManager($app);
        },'files');
    }
}