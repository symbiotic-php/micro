<?php
declare(strict_types=1);

namespace Symbiotic\Core;

use Symbiotic\Container\DIContainerInterface;


interface BootstrapInterface
{
    /**
     * @param DIContainerInterface $core
     *
     * @return void
     */
    public function bootstrap(DIContainerInterface $core) : void;
}