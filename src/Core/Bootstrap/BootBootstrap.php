<?php

declare(strict_types=1);

namespace Symbiotic\Core\Bootstrap;

use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Core\BootstrapInterface;


class BootBootstrap implements BootstrapInterface
{
    /**
     * @param \Symbiotic\Container\ServiceContainerInterface|\Symbiotic\Core\CoreInterface $app
     */
    public function bootstrap(DIContainerInterface $core): void
    {
        $core->boot();
    }
}