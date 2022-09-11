<?php

declare(strict_types=1);

namespace Symbiotic\Core;

use Symbiotic\Container\DIContainerInterface;


class  ServiceProvider implements ServiceProviderInterface
{
    /**
     * @var DIContainerInterface| [ 'config' => new \Symbiotic\Config() ]
     */
    protected $app = null;

    public function __construct(DIContainerInterface $app)
    {
        $this->app = $app;
    }

    /**
     * @return void
     */
    public function register(): void
    {
    }

    /**
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Returns an array of bindings
     *
     * You can describe this method to massively return factory methods to create for objects
     * return [
     *      ClassName::class => function($dependencies){return new ClassName();},
     *      TwoClass:class   => function(Config $data){return new TwoClass($data);},
     * ]
     *
     * @return array| \Closure[]
     */
    public function bindings(): array
    {
        return [];
    }

    /**
     * Returns an array of singleton bindings
     *
     * You can describe this method to massively return factory methods to create for objects
     * return [
     *      ClassName::class => function($dependencies){return new ClassName();},
     *      TwoClass:class   => function($data){return new TwoClass($data);},
     * ]
     *
     * @return string[]| \Closure[]
     */
    public function singletons(): array
    {
        return [];
    }

    /**
     * Array of service aliases
     *
     * @return string[]
     *
     * [abstract => alias,...]
     */
    public function aliases(): array
    {
        return [];
    }


}