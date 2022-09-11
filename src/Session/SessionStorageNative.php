<?php

declare(strict_types=1);

namespace Symbiotic\Session;

use Symbiotic\Container\ArrayAccessTrait;

class SessionStorageNative implements SessionStorageInterface
{

    use ArrayAccessTrait;

    /**
     * Session data
     * @var array
     */
    protected array $items = [];

    /**
     * @var bool
     */
    protected bool $started = false;


    /**
     * @var bool
     */
    protected bool $updated = false;


    public function __construct(
        protected ?string $namespace,
        protected bool $symbiosis
    ) {
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->start();
        $this->updated = true;
        $this->items[$key] = $value;
    }

    /**
     * Start the session, reading the data from a handler.
     *
     * @return bool
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        if (\PHP_SESSION_ACTIVE !== \session_status()) {
            // ok to try and start the session
            if (!\session_start()) {
                throw new \RuntimeException('Failed to start the session');
            }
        }
        $this->loadSession();
        $this->started = true;

        return true;
    }

    /**
     * Load the session data from the handler.
     *
     * @return void
     */
    protected function loadSession(): void
    {
        $session_namespace = $this->namespace;
        if ($session_namespace) {
            if (!isset($_SESSION[$session_namespace])) {
                $_SESSION[$session_namespace] = [];
            }
            $this->items =  &$_SESSION[$session_namespace];
        } else {
            $this->items =  &$_SESSION;
        }
    }

    public function has(string $key): bool
    {
        $this->start();
        return array_key_exists($key, $this->items);
    }

    /**
     * Regenerate the CSRF token value.
     *
     * @return string
     */
    protected function regenerateToken(): string
    {
        $this->set('_token', $token = \md5(\uniqid('', true)));
        return $token;
    }

    public function delete(string $key): bool
    {
        $this->start();
        $this->updated = true;
        unset($this->items[$key]);
        return true;
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $this->updated = true;
        $this->items = [];
    }

    /**
     * @return bool
     */
    public function destroy(): bool
    {
        if ($this->symbiosis) {
            $this->items = [];
            return true;
        } else {
            return \session_destroy();
        }
    }

    /**
     * Save the session data to storage.
     *
     * @return bool
     */
    public function save(): bool
    {
        // native save
        return true;
    }

    /**
     * Determine if the session has been started.
     *
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * Get the name of the session.
     *
     * @return false|string
     */
    public function getName(): false|string
    {
        return \session_name();
    }

    /**
     * Get the current session ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return \session_id();
    }

    /**
     * Set the session ID.
     *
     * @param string $id
     *
     * @return void
     */
    public function setId(string $id): void
    {
        if (\session_status() === \PHP_SESSION_ACTIVE) {
            throw new SessionException('Session active or invalid id');
        }
        \session_id($id);
    }


    /**
     * Get the CSRF token value.
     *
     * @return string
     */
    public function token(): string
    {
        return !$this->has('_token') ? $this->regenerateToken() : $this->get('_token');
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function get(string $key): mixed
    {
        $this->start();
        return $this->items[$key] ?? null;
    }

    /**
     * @return bool
     */
    public function isUpdated(): bool
    {
        return $this->updated;
    }
}