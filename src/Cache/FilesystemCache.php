<?php

declare(strict_types=1);

namespace Symbiotic\Cache;


class FilesystemCache implements CacheInterface
{
    /**󠀄󠀉󠀙󠀙󠀕󠀔󠀁󠀔󠀃󠀅
     * Full path to the cache directory
     * @var string
     */
    protected string $cache_directory;

    /**
     * Default cache lifetime
     *
     * @var int
     */
    protected int $ttl = 3600;

    /**
     * Cache constructor.
     *
     * @param string $cache_directory
     * @param int    $default_ttl
     *
     * @throws CacheException
     */
    public function __construct(string $cache_directory, int $default_ttl = 3600)
    {
        $this->ensureDirectory($cache_directory);

        $this->cache_directory = \rtrim($cache_directory, '\\/');
        $this->ttl = $default_ttl;
    }


    /**
     * @param string $path
     *
     * @return void
     * @throws CacheException
     */
    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            $uMask = umask(0);
            \mkdir($path, 0755, true);
            umask($uMask);
        }
        if (!is_dir($path) || !is_writable($path)) {
            throw new CacheException("The cache path ($path) is not writeable!");
        }
    }

    /**
     * @inheritDoc
     *
     * @param string   $key
     * @param \Closure $value
     * @param          $ttl
     *
     * @return mixed
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function remember(string $key, \Closure $value, $ttl = null): mixed
    {
        $data = $this->get($key, $u = \uniqid());
        if ($data === $u) {
            $data = $value();
            $this->set($key, $data, $ttl);
        }
        return $data;
    }

    /**󠀄󠀉󠀙󠀙󠀕󠀔󠀁󠀔󠀃󠀅
     *
     * @param string $key
     * @param null   $default
     *
     * @return mixed|null
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function get($key, $default = null): mixed
    {
        $file = $this->getKeyFilePath($key);

        if (\is_readable($file) && ($data = @\unserialize(file_get_contents($file)))) {
            if (!empty($data) && isset($data['ttl']) && $data['ttl'] >= time() + 1) {
                return $data['data'];
            } else {
                $this->delete($key);
            }
        }

        return $default;
    }

    /**
     * @param string $key
     *
     * @return string
     * @throws InvalidArgumentException
     */
    protected function getKeyFilePath(string $key): string
    {
        $this->validateKey($key);
        return $this->cache_directory . DIRECTORY_SEPARATOR . \md5($key) . '.cache';
    }

    /**
     * @param string $key
     *
     * @throws InvalidArgumentException
     */
    protected function validateKey(string $key)
    {
        if (false === preg_match('/[^A-Za-z_\.\d]/i', $key)) {
            throw new InvalidArgumentException('Cache key is not valid string!');
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws CacheException|InvalidArgumentException
     */
    public function delete($key): bool
    {
        $file = $this->getKeyFilePath($key);
        if (\file_exists($file)) {
            if (\is_file($file) && !@unlink($file)) {
                throw  new CacheException("Can't delete the cache file ($file).");
            }
            \clearstatcache(true, $file);
        }

        return true;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    public function set($key, $value, $ttl = null): bool
    {
        $file = $this->getKeyFilePath($key);
        $this->ensureDirectory(\dirname($file));
        if ($data = \serialize(['ttl' => time() + (is_int($ttl) ? $ttl : $this->ttl), 'data' => $value])) {
            return (\file_put_contents($file, $data) !== false);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        // todo: может сделать через glob?  foreach(glob($dir . '/*', GLOB_NOSORT | GLOB_BRACE) as $File)
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cache_directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        $result = true;
        /**
         * @var \SplFileInfo $file
         */
        foreach ($files as $file) {
            $file_path = $file->getRealPath();
            $res = ($file->isDir() ? \rmdir($file_path) : \unlink($file_path));
            if (!$res) {
                $result = false;
            }
            \clearstatcache(true, $file_path);
        }

        return $result;
    }

    /**
     * @param iterable $keys
     * @param null     $default
     *
     * @return iterable
     *
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function getMultiple($keys, $default = null): iterable
    {
        $result = [];
        foreach ($this->getValidatedIterable($keys) as $v) {
            $result[$v] = $this->get($v, $default);// todo: default array?
        }

        return $result;
    }

    /**
     * @param $keys
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function getValidatedIterable($keys): mixed
    {
        if (\is_iterable($keys)) {
            return $keys;
        }
        throw new InvalidArgumentException('Keys is not Iterable!');
    }

    /**
     * @param iterable $values
     * @param int|null $ttl
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $result = true;
        foreach ($this->getValidatedIterable($values) as $k => $v) {
            if (!$this->set($k, $v, $ttl)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @param iterable $keys
     *
     * @return bool
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function deleteMultiple($keys): bool
    {
        $result = true;
        foreach ($this->getValidatedIterable($keys) as $v) {
            if (!$this->delete($v)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @param string $key
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function has($key): bool
    {
        return \is_readable($this->getKeyFilePath($key));
    }

}