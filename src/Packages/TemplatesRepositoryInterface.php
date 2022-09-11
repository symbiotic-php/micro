<?php

declare(strict_types=1);

namespace Symbiotic\Packages;

interface TemplatesRepositoryInterface
{
    /**
     * @param string $package_id
     * @param string $path
     *
     * @return string A line of php code to execute in eval or save to a file
     *
     * @throws \Exception|ResourceExceptionInterface
     *
     * @uses \Symbiotic\Packages\ResourcesRepositoryInterface::getResourceFileStream()
     *
     * @see  \Symbiotic\Packages\TemplateCompiler
     */
    public function getTemplate(string $package_id, string $path): string;
}