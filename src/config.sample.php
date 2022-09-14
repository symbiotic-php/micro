<?php

$basePath = dirname(__DIR__, 3);

return [
    'debug' => false,
    // Symbiosis mode, if enabled and the framework does not find a handler,
    // then it will not return anything and the main framework will be able to process the request itself
    'symbiosis' => true,

    // The base domain for routing in console mode
    'default_host' => 'localhost',

    // The prefix in which the framework works, if empty, it works from the root
    'uri_prefix' => 'symbiotic',

    // Admin prefix, full admin uri = uri_prefix/backend_prefix
    'backend_prefix' => 'admin',

    // Root folder of the project
    'base_path' => $basePath,
    // URL prefix for static file addresses
    'assets_prefix' => '/assets',
    // The root directory of the cache, if you remove it, the cache will be disabled
    'storage_path' => $basePath . '/storage',
    // resolves packet addresses by application ID /FRAMEWORK_ROOT/APP_ID/ROUTE
    'packages_settlements' => true,

    'packages_paths' => [
        $basePath . '/vendor', // Folder for applications
    ],
    // Session settings
    'session' => [
        'driver' => 'native',
        /** {@uses \Symbiotic\Session\SessionStorageNative,\Symbiotic\Session\SessionManager::createNativeDriver()} **/
        'secure' => false,
        'httponly' => true,
        'same_site' => null,
    ],
    /**
     * The order in which bootstraps are connected matters
     */
    'bootstrappers' => [
        \Symbiotic\Core\Bootstrap\EventBootstrap::class,
        \Symbiotic\Filesystem\Bootstrap::class,
        \Symbiotic\Cache\Bootstrap::class,
        \Symbiotic\Settings\SettingsBootstrap::class,
        \Symbiotic\Packages\Loader\PackagesLoaderFilesystemBootstrap::class,
        \Symbiotic\Packages\PackagesBootstrap::class,
        \Symbiotic\Packages\ResourcesBootstrap::class,
        \Symbiotic\Apps\Bootstrap::class,
        \Symbiotic\Http\Bootstrap::class,
        \Symbiotic\Http\Kernel\Bootstrap::class,
        \Symbiotic\View\Blade\Bootstrap::class,
    ],
    // Core providers
    'providers' => [
        \Symbiotic\Session\SessionProvider::class,
        \Symbiotic\Auth\Provider::class,
        \Symbiotic\Routing\Provider::class,
    ]
];