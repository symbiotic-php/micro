<?php

declare(strict_types=1);

namespace Symbiotic\Settings;

use Symbiotic\Container\ArrayAccessTrait;
use Symbiotic\Container\ItemsContainerTrait;
use Symbiotic\Container\MultipleAccessTrait;


class Settings implements SettingsInterface
{
    use ItemsContainerTrait,
        ArrayAccessTrait,
        MultipleAccessTrait;

    /**
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

}