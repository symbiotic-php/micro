<?php

declare(strict_types=1);

namespace Symbiotic\Packages;


interface PackagesLoaderInterface
{
    /**
     * @param PackagesRepositoryInterface $repository
     *
     * @return void
     */
    public function load(PackagesRepositoryInterface $repository): void;
}
