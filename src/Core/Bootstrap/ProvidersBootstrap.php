<?php

declare(strict_types=1);

namespace Symbiotic\Core\Bootstrap;

use Symbiotic\Core\{AbstractBootstrap, ProvidersRepository};


class ProvidersBootstrap extends AbstractBootstrap
{
    /**
     * @param \Symbiotic\Container\DIContainerInterface | \Symbiotic\Container\ServiceContainerInterface $core
     */
    public function bootstrap($core): void
    {
        $providers_class = ProvidersRepository::class;
        $this->cached($core, $providers_class);
        /**
         * @var ProvidersRepository $providers_repository
         */
        $providers_repository = $core[$providers_class];
        $providers_repository->load(
            $core,
            $core('config::providers', []),
            $core('config::providers_exclude', [])
        );
    }
}