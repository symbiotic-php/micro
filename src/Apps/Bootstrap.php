<?php

declare(strict_types=1);

namespace Symbiotic\Apps;

use Symbiotic\Container\DIContainerInterface;
use Symbiotic\Core\AbstractBootstrap;
use Symbiotic\Event\ListenersInterface;
use Symbiotic\Packages\PackagesRepositoryInterface;
use Symbiotic\Routing\AppsRoutesRepository;


class Bootstrap extends AbstractBootstrap
{
    public function bootstrap(DIContainerInterface $core): void
    {
        $core->bind(AppConfigInterface::class, AppConfig::class);
        $core->bind(ApplicationInterface::class, Application::class);

        // todo: it takes 3.5 m s with 700 packets, you can write to a file, it will be less
        $core->singleton(
            AppsRepositoryInterface::class,
            static function ($core) {
                $apps_repository = new AppsRepository($core);
                foreach ($core[PackagesRepositoryInterface::class]->all() as $config) {
                    $app_c = $config['app'] ?? null;
                    if (is_array($app_c)) {
                        $apps_repository->addApp($app_c);
                    }
                }
                return $apps_repository;
            },
            'apps'
        );

        /**
         * @used-by  \Symbiotic\Routing\Provider::boot()
         * or
         * @used-by  \Symbiotic\Routing\SettlementsRoutingProvider::register()
         */
        $core->get(ListenersInterface::class)->add(
            AppsRoutesRepository::class,
            function (AppsRoutesRepository $event, AppsRepositoryInterface $appsRepository) {
                foreach ($appsRepository->enabled() as $v) {
                    $providerCLass = $v['routing'] ?? null;
                    if ($providerCLass) {
                        $event->append($v['id'], $providerCLass, $v['controllers_namespace']??null);
                    }
                }
            }
        );
    }
}
