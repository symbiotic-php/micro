<?php

declare(strict_types=1);

namespace Symbiotic\Filesystem;


trait ArrayStorageTrait
{
    private ?string $storage_path = null;

    /**
     * @param string $path
     *
     * @return void
     * @throws NotExistsException
     */
    protected function setStoragePath(string $path): void
    {
        $path = \rtrim($path);
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        if (!is_dir($path)) {
            throw new NotExistsException("Не удалось создать папку[$path]!");
        }
        $this->storage_path = $path;
    }

    /**
     * @param string   $name
     * @param callable $callback
     * @param bool $force
     *
     * @return array
     * @throws FilesystemException|\TypeError
     */
    public function remember(string $name, callable $callback, bool $force = false): array
    {
        if (!$this->storage_path) {
            return $callback();
        }
        $path = $this->getFilePath($name);
        if (\is_readable($path) && !$force) {
            return include $path;
        }
        $data = $callback();
        // TODO: может нул разрешить?
        if (!\is_array($data)) {
            throw new \TypeError('Данные должны быть массивом [' . gettype($data) . ']!');
        }
        if (!\file_put_contents($path, '<?php ' . PHP_EOL . 'return ' . var_export($data, true) . ';')) {
            throw new FilesystemException('Не удалось записать в файл[' . $path . ']!');
        }

        return $data;
    }

    /**
     * @param string $name
     *
     * @return array
     * @throws \TypeError
     */
    public function get(string $name): array
    {
        if ($this->has($name)) {
            $data = include $this->getFilePath($name);
            if (!is_array($data)) {
                throw new \TypeError('Data is not array [' . $name . ']!');
            }

            return $data;
        }
        return [];
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getFilePath(string $name): string
    {
        return $this->storage_path . \_S\DS . $name;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return \is_readable($this->getFilePath($name));
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function remove(string $name):bool
    {
        return \unlink($this->getFilePath($name));
    }
}

