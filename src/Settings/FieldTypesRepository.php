<?php

declare(strict_types=1);

namespace Symbiotic\Settings;


use Symbiotic\Core\SymbioticException;

/**
 * Class FieldTypesRepository
 * @package Symbiotic\Settings
 *
 * To add fields, subscribe to an event in the event('FieldTypesRepository::class','\My\HandlerObject') kernel
 */
class FieldTypesRepository
{
    protected array $types = [];

    /**
     * @param string   $type     the field type must include the application prefix: filesystems::path
     * @param \Closure $callback Should return the html code with the field
     *
     * @see     render()
     * @example function(array $field, $value = null):string {return '<code>';}
     */
    public function add(string $type, \Closure $callback): void
    {
        $this->types[$type] = $callback;
    }

    /**
     * @param array                      $field
     * @param null|string|int|bool|mixed $value If null , then the value has not been set , this is to use the default
     *                                          value .
     *
     * @return string
     */
    public function render(array $field, mixed $value = null): string
    {
        $type = $field['type'];
        if ($this->has($field['type'])) {
            $callback = $this->types[$field['type']];
            return $callback($field, $value);
        }
        throw  new SymbioticException('Field type [' . $type . '] not found!');
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function has(string $type): bool
    {
        return isset($this->types[$type]);
    }

}