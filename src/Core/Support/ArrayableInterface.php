<?php

declare(strict_types=1);

namespace Symbiotic\Core\Support;

interface ArrayableInterface
{
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array;
}