<?php

declare(strict_types=1);

namespace Symbiotic\Container;


class Container implements DIContainerInterface
{
    use MethodBindingsTrait,
        ContainerTrait;

    public function __clone()
    {
        foreach ($this->instances as $k => $instance) {
            if ($instance instanceof CloningContainer && ($newService = $instance->cloneInstance($this))) {
                $this->instances[$k] = $newService;
            } elseif ($instance instanceof $this) {
                $this->instances[$k] = $this;
            }
        }
    }
}
