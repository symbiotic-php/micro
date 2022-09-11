<?php

declare(strict_types=1);

namespace _S;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symbiotic\Apps\ApplicationInterface;
use Symbiotic\Apps\AppsRepositoryInterface;
use Symbiotic\Container\FactoryInterface;
use Symbiotic\Core\Config;
use Symbiotic\Core\Support\Arr;
use Symbiotic\Core\Support\Collection;
use Symbiotic\Core\Support\Str;
use Symbiotic\Routing\UrlGeneratorInterface;
use Symbiotic\View\View;
use Symbiotic\Core\HttpKernelInterface;
use Symbiotic\Packages\PackageConfig;
use Symbiotic\Packages\PackagesRepositoryInterface;
use Symbiotic\Settings\SettingsInterface;
use Symbiotic\Settings\SettingsRepositoryInterface;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Symbiotic\View\ViewFactory;


const DS = DIRECTORY_SEPARATOR;

if (!function_exists('_S\\app')) {
    /**
     * Возвращает контейнер приложения без инициализации
     * для инициализации используйте метод  {@uses \Symbiotic\Apps\ApplicationInterface::bootstrap()}
     *
     * @param string $id
     *
     * @return ApplicationInterface|null
     * @throws \Psr\Container\ContainerExceptionInterface Если нет сервиса приложений
     */
    function app(ContainerInterface $app, string $id): ?ApplicationInterface
    {
        $apps = $app->get(AppsRepositoryInterface::class);
        return $apps->has($id) ? $apps->get($id) : null;
    }
}

if (!function_exists('_S\\config')) {
    /**
     * Get Config data
     *
     * @param string|null $key
     * @param null        $default
     *
     * @return Config|null|mixed
     */
    function config(ContainerInterface $app, string $key = null, $default = null): mixed
    {
        $config = $app->get('config');
        return is_null($key) ? $config : ($config->has($key) ? $config->get($key) : $default);
    }
}


if (!function_exists('_S\\event')) {
    /**
     * Run event
     *
     * @param object $event
     *
     * @return object $event
     */
    function event(ContainerInterface $app, object $event): object
    {
        /**
         * @uses \Symbiotic\Event\EventDispatcher::dispatch()
         */
        return $app->get(EventDispatcherInterface::class)->dispatch($event);
    }
}


if (!function_exists('_S\\response')) {
    /**
     * @param ContainerInterface $app
     * @param int                $code
     * @param \Throwable|null    $exception
     *
     * @return ResponseInterface
     * @uses \Symbiotic\Core\HttpKernelInterface::response()
     */
    function response(ContainerInterface $app, int $code = 200, \Throwable $exception = null): ResponseInterface
    {
        return $app->get(HttpKernelInterface::class)->response($code, $exception);
    }
}

if (!function_exists('_S\\redirect')) {
    /**
     * @param ContainerInterface $app
     * @param string             $uri
     * @param int                $code
     *
     * @return ResponseInterface
     */
    function redirect(ContainerInterface $app, string $uri, int $code = 301): ResponseInterface
    {
        $response = $app->get(ResponseFactoryInterface::class)->createResponse($code);
        return $response
            ->withHeader('Location', $uri)
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');
    }
}

if (!function_exists('_S\\settings')) {
    /**
     * @param ContainerInterface|FactoryInterface $container
     * @param string                              $package_id
     *
     * @return SettingsInterface
     * Be careful, a new object is always created, it's better to teach $app['settings'] from the application
     */
    function settings(ContainerInterface $container, string $package_id): SettingsInterface
    {
        $settings = $container->make(SettingsInterface::class, [uniqid() => ''/* for create empty object*/]);

        /**
         * Adding the default package settings
         *
         * @var PackageConfig|null $package
         */
        $package = $container->get(PackagesRepositoryInterface::class)->getPackageConfig($package_id);

        if ($package && $package->has('settings')) {
            $default_settings = $package->get('settings');
            $settings->setMultiple(\is_array($default_settings) ? $default_settings : []);
        }

        /**
         * Adding the current settings
         *
         * @var SettingsRepositoryInterface $repository
         */
        $repository = $container->get(SettingsRepositoryInterface::class);
        if ($repository->has($package_id)) {
            $actual_settings = $repository->get($package_id);
            $settings->setMultiple(\is_iterable($actual_settings) ? $actual_settings : []);
        }

        return $settings;
    }
}
if (!function_exists('_S\\listen')) {
    /**󠀄󠀉󠀙󠀙󠀕󠀔󠀁󠀔󠀃󠀅
     *
     * @param ContainerInterface $app
     * @param string             $event   the class name or an arbitrary event name
     *                                    (with an arbitrary name, you need a custom dispatcher not for PSR)
     *
     * @param \Closure|string    $handler function or class name of the handler
     *                                    The event handler class must implement the handle method  (...$params) or __invoke(...$params)
     *                                    <Important:> When adding listeners as class names, you will need to adapt them to \Closure when you return them in the getListenersForEvent() method!!!
     *
     * @return void
     */
    function listen(ContainerInterface $app, string $event, \Closure|string $handler): void
    {
        $app->get('listeners')->add($event, $handler);
    }
}

if (!function_exists('_S\\route')) {
    /**
     * Generate the URL to a named route.
     *
     * @param string $name
     * @param array  $parameters
     * @param bool   $absolute
     *
     * @return string
     *
     * @uses \Symbiotic\Routing\UrlGeneratorInterface::route()
     */
    function route(ContainerInterface $app, string $name, array $parameters = [], bool $absolute = true): string
    {
        return $app->get(UrlGeneratorInterface::class)->route($name, $parameters, $absolute);
    }
}
/**
 * TODO add lang service
 *
 * @param string     $message
 * @param array|null $vars
 * @param null       $lang
 *
 * @return string
 */
function lang(ContainerInterface $app, string $message, array $vars = null, $lang = null): string
{
    //todo: translate
    if (is_array($vars)) {
        $replaces = [];
        foreach ($vars as $k => $v) {
            $replaces['{$' . $k . '}'] = $v;
        }
        return str_replace(array_keys($replaces), $replaces, $message);
    }

    return $message;
}

if (!function_exists('_S\\view')) {
    /**
     * Make View
     *
     * @param ContainerInterface $app
     * @param string $path
     * @param array  $vars
     * @param null   $app_id
     *
     * @return View
     * @throws
     */
    function view(ContainerInterface $app, string $path, array $vars = [], $app_id = null): View
    {
        return $app->get(ViewFactory::class)->make($path, $vars, $app_id);
    }
}

if (!function_exists('_S\\asset')) {
    /**
     * @param string $path
     * @param bool   $absolute
     *
     * @return string Uri файла приложения
     */
     function asset(ContainerInterface $app, string $path = '', bool $absolute = true)
     {
         return $app->get(UrlGeneratorInterface::class)->asset($path, $absolute);
     }
}

if (!function_exists('_S\\camel_case')) {
    /**
     * Convert a value to camel case.
     *
     * @param string $value
     *
     * @return string
     *
     * @deprecated Str::camel() should be used directly instead. Will be removed in Laravel 5.9.
     */
    function camel_case($value)
    {
        return Str::camel($value);
    }
}

if (!function_exists('_S\\class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     *
     * @param string|object $class
     *
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('_S\\collect')) {
    /**
     * Create a collection from the given value.
     *
     * @param mixed $value
     *
     * @return Collection
     */
    function collect($value = null)
    {
        return new Collection($value);
    }
}

if (!function_exists('_S\\data_fill')) {
    /**
     * Fill in data where it's missing.
     *
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $value
     *
     * @return mixed
     */
    function data_fill(&$target, $key, $value)
    {
        return data_set($target, $key, $value, false);
    }
}

if (!function_exists('_S\\data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param mixed            $target
     * @param string|array|int $key
     * @param mixed            $default
     *
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (!is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (!is_array($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (!function_exists('_S\\data_set')) {
    /**
     * Set an item on an array or object using dot notation.
     *
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $value
     * @param bool         $overwrite
     *
     * @return mixed
     */
    function data_set(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
            if (!Arr::accessible($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (Arr::accessible($target)) {
            if ($segments) {
                if (!Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }

                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];

            if ($segments) {
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }
}


if (!function_exists('_S\\ends_with')) {
    /**
     * Determine if a given string ends with a given substring.
     *
     * @param string       $haystack
     * @param string|array $needles
     *
     * @return bool
     * @uses       \Symbiotic\Core\Support\Str::endsWith()
     * @deprecated \Symbiotic\Str::endsWith() should be used directly instead. Will be removed in Laravel 5.9.
     */
    function ends_with($haystack, $needles)
    {
        return Str::endsWith($haystack, $needles);
    }
}


if (!function_exists('_S\\blank')) {
    /**
     * Determine if the given value is "blank".
     *
     * @param mixed $value
     *
     * @return bool
     */
    function blank($value)
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof \Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (!function_exists('_S\\filled')) {
    /**
     * Determine if a value is "filled".
     *
     * @param mixed $value
     *
     * @return bool
     */
    function filled($value)
    {
        return !blank($value);
    }
}


if (!function_exists('_S\\snake_case')) {
    /**
     * Convert a string to snake case.
     *
     * @param string $value
     * @param string $delimiter
     *
     * @return string
     *
     * @deprecated Str::snake() should be used directly instead. Will be removed in Laravel 5.9.
     */
    function snake_case($value, $delimiter = '_')
    {
        return Str::snake($value, $delimiter);
    }
}


if (!function_exists('_S\\transform')) {
    /**
     * Transform the given value if it is present.
     *
     * @param mixed    $value
     * @param callable $callback
     * @param mixed    $default
     *
     * @return mixed|null
     */
    function transform($value, callable $callback, $default = null)
    {
        if (filled($value)) {
            return $callback($value);
        }

        if (is_callable($default)) {
            return $default($value);
        }

        return $default;
    }
}

if (!function_exists('_S\\value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    function value($value)
    {
        return is_callable($value) ? $value() : $value;
    }
}


if (!function_exists('_S\\with')) {
    /**
     * Return the given value, optionally passed through the given callback.
     *
     * @param mixed         $value
     * @param callable|null $callback
     *
     * @return mixed
     */
    function with($value, callable $callback = null)
    {
        return is_null($callback) ? $value : $callback($value);
    }
}




