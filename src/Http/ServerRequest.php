<?php

declare(strict_types=1);

namespace Symbiotic\Http;


class ServerRequest extends \Nyholm\Psr7\ServerRequest
{

    /**
     * @return bool
     */
    public function isXMLHttpRequest(): bool
    {
        return $this->getServerParam('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest';
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->getServerParam('HTTP_USER_AGENT');
    }

    /**
     * @param string     $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getServerParam(string $name, mixed $default = null): mixed
    {
        $server = $this->getServerParams();
        return $server[$name] ?? $default;
    }

    /**
     * @param string     $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getInput(string $name, mixed $default = null): mixed
    {
        $params = $this->getParsedBody();
        return $params[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getQuery(string $name, mixed $default = null): mixed
    {
        $params = $this->getQueryParams();
        return $params[$name] ?? $default;
    }
}