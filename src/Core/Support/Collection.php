<?php

declare(strict_types=1);

namespace Symbiotic\Core\Support;


/**
 * Class Collection
 * @package Symbiotic\Core\Support
 *
 * @see     https://laravel.com/docs/9.x/collections
 */
class Collection implements
    \ArrayAccess,
    ArrayableInterface,
    JsonableInterface,
    \Countable,
    \IteratorAggregate,
    \JsonSerializable
{
    use CollectionTrait;
}
