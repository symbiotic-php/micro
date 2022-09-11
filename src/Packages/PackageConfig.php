<?php

declare(strict_types=1);

namespace Symbiotic\Packages;

use Symbiotic\Container\{ArrayAccessTrait, ArrayContainerInterface, ItemsContainerTrait};


class PackageConfig implements ArrayContainerInterface
{
    use ArrayAccessTrait,
        ItemsContainerTrait;

    /**
     * @property $items = [
     *     'id' => '',
     *     'bootstrappers' => [],
     *     'app' => [],
     *     'settings_form' => '\Package\FormClassName',
     *     // or для сложных настроек {@uses \Symbiotic\Settings\PackageSettingsControllerAbstract}
     *     'settings_controller' => '\PAck\MySettingsController',
     *     // or
     *    'settings' => [
     *         ['field_name' => 'name', 'type' => 1 ], // {@see \Symbiotic\Form\FormInterface}
     *     ]
     *     ....
     * ]
     */

    /**
     * PackageConfig constructor.
     *
     * @param array|\ArrayAccess $config
     */
    public function __construct(array|\ArrayAccess $config)
    {
        $this->items = $config;
    }

    /**
     * Package id
     * @return string
     */
    public function getId(): string
    {
        return $this->get('id');
    }

    /**
     * Package Application config
     *
     * @return array|null {@see \Symbiotic\Apps\AppConfigInterface wrapper array config}
     */
    public function getAppData(): ?array
    {
        return $this->get('app');
    }

    /**
     * Get path with root package base path
     *
     * @param string $path
     *
     * @return string|null
     */
    public function getPath(string $path): ?string
    {
        return \rtrim($this->get('base_path'), '\\/') . \_S\DS . \ltrim($path, '\\/');
    }


}