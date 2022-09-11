<?php

declare(strict_types=1);

namespace Symbiotic\Routing;


/**
 * Class Router
 * @package Symbiotic\Routing
 *
 */
class Router implements RouterInterface
{
    use AddRouteTrait;

    /**
     * @var array|string[]
     */
    protected static array $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    /**
     * @var int
     */
    protected int $count_routes = 0;

    /**
     * @used-by group()
     *
     * @var array
     */
    protected array $groupStack = [];

    /**
     *
     * @var array = [
     *     'GET' => [
     *        'pattern/test' => Route(),
     *        'pattern/test1' => Route(),
     *        // ....
     *      ],
     *      'POST' => []//....
     * ]
     *      * @used-by addRoute()
     */
    protected array $routes = [];

    /**
     * @var array
     *
     * @see     addRoute()
     * @used-by getByName()
     */
    protected array $named_routes = [];

    /**
     * @var array ['domain','secure','name'....]
     */
    protected array $params = [
        'secure' => true
    ];


    /**
     *
     */
    public function __construct()
    {
        foreach (static::$verbs as $verb) {
            $this->routes[$verb] = [];
        }
    }

    /**
     * @param array $params
     *
     * @return void
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * @param string $domain
     */
    public function setRoutesDomain(string $domain): void
    {
        $this->params['domain'] = $domain;
    }

    /**
     * @param bool $secure
     */
    public function setSecure(bool $secure = true): void
    {
        $this->params['secure'] = $secure;
    }

    /**
     * Add route
     *
     * @param array |string         $httpMethods
     * @param string                $uri    Uri pattern
     * @param array|string|\Closure $action = [
     *
     *                    'uses' => '\\Module\\Http\\EntityController@edit',//  \Closure | string
     *                     // optional params
     *                     'as' => 'module.entity.edit',
     *                     'module' => 'module_name',
     *                     'middleware' => ['group_name','\\Symbiotic\\Http\\Middlewares\Auth',
     *                     '\\Module\\Http\\Middlewares\Test']
     * ]
     *
     * @return Route
     */
    public function addRoute(string|array $httpMethods, string $uri, string|array|\Closure $action): RouteInterface
    {
        if (\is_string($action) || $action instanceof \Closure) {
            $action = ['uses' => $action];
        }
        // Set default params
        foreach (['domain', 'secure'] as $v) {
            if (isset($this->params[$v]) && !isset($action[$v])) {
                $action[$v] = $this->params[$v];
            }
        }

        $route = $this->createRoute($uri, $action, \array_map('strtoupper', (array)$httpMethods));

        $this->setRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param array  $action
     * @param array  $httpMethods
     *
     * @return RouteInterface
     */
    protected function createRoute(string $uri, array $action, array $httpMethods): RouteInterface
    {
        if (!empty($this->groupStack)) {
            $group = \end($this->groupStack);
            // Merge group namespace with controller name
            if (isset($action['uses']) && \is_string($action['uses'])) {
                $class = $action['uses'];
                $action['uses'] = isset($group['namespace']) && !str_starts_with($class, '\\')
                    ? \rtrim($group['namespace'], '\\') . '\\' . $class : $class;
            }
            if (!isset($action['as'])) {
                $action['as'] = trim($uri, '/');
            }
            // Merge other params (as, prefix, namespace,module)
            $action = static::mergeAttributes($action, $group);

            // Merge Uri with prefix
            $uri = \trim(
                \trim($group['prefix'] ?? '', '/') . '/' . \trim($uri, '/'),
                '/'
            ) ?: '/';
        }

        $action['methods'] = $httpMethods;

        return new Route($uri, $action);
    }

    protected static function mergeAttributes(array $new, array $old): array
    {
        $as = 'as';
        if (isset($old[$as])) {
            $separator = str_ends_with($old[$as], '::') ? '' : '.';
            $new[$as] = $old[$as] . (isset($new[$as]) ? $separator . $new[$as] : '');
        }
        $module = 'app';
        if (!isset($new[$module]) && isset($old[$module])) {
            $new[$module] = $old[$module];
        }
        $secure = 'secure';
        if (!isset($new[$secure]) && isset($old[$secure])) {
            $new[$secure] = $old[$secure];
        }
        $namespace = 'namespace';
        if (isset($new[$namespace])) {
            $new[$namespace] = isset($old[$namespace]) && !str_starts_with($new[$namespace], '\\')
                ? \rtrim($old[$namespace], '\\') . '\\' . \trim($new[$namespace], '\\')
                : '\\' . \trim($new[$namespace], '\\');
        } elseif (isset($old[$namespace])) {
            $new[$namespace] = $old[$namespace];
        }

        $prefix = 'prefix';
        $old_p = $old[$prefix] ?? '';
        $new[$prefix] = isset($new[$prefix]) ?
            \trim($old_p, '/') . '/' . \trim($new[$prefix], '/')
            : $old_p;

        foreach ([$as, $module, $namespace, $prefix] as $v) {
            if (\array_key_exists($v, $old)) {
                unset($old[$v]);
            }
        }
        return \array_merge_recursive($old, $new);
    }

    /**
     * @param RouteInterface $route
     *
     * @return RouteInterface
     */
    public function setRoute(RouteInterface $route): RouteInterface
    {
        foreach ($route->getAction()['methods'] as $method) {
            $this->routes[$method][$route->getPath()] = $route;
        }
        $name = $route->getName();
        if ($name) {
            $this->named_routes[$name] = $route;
        }

        $this->count_routes++;

        return $route;
    }

    /**
     * @param string $name
     *
     * @return RouteInterface|null
     */
    public function getByName(string $name): ?RouteInterface
    {
        return $this->named_routes[$name] ?? null;
    }

    public function getNamedRoutes(): array
    {
        return $this->named_routes;
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param array    $attributes
     * @param \Closure $routes if object need __invoke method
     *
     * @return void
     */
    public function group(array $attributes, \Closure $routes): void
    {
        $attributes = static::mergeAttributes($attributes, !empty($this->groupStack) ? end($this->groupStack) : []);

        $this->groupStack[] = $attributes;
        $routes($this);
        array_pop($this->groupStack);
    }

    /**
     * @param string $httpMethod
     * @param string $uri
     *
     * @return Route|null
     */
    public function match(string $httpMethod, string $uri): ?RouteInterface
    {
        $uri = trim($uri, '/');
        $httpMethod = strtoupper($httpMethod);
        $all_routes = $this->getRoutes();
        $routes = $all_routes[$httpMethod] ?? [];

        /**
         * @var Route $route
         * @todo: Compile by 30 routes and save compiled to cache {@see CacheRouterDecorator}
         */
        foreach ($routes as $route) {
            $vars = [];
            $pattern = \preg_replace(
                '/(^|[^\.])\*/ui',
                '$1.*?',
                \str_replace(array(' ', '.', '('), array('\s', '\.', '(?:'), $route->getPath())
            );
            if (\preg_match_all(
                '/\{([a-z_]+):?([^\}]*)?\}/ui',
                $pattern,
                $match,
                PREG_OFFSET_CAPTURE | PREG_SET_ORDER
            )) {
                $offset = 0;
                foreach ($match as $m) {
                    $vars[] = $m[1][0];
                    $p = $m[2][0] ?: '.*?';
                    $pattern = substr($pattern, 0, $offset + $m[0][1]) . '(' . $p . ')' . substr(
                            $pattern,
                            $offset + $m[0][1] + strlen(
                                $m[0][0]
                            )
                        );
                    $offset = $offset + strlen($p) + 2 - strlen($m[0][0]);
                }
            }
            if (preg_match('!^' . $pattern . '$!ui', $uri, $match)) {
                if ($vars) {
                    $route = clone $route;
                    array_shift($match);
                    foreach ($vars as $i => $v) {
                        if (isset($match[$i])) {
                            $route->setParam($v, $match[$i]);
                        }
                    }
                }

                return $route;
            }
        }
        return null;
    }

    /**
     * @param null|string $httpMethod
     *
     * @return array
     * @uses $routes - see structure
     *
     */
    public function getRoutes(string $httpMethod = null): array
    {
        if ($httpMethod && in_array(strtoupper($httpMethod), static::$verbs)) {
            return $this->routes[strtoupper($httpMethod)];
        }
        return $this->routes;
    }

    /**
     * @param string $name
     *
     * @return array|RouteInterface[]
     */
    public function getByNamePrefix(string $name): array
    {
        $routes = [];
        foreach ($this->named_routes as $v) {
            if (preg_match('/^' . preg_quote($name, '/') . '/is', $v->getName())) {
                $routes[$v->getName()] = $v;
            }
        }

        return $routes;
    }
}
