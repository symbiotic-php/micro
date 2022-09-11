<?php
declare(strict_types=1);

namespace Symbiotic\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Trait DeepGetter
 * @package Symbiotic\Container
 */
trait DeepGetterTrait /* implements ContainerInterface*/
{

    private string $deep_delimiter = '::';

    /**
     * A less strict method of getting data from the container, if the key does not exist, will return NULL by default
     *
     * @param string $key
     * @param null|mixed $default
     * @return mixed
     * @throws
     */
    public function __invoke(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    /**
     * @param string $key - It is possible to use access inside the object through a point,
     * if the object uses {@see\ArrayAccess,\Psr\Container\ContainerInterface}
     * For example: 'config::providers' will return an array of providers from the \Symbiotic\Config object
     *
     * @return mixed
     *
     * @throws NotFoundExceptionInterface|BindingResolutionException|\Exception
     *
     */
    public function get(string $key):mixed
    {
        /**
         * @var DIContainerInterface|DeepGetterTrait $this
         */
        $key = (!str_contains($key, $this->deep_delimiter)) ? $key : \explode($this->deep_delimiter, $key);
        try {
            if (\is_array($key)) {
                $c = $key[0];
                $k = $key[1];
                $service = $this->make($c);
                if (\is_array($service)) {
                    if (isset($service[$k]) || \array_key_exists($k, $service)) {// isset 4x fast
                        return $service[$k];
                    }
                } elseif ($service instanceof ContainerInterface || $service instanceof BaseContainerInterface) {
                    if ($service->has($k)) {
                        return $service->get($k);
                    }
                } elseif ($service instanceof \ArrayAccess && $service->offsetExists($k)) {
                    return $service->offsetGet($k);
                }
                throw new NotFoundException($k, $service);
            }
            try {
                return $this->make($key);
            } catch (\Throwable $e) {
                $has = $this->has($key);
                if (!$has || (get_class($e) === 'Exception')) {
                    if ($has) {
                        throw $e;
                    } else {
                        throw new NotFoundException($key, $this, 4004, $e);
                    }
                }
            }
        } catch (ContainerException $e) {
            if ($e instanceof NotFoundExceptionInterface && \func_num_args() === 2) {
                return \func_get_arg(1);
            }
            throw $e;
        }
        return null;
    }
}
