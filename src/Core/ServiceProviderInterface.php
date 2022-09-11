<?php

declare(strict_types=1);

namespace Symbiotic\Core;


interface ServiceProviderInterface
{

    /**
     * @return void
     */
    public function register(): void;

    /**
     * @return void
     */
    public function boot(): void;

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
    public function bindings(): array;

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
    public function singletons(): array;

    /**
     * Array of service aliases
     *
     * @return string[]
     *
     * [abstract => alias,...]
     */
    public function aliases(): array;
}