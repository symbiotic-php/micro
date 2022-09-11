<?php

declare(strict_types=1);

namespace Symbiotic\Core;

use Symbiotic\Container\{DIContainerInterface, ServiceContainerInterface};

/**
 * Interface CoreInterface
 * @package Symbiotic\Contracts
 */
interface CoreInterface extends DIContainerInterface, ServiceContainerInterface
{
    /**
     * @param string | string[] $bootstraps class name implemented {@see BootstrapInterface, AbstractBootstrap}
     *
     * @return void
     */
    public function addBootstraps(string|array $bootstraps): void;

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted(): bool;

    /**
     * Initializes the core of the framework
     *
     * @used-by  HttpKernelInterface::bootstrap()
     * @used-by  \Symbiotic\Http\Kernel\HttpKernel::bootstrap()
     */
    public function bootstrap(): void;

    /**
     * Bootstrap launch
     *
     * @param string $class
     */
    public function runBootstrap(string $class): void;

    /**
     *
     * @param RunnerInterface $runner
     * @param int             $priority
     * @param string|null     $name
     *
     * @return void
     */
    public function addRunner(RunnerInterface $runner, int $priority = 1, string $name = null): void;

    public function run(): void;

    /**
     * If an application is defined to process the request
     * then a function will be executed in which you can connect files
     *
     * @param \Closure $loader
     */
    public function beforeHandle(\Closure $loader): void;

    /**
     * Event before request processing starts
     *
     * @used-by CoreInterface::run()
     */
    public function runBefore(): void;

    /**
     * Triggering the event after successful processing of the request by the framework
     *
     * @used-by CoreInterface::run()
     */
    public function runComplete(): void;


    /**
     * @used-by CoreInterface::runComplete()
     *
     * @param \Closure $complete
     */
    public function onComplete(\Closure $complete): void;

    /**
     *  Event after unsuccessful processing of the framework request
     *
     * @param \Closure $loader
     */
    public function then(\Closure $loader): void;

    /**
     * Runs scripts after the framework, after the unsuccessful completion of the framework
     *
     * @used-by Core::run()
     */
    public function runNext(): void;

    /**
     * Get the base path of the Laravel installation.
     *
     * @param string $path Optionally, a path to append to the base path
     *
     * @return string
     *
     * @todo: The method is used once, is it needed?
     */
    public function getBasePath(string $path = ''): string;
}
