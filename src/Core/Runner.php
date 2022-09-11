<?php
declare(strict_types=1);

namespace Symbiotic\Core;

abstract class Runner implements RunnerInterface
{
    /**
     * @var CoreInterface
     */
    protected CoreInterface $core;

    /**
     * @param CoreInterface $container
     */
    public function __construct(CoreInterface $container)
    {
        $this->core = $container;
    }

}