<?php

declare(strict_types=1);

namespace Symbiotic\Auth;


use Symbiotic\Auth\Storage\NonPersistStorage;

/**
 * @copyright   2020 laminas Project
 * @author      laminas Project
 * @refactoring 2021 Symbiotic
 * @license     BSD-3-Clause
 *
 */
class AuthService implements AuthServiceInterface
{
    /**
     * Persistent storage handler
     *
     * @var AuthStorageInterface|null
     */
    protected ?AuthStorageInterface $storage = null;

    /**
     * Authentication adapter
     *
     * @var AuthenticatorInterface|null
     */
    protected ?AuthenticatorInterface $adapter = null;

    /**
     * The current user from the authorization store
     *
     * @var UserInterface|null
     */
    protected ?UserInterface $identity = null;

    /**
     * Constructor
     *
     * @param AuthStorageInterface|null   $storage
     * @param AuthenticatorInterface|null $adapter
     */
    public function __construct(AuthStorageInterface $storage = null, AuthenticatorInterface $adapter = null)
    {
        if (null !== $storage) {
            $this->setStorage($storage);
        }
        if (null !== $adapter) {
            $this->setAdapter($adapter);
        }
    }

    /**
     * Authenticates against the supplied adapter
     *
     * @param AuthenticatorInterface|null $adapter
     *
     * @return ResultInterface
     * @throws AuthException
     */
    public function authenticate(AuthenticatorInterface $adapter = null): ResultInterface
    {
        if (!$adapter) {
            if (!$adapter = $this->getAdapter()) {
                throw new AuthException(
                    'An adapter must be set or passed prior to calling authenticate()'
                );
            }
        }
        $result = $adapter->authenticate();

        if ($this->hasIdentity()) {
            $this->clearIdentity();
        }

        if ($result->isValid()) {
            $this->getStorage()->write(\serialize($result->getIdentity()));
        }

        return $result;
    }

    /**
     * Returns the authentication adapter
     *
     * The adapter does not have a default if the storage adapter has not been set.
     *
     * @return AuthenticatorInterface|null
     */
    public function getAdapter(): ?AuthenticatorInterface
    {
        return $this->adapter;
    }

    /**
     * Sets the authentication adapter
     *
     * @param AuthenticatorInterface $adapter
     *
     * @return self Provides a fluent interface
     */
    public function setAdapter(AuthenticatorInterface $adapter): static
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Returns true if and only if an identity is available from storage
     *
     * @return bool
     */
    public function hasIdentity(): bool
    {
        return !$this->getStorage()->isEmpty();
    }

    /**
     * Returns the persistent storage handler
     *
     * Session storage is used by default unless a different storage adapter has been set.
     *
     * @return AuthStorageInterface|null
     */
    public function getStorage(): ?AuthStorageInterface
    {
        if (null === $this->storage) {
            $this->setStorage(new NonPersistStorage());
        }

        return $this->storage;
    }

    /**
     * Sets the persistent storage handler
     *
     * @param AuthStorageInterface $storage
     *
     * @return self Provides a fluent interface
     */
    public function setStorage(AuthStorageInterface $storage): static
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * Clears the identity from persistent storage
     *
     * @return void
     */
    public function clearIdentity(): void
    {
        $this->getStorage()->clear();
        $this->identity = null;
    }

    /**
     * Returns the identity from storage or null if no identity is available
     *
     * @return mixed|UserInterface|null
     * @throws \Exception
     */
    public function getIdentity(): mixed
    {
        if ($this->identity) {
            return $this->identity;
        }
        $storage = $this->getStorage();

        if ($storage->isEmpty()) {
            return null;
        }

        if ($data = \unserialize($storage->read())) {
            return $this->identity = $data;
        }
        throw new AuthException('Incorrect user session data!');
    }

    /**
     *
     * @param string|null $fullName
     * @param string|null $id
     *
     * @return User
     */
    public function getGuestUser(string $fullName = null, string $id = null): UserInterface
    {
        if (null === $fullName) {
            $fullName = 'Guest';
        }
        return new User(UserInterface::GROUP_GUEST, $fullName, $id);
    }
}