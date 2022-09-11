<?php

declare(strict_types=1);

namespace Symbiotic\Routing;


class Route implements RouteInterface
{

    /**
     * @var array
     *      'uses'  string classname or \Closure handler
     *      'as'    string route name
     *      'middleware' string[] Middlewares
     *      'domain'    string domain name without scheme
     *      'secure'    bool https or http scheme
     *      'app'    string|null application name
     */
    protected array $action = [];

    /**
     * Uri pattern
     * @var string
     */
    protected string $pattern = '';

    /**
     * @var array
     *
     * @used-by setParam()
     * @used-by getParam()
     */
    protected array $request_params = [];

    /**
     * Route constructor.
     *
     * @param string $uri
     * @param array  $action
     */
    public function __construct(string $uri, array $action)
    {
        $this->pattern = trim($uri, '/');
        $this->action = $action;
    }

    /**
     * Route name
     * @return string
     */
    public function getName(): string
    {
        return $this->action['as'] ?? $this->pattern;
    }

    /**
     * @return bool
     */
    public function isStatic(): bool
    {
        return !str_contains($this->getPath(), '{');
    }

    /**
     * Uri pattern
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->pattern;
    }

    /**
     * @return array
     */
    public function getAction(): array
    {
        return $this->action;
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->action['middleware'] ?? [];
    }

    /**
     * @param string $domain
     *
     * @return $this
     */
    public function setDomain(string $domain): static
    {
        $this->action['domain'] = $domain;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSecure(): bool
    {
        return isset($this->action['secure']) ? (bool)$this->action['secure'] : true;
    }

    /**
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->action['domain'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getApp(): ?string
    {
        return $this->action['app'] ?? null;
    }

    public function getHandler(): mixed
    {
        return $this->action['uses'];
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setParam(string $key, mixed $value): void
    {
        $this->request_params[$key] = $value;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->request_params;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getParam(string $key): mixed
    {
        return $this->request_params[$key] ?? null;
    }
}