<?php

declare(strict_types=1);

namespace Symbiotic\Core\Events;

class CacheClear
{

    protected ?string $path = null;

    public function __construct(string $path = null)
    {
        $this->path = trim($path);
    }

    public function getPath(): ?string
    {
        return $this->path;
    }
}