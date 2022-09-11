<?php

declare(strict_types=1);

namespace Symbiotic\Auth;


class AuthResult implements ResultInterface
{

    /**
     * @var string|null
     */
    protected ?string $error = null;

    public function __construct(protected ?UserInterface $user = null)
    {
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @param string $error
     *
     * @return $this
     */
    public function setError(string $error): static
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return ($this->user instanceof UserInterface);
    }

    /**
     * @return UserInterface|null
     */
    public function getIdentity(): ?UserInterface
    {
        return $this->user;
    }

}