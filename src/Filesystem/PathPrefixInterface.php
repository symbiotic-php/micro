<?php
declare(strict_types=1);

namespace Symbiotic\Filesystem;


interface PathPrefixInterface
{
    public function setPathPrefix($path);

    public function getPathPrefix();

    public function applyPathPrefix($path);

    public function removePathPrefix($path);
}
