<?php

declare(strict_types=1);

namespace Symbiotic\Apps;

use Symbiotic\Container\{ArrayAccessTrait, ItemsContainerTrait};


class AppConfig implements AppConfigInterface
{
    use ArrayAccessTrait,
        ItemsContainerTrait;

    /**
     * Application id
     * @var string
     */
    protected string $id;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->id = $config['id'] ?? null;
        $this->items = $config;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getAppName(): string
    {
        return $this->has('name') ? $this->get('name') : \ucfirst($this->getId());
    }

    /**
     * @inheritDoc
     *
     * @param string|null $path
     *
     * @return string|null
     */
    public function getBasePath(string $path = null): ?string
    {
        $base = $this->get('base_path');
        return is_string($base) ? ($path ? rtrim($base,'\\/') . \_S\DS . ltrim($path,'\\/') : $base) : null;
    }

    /**
     * @inheritDoc
     *
     * @return string|null
     *
     * @see \Symbiotic\Routing\AppRoutingInterface
     */
    public function getRoutingProvider(): ?string
    {
        return $this->get('routing');
    }

    /**
     * @inheritDoc
     *
     * @return bool
     */
    public function hasParentApp(): bool
    {
        return $this->has('parent_app');
    }

    /**
     * @inheritDoc
     *
     * @return string|null
     */
    public function getParentAppId(): ?string
    {
        return $this->get('parent_app');
    }
}