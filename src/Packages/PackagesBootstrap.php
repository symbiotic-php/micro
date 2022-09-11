<?php

declare(strict_types=1);

namespace Symbiotic\Packages;

use Symbiotic\Core\AbstractBootstrap;
use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Event\ListenerProvider;
use Symbiotic\Event\ListenersInterface;


class PackagesBootstrap extends AbstractBootstrap
{
    public function bootstrap(DIContainerInterface $core): void
    {
        $packages_class = PackagesRepositoryInterface::class;

        $core->singleton($packages_class, static function () {
            return new PackagesRepository;
        });


        /**
         * @var PackagesRepositoryInterface $packagesRepository
         */
        $packagesRepository = $core[$packages_class];
        $packagesRepository->load();
        foreach ($packagesRepository->getBootstraps() as $v) {
            if ($v === get_class($this)) {
                continue;
            }
            $core->runBootstrap($v);
        }

        /**
         * @var ListenerProvider $listener
         */
        $listener = $core[ListenersInterface::class];
        foreach ($packagesRepository->getEventsHandlers() as $event => $handlers) {
            foreach ($handlers as $v) {
                $listener->add($event, $v);
            }
        }
    }
}
