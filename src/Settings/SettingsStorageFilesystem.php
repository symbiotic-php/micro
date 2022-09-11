<?php
declare(strict_types=1);

namespace Symbiotic\Settings;

use Symbiotic\Filesystem\ArrayStorageTrait;


class SettingsStorageFilesystem implements SettingsStorageInterface
{
    use ArrayStorageTrait;

    /**
     * @param string $storage_path
     *
     * @throws \Symbiotic\Filesystem\NotExistsException
     */
    public function __construct(string $storage_path)
    {
        $this->setStoragePath($storage_path);
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return bool
     * @throws \Exception
     */
    public function set(string $name, array $data = []):bool
    {
        $this->remember($name, fn() => $data, true);
        return true;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getFilePath(string $name):string
    {
        return $this->storage_path . \_S\DS . $name.'.php';
    }
}