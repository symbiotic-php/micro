<?php

declare(strict_types=1);

namespace Symbiotic\Session;

use Symbiotic\Container\ArrayAccessTrait;
use Symbiotic\Core\Support\Str;

/**
 * TODO: tests
 */
class SessionStorage implements SessionStorageInterface
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

    /**
     * @param \SessionHandlerInterface $handler
     * @param string                   $name
     * @param string|null              $session_namespace The key for storing the session in a separate cell
     * @param string|null              $id                Session Id {@see generateId()}
     */
    public function __construct(
        protected \SessionHandlerInterface $handler,
        protected string $name,
        protected ?string $session_namespace = null,
        protected ?string $id = null
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
        if (empty($this->id)) {
            $this->id = $this->generateId();
        } else {
            $session = $this->readFromHandler();
            $namespace = $this->session_namespace;
            $this->items = $namespace ? $session[$namespace] ?? [] : $session;
        }
    }

    /**
     * @return array
     */
    protected function readFromHandler(): array
    {
        if ($data = $this->handler->read($this->getId())) {
            $data = @\unserialize($data, ['allowed_classes' => true]);
            if ($data !== false && is_array($data)) {
                return $data;
            }
        }
        return [];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->start();
        return isset($this->items[$key]); // todo: may be array_key_exists???
    }

    /**
     * @param string $key
     *
     * @return bool
     */
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
        return $this->handler->destroy($this->getId());
    }

    /**
     * Save the session data to storage.
     *
     * @return bool
     */
    public function save(): bool
    {
        try {
            if ($this->session_namespace) {
                $data = $this->readFromHandler();
                $data[$this->session_namespace] = $this->items;
            } else {
                $data = $this->items;
            }
            $this->handler->write($this->getId(), \serialize($data));
            return true;
        } catch (\Throwable $e) {
            //todo: logger
            return false;
        }
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
     * @return bool
     */
    public function isUpdated(): bool
    {
        return $this->updated;
    }

    /**
     * Regenerate the CSRF token value.
     *
     * @return string
     */
    public function regenerateToken(): string
    {
        $this->set('_token', $token = \md5(\uniqid('', true)));
        return $token;
    }

    /**
     * Get the name of the session.
     *
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the name of the session.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the current session ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
        $this->id = $this->isValidId($id) ? $id : $this->generateId();
    }

    /**
     * Determine if this is a valid session ID.
     *
     * @param mixed $id
     *
     * @return bool
     */
    public function isValidId(mixed $id): bool
    {
        return is_string($id) && ctype_alnum($id) && strlen($id) === 40;
    }

    /**
     * @return string
     */
    protected function generateId(): string
    {
        return Str::random(40);
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
}