<?php

declare(strict_types=1);

namespace Symbiotic\View\Blade;

use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Packages\TemplateCompiler;


class Bootstrap implements \Symbiotic\Core\BootstrapInterface
{

    public function bootstrap(DIContainerInterface $core): void
    {
        $core->afterResolving(TemplateCompiler::class, function (TemplateCompiler $compiler) {
            $compiler->addCompiler(new Blade());;
        });
    }
}
