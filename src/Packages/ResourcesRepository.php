<?php

declare(strict_types=1);

namespace Symbiotic\Packages;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;


class ResourcesRepository implements ResourcesRepositoryInterface,
                                     AssetsRepositoryInterface,
                                     TemplatesRepositoryInterface
{


    /**
     * @var array
     */
    protected array $packages = [];


    /**
     * ResourcesRepository constructor.
     *
     * @param TemplateCompiler            $compiler
     * @param StreamFactoryInterface      $factory
     * @param PackagesRepositoryInterface $packagesRepository
     */
    public function __construct(
        protected TemplateCompiler $compiler,
        protected StreamFactoryInterface $factory,
        protected PackagesRepositoryInterface $packagesRepository
    ) {
    }


    /**
     * @param string $package_id
     * @param string $path
     *
     * @return StreamInterface
     * @throws \Exception|ResourceException
     */
    public function getAssetFileStream(string $package_id, string $path): StreamInterface
    {
        return $this->getFileStream(
            $this->getPathType($package_id, $path, 'public_path')
        );
    }

    /**
     * @param string $package_id
     * @param string $path
     * @param string $path_type resources array key 'public_path' or 'resources_path'
     *
     * @return StreamInterface|null
     * @throws
     */
    protected function getPathType(string $package_id, string $path, string $path_type): ?string
    {
        $path = $this->cleanPath($path);
        $repository = $this->packagesRepository;
        if ($repository->has($package_id)) {
            $assets = [];
            $package_config = $repository->get($package_id);
            //todo: store found paths
            foreach (['public_path' => 'assets', 'resources_path' => 'resources'] as $k => $v) {
                if (!empty($package_config[$k]) || isset($package_config['app'])) {
                    $assets[$k] = rtrim($package_config['base_path'], '\\/')
                        . \_S\DS
                        . (isset($package_config[$k]) ? trim($package_config[$k], '\\/') : $v);
                }
            }
            if (isset($assets[$path_type])) {
                return $assets[$path_type] . '/' . ltrim($path, '/\\');
            }
            throw new PackagesException(ucfirst($path_type) . ' is not defined!');
        }
        throw new PackagesException('Package not found [' . $package_id . ']!');
    }

    protected function getFileStream(string $path, $mode = 'r'): StreamInterface
    {
        if (!\is_readable($path) || !($res = \fopen($path, $mode))) {
            throw new ResourceException('File is not exists or not readable!', $path);
        }
        return $this->factory->createStreamFromResource($res);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function cleanPath(string $path): string
    {
        return preg_replace('!\.\.[/\\\]!', '', $path);
    }

    /**
     * @param string $package_id
     * @param string $path layouts/base/index or /layouts/base/index  - real
     *                     path(module_root/resources/views/layouts/base/index) if use config resources  storage as
     *                     strings layouts/base/index or /layouts/base/index  -
     *                     $config['resources']['views']['layouts/base/index']
     *
     * @return string
     *
     * @throws \Exception|ResourceException
     */
    public function getTemplate(string $package_id, string $path): string
    {
        $base_name = basename($path);
        if (!str_contains($base_name, '.')) {
            $path .= '.blade.php';
        }
        $file = $this->getResourceFileStream($package_id, 'views/' . ltrim($this->cleanPath($path), '\\/'));

        return $this->compiler->compile($path, $file->getContents());
    }

    /**
     * @param string $package_id
     * @param string $path
     *
     * @return StreamInterface
     * @throws \Exception|ResourceException
     */
    public function getResourceFileStream(string $package_id, string $path): StreamInterface
    {
        return $this->getFileStream($this->getPathType($package_id, $path, 'resources_path'));
    }

    /**
     * Returns the full path to the package resources folder
     *
     * @param string $package_id
     *
     * @return string|null
     * @throws \Exception
     */
    public function getResourcesPath(string $package_id): ?string
    {
        try {
            return $this->getPathType($package_id, '', 'resources_path');
        } catch (PackagesException $e) {
            // todo: logger
            return null;
        }
    }

    /**
     * Returns the full path to the package assets folder
     *
     * @param string $package_id
     *
     * @return string|null
     * @throws \Exception
     */
    public function getAssetsPath(string $package_id): ?string
    {
        try {
            return $this->getPathType($package_id, '', 'public_path');
        } catch (PackagesException $e) {
            // todo: logger
            return null;
        }
    }
}