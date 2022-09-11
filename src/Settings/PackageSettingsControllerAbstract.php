<?php

declare(strict_types=1);

namespace Symbiotic\Settings;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symbiotic\Form\FormBuilder;
use Symbiotic\View\View;
use Symbiotic\Packages\PackageConfig;
use Symbiotic\Packages\PackagesRepository;

use Symbiotic\View\ViewFactory;

use function _S\settings;


abstract class PackageSettingsControllerAbstract
{

    protected array $errors = [];

    /**
     * @var SettingsRepositoryInterface
     */
    protected SettingsRepositoryInterface $settingsRepository;

    /**
     * @var ViewFactory|mixed 
     */
    protected ViewFactory $view;
    
    protected FormBuilder $formBuilder;


    /**
     * PackageSettingsControllerAbstract constructor.
     *
     * @param PackageConfig      $package {@see PackagesRepository::getPackageConfig()}
     * @param ContainerInterface $container
     */
    public function __construct(protected PackageConfig $package, protected ContainerInterface $container)
    {
        $this->settingsRepository = $this->container->get(SettingsRepositoryInterface::class);
        $this->view = $this->container->get(ViewFactory::class);
        $this->formBuilder = $this->container->get(FormBuilder::class);
    }

    /**
     * @return View
     */
    abstract public function edit(): View;

    /**
     * @param ServerRequestInterface $request
     *
     * @return View
     * @throws \Exception
     */
    abstract public function save(ServerRequestInterface $request): View;

    /**
     * @return SettingsInterface
     */
    protected function getPackageSettings(): SettingsInterface
    {
        return settings($this->container, $this->package->getId());
    }

    /**
     * @param string $field
     * @param string $message
     *
     * @return void
     */
    protected function addError(string $field, string $message): void
    {
        $this->errors['fields'][$field] = $message;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function validateData(array $data): bool
    {
        /** {@see addError()}*/
        return true;
    }
}