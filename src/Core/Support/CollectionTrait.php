<?php

declare(strict_types=1);

namespace Symbiotic\Core\Support;

use Symbiotic\Container\ArrayAccessTrait;
use Symbiotic\Container\MagicAccessTrait;
use \Traversable;
use \ArrayIterator;


trait CollectionTrait
{
    use ArrayAccessTrait;
    use MagicAccessTrait;

    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected array $items = [];

    /**
     * Create a new collection.
     *
     * @param mixed $items
     *
     * @return void
     */
    public function __construct(mixed $items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @param mixed $items
     *
     * @return static
     */
    public static function create(mixed $items = []): static
    {
        return new static($items);
    }

    /**
     * Wrap the given value in a collection if applicable.
     *
     * @param mixed $value
     *
     * @return static
     */
    public static function wrap(mixed $value): static
    {
        return new static($value instanceof self ? $value : Arr::wrap($value));
    }

    /**
     * Get the underlying items from the given collection if applicable.
     *
     * @param array|static $value
     *
     * @return array
     */
    public static function unwrap(mixed $value): array
    {
        return $value instanceof self ? $value->all() : $value;
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Run a filter over each of the items.
     *
     * @param callable|null $callback
     *
     * @return static
     */
    public function filter(callable $callback = null): static
    {
        return new static(
            $callback ?
                Arr::where($this->items, $callback)
                : array_filter($this->items)
        );
    }

    /**
     * Apply the callback if the value is truthy.
     *
     * @param bool          $value
     * @param callable      $callback
     * @param callable|null $default
     *
     * @return mixed
     */
    public function when(bool $value, callable $callback, callable $default = null): mixed
    {
        if ($value) {
            return $callback($this, $value);
        } elseif ($default) {
            return $default($this, $value);
        }

        return $this;
    }

    /**
     * Get an operator checker callback.
     *
     * @param string      $key
     * @param string|null $operator
     * @param mixed       $value
     *
     * @return \Closure
     */
    protected function operatorForWhere(string $key, string $operator = null, mixed $value = null): \Closure
    {
        $args = func_num_args();
        if ($args < 3) {
            $value = $args < 2 ? true : $operator;
            $operator = '=';
        }

        return function ($item) use ($key, $operator, $value) {
            $retrieved = \_S\data_get($item, $key);

            $strings = array_filter([$retrieved, $value], function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }

            switch ($operator) {
                default:
                case '=':
                case '==':
                    return $retrieved == $value;
                case '!=':
                case '<>':
                    return $retrieved != $value;
                case '<':
                    return $retrieved < $value;
                case '>':
                    return $retrieved > $value;
                case '<=':
                    return $retrieved <= $value;
                case '>=':
                    return $retrieved >= $value;
                case '===':
                    return $retrieved === $value;
                case '!==':
                    return $retrieved !== $value;
            }
        };
    }

    /**
     * Get an item from the collection by key.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->items, $key, $default);
    }

    /**
     * Get the first item from the collection.
     *
     * @param callable|null $callback
     * @param mixed         $default
     *
     * @return mixed
     */
    public function first(callable $callback = null, mixed $default = null): mixed
    {
        return Arr::first($this->items, $callback, $default);
    }

    /**
     * Get the first item from the collection.
     *
     * @param callable|null $callback
     * @param mixed         $default
     *
     * @return mixed
     */
    public function last(callable $callback = null, mixed $default = null): mixed
    {
        return Arr::last($this->items, $callback, $default);
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    /**
     * Set the item at a given offset.
     *
     * @param string|null $key
     * @param mixed       $value
     *
     * @return void
     */
    public function set(string|null $key, mixed $value): void
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            Arr::set($this->items, $key, $value);
        }
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param array|string $keys
     *
     * @return void
     */
    public function remove(array|string $keys): void
    {
        Arr::forget($this->items, $keys);
    }


    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Determine if the given value is callable, but not a string.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function useAsCallable(mixed $value): bool
    {
        return !is_string($value) && is_callable($value);
    }

    /**
     * Get the keys of the collection items.
     *
     * @return static
     */
    public function keys(): static
    {
        return new static(array_keys($this->items));
    }


    /**
     * Run a map over each of the items.
     *
     * @param callable $callback
     * @param bool     $replace_keys - use 2 param in your callback f($item, $key){return [$item, $key]}
     *
     * @return static
     */
    public function map(callable $callback, bool $replace_keys = false): static
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        if ($replace_keys) {
            $tmp = [];
            foreach ($items as $item) {
                $tmp[$item[1]] = $item[0];
            }
            return new static($tmp);
        }

        return new static(array_combine($keys, $items));
    }


    /**
     * Transform each item in the collection using a callback.
     *
     * @param callable $callback
     *
     * @return static
     */
    public function transform(callable $callback): static
    {
        $this->items = $this->map($callback)->all();

        return $this;
    }

    /**
     * Map the values into a new class.
     *
     * @param string $class
     *
     * @return static
     */
    public function mapInto(string $class): static
    {
        return $this->map(function ($value, $key) use ($class) {
            return new $class($value, $key);
        });
    }

    /**
     * Merge the collection with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function merge(mixed $items): static
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * @param mixed $values
     *
     * @return static
     */
    public function combine(mixed $values): static
    {
        return new static(array_combine($this->all(), $this->getArrayableItems($values)));
    }

    /**
     * Union the collection with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function union(mixed $items): static
    {
        return new static($this->items + $this->getArrayableItems($items));
    }

    /**
     * Get and remove the last item from the collection.
     *
     * @return mixed
     */
    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    /**
     * Push an item onto the beginning of the collection.
     *
     * @param mixed $value
     * @param mixed $key
     *
     * @return $this
     */
    public function prepend(mixed $value, string $key = null): static
    {
        $this->items = Arr::prepend($this->items, $value, $key);

        return $this;
    }

    /**
     * Add an item to the collection.
     *
     * @param mixed $item
     *
     * @return $this
     */
    public function add(mixed $item): static
    {
        $this->set(null, $item);

        return $this;
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function push(mixed $value): static
    {
        return $this->add($value);
    }


    /**
     * Get and remove an item from the collection.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        return Arr::pull($this->items, $key, $default);
    }

    /**
     * Put an item in the collection by key.
     *
     * @param string|null $key
     * @param mixed       $value
     *
     * @return $this
     */
    public function put(null|string $key, mixed $value): static
    {
        $this->set($key, $value);

        return $this;
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param mixed $value
     * @param bool  $strict
     *
     * @return string|int|false
     */
    public function search(mixed $value, bool $strict = false): string|int|false
    {
        if (!$this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }

        foreach ($this->items as $key => $item) {
            if (call_user_func($value, $item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param callable|mixed $callback
     *
     * @return static
     */
    public function reject(mixed $callback): static
    {
        if ($this->useAsCallable($callback)) {
            return $this->filter(function ($value, $key) use ($callback) {
                return !$callback($value, $key);
            });
        }

        return $this->filter(function ($item) use ($callback) {
            return $item != $callback;
        });
    }

    /**
     * Get and remove the first item from the collection.
     *
     * @return mixed
     */
    public function shift(): mixed
    {
        return array_shift($this->items);
    }

    /**
     * Split a collection into a certain number of groups.
     *
     * @param int $numberOfGroups
     *
     * @return static
     */
    public function split(int $numberOfGroups): static
    {
        if ($this->isEmpty()) {
            return new static;
        }

        $groups = new static;

        $groupSize = floor($this->count() / $numberOfGroups);

        $remain = $this->count() % $numberOfGroups;

        $start = 0;

        for ($i = 0; $i < $numberOfGroups; $i++) {
            $size = $groupSize;

            if ($i < $remain) {
                $size++;
            }

            if ($size) {
                // todo: test float slice
                $groups->push(new static(array_slice($this->items, $start, (int)$size)));

                $start += $size;
            }
        }

        return $groups;
    }

    /**
     * Chunk the underlying collection array.
     *
     * @param int $size
     *
     * @return static
     */
    public function chunk(int $size): static
    {
        if ($size <= 0) {
            return new static;
        }

        $chunks = [];

        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * Slice the underlying collection array.
     *
     * @param int      $offset
     * @param int|null $length
     *
     * @return static
     */
    public function slice(int $offset, int $length = null): static
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }


    /**
     * Sort through each item with a callback.
     *
     * @param callable|null $callback
     *
     * @return static
     */
    public function sort(callable $callback = null): static
    {
        $items = $this->items;

        $callback
            ? uasort($items, $callback)
            : asort($items);

        return new static($items);
    }

    /**
     * Return only unique items from the collection array.
     *
     * @param string|callable|null $key
     * @param bool                 $strict
     *
     * @return static
     */
    public function unique(mixed $key = null, bool $strict = false): static
    {
        $callback = $this->valueRetriever($key);

        $exists = [];

        return $this->reject(function ($item, $key) use ($callback, $strict, &$exists) {
            if (in_array($id = $callback($item, $key), $exists, $strict)) {
                return true;
            }

            $exists[] = $id;
        });
    }

    /**
     * Sort the collection keys.
     *
     * @param int  $options
     * @param bool $descending
     *
     * @return static
     */
    public function sortKeys(int $options = SORT_REGULAR, bool $descending = false): static
    {
        $items = $this->items;

        $descending ? krsort($items, $options) : ksort($items, $options);

        return new static($items);
    }

    /**
     * Get a value retrieving callback.
     *
     * @param callable|string|null $value
     *
     * @return callable
     */
    protected function valueRetriever(mixed $value): callable
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            return \_S\data_get($item, $value);
        };
    }

    /**
     * Pad collection to the specified length with a value.
     *
     * @param int   $size
     * @param mixed $value
     *
     * @return static
     */
    public function pad(int $size, mixed $value): static
    {
        return new static(array_pad($this->items, $size, $value));
    }

    /**
     * Reverse items order.
     *
     * @return static
     */
    public function reverse(): static
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_map(function ($value) {
            return $value instanceof ArrayableInterface ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return array_map(function ($value) {
            if ($value instanceof \JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof JsonableInterface) {
                return \json_decode($value->toJson(), true);
            } elseif ($value instanceof ArrayableInterface) {
                return $value->toArray();
            }

            return $value;
        }, $this->items);
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return \json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get an iterator for the items.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }


    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Results array of items from Collection or Arrayable.
     *
     * @param mixed $items
     *
     * @return array
     */
    protected function getArrayableItems(mixed $items): array
    {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof self) {
            return $items->all();
        } elseif ($items instanceof ArrayableInterface) {
            return $items->toArray();
        } elseif ($items instanceof JsonableInterface) {
            return \json_decode($items->toJson(), true);
        } elseif ($items instanceof \JsonSerializable) {
            return $items->jsonSerialize();
        } elseif ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array)$items;
    }
}
