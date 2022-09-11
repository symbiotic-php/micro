<?php

declare(strict_types=1);

namespace Symbiotic\Packages;


interface ResourceExceptionInterface extends \Throwable
{
    public function getPath(): string;
}


