<?php

declare(strict_types=1);

namespace Symbiotic\Cache;

use Symbiotic\Core\SymbioticException;


class CacheException extends SymbioticException implements \Psr\SimpleCache\CacheException
{

}

