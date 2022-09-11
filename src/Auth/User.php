<?php

declare(strict_types=1);

namespace Symbiotic\Auth;


class User implements UserInterface
{
    /**
     * @param int      $accessGroup {@see UserInterface constants the groups}
     * @param string   $fullName
     * @param int|null $id
     */
    public function __construct(
        protected int $accessGroup = self::GROUP_GUEST,
        protected string $fullName = '',
        protected ?int $id = null
    ) {
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getAccessGroup(): int
    {
        return $this->accessGroup;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }
}