<?php

declare(strict_types=1);

namespace Symbiotic\Filesystem\Adapter;

use Symbiotic\Filesystem\ExistsException;
use Symbiotic\Filesystem\FilesystemException;
use Symbiotic\Filesystem\FilesystemInterface;
use Symbiotic\Filesystem\NotExistsException;


class Local extends AbstractAdapter implements FilesystemInterface
{

    /**
     * @var array
     */
    const permissions = [
        'file' => [
            'public' => 0644,
            'private' => 0600,
        ],
        'dir' => [
            'public' => 0755,
            'private' => 0700,
        ],
    ];
    protected int $writeFlags = LOCK_EX;
    /**
     * @var array
     */
    protected array $permissionMap;

    /**
     * Constructor.
     *
     * @param string $root
     * @param int    $writeFlags
     * @param array  $permissions
     *
     * @throws \LogicException
     */
    public function __construct(string $root = '', int $writeFlags = LOCK_EX, array $permissions = [])
    {
        $root = is_link($root) ? realpath($root) : $root;
        $this->permissionMap = array_replace_recursive(static::permissions, $permissions);

        if (!empty($root) && (!is_dir($root) || !is_readable($root))) {
            throw new \LogicException('The root path ' . $root . ' is not readable.');
        }

        $this->setPathPrefix($root);
        $this->writeFlags = $writeFlags;
    }

    /**
     * Возвращает нумерованный список файлов директории или false
     * [
     *    0 => '..',
     *    1 => 'file.php',
     *    2 => 'link.php',
     *    ....
     * ] : false
     *
     * @param string $path - Полный путь к директории от корня сервера
     *
     * @return array|false
     */
    public function listDir(string $path): array|false
    {
        $path = $this->applyPathPrefix($this->normalizePath($path));
        if (!is_dir($path)) {
            return false;
        }
        $files = [];
        if (!\function_exists("scandir")) {
            $h = \opendir($path);
            while (false !== ($filename = \readdir($h))) {
                $files [] = $filename;
            }
        } else {
            $files = \scandir($path);
        }
        return $files;
    }


    /**
     * @param string $dirname
     * @param array  $options
     *
     * @return bool
     */
    public function createDir(string $dirname, array $options = []): bool
    {
        $return = $dirname = $this->applyPathPrefix($dirname);

        if (!is_dir($dirname)) {
            if (false === @mkdir(
                    $dirname,
                    $this->permissionMap['dir'][$options['visibility'] ?? 'public'],
                    true
                )
                || false === is_dir($dirname)) {
                $return = false;
            }
        }
        return $return;
    }

    /**
     * @param string $path
     * @param int    $time
     *
     * @return bool
     */
    public function touch(string $path, int $time): bool
    {
        return \touch($this->applyPathPrefix($path), $time, $time);
    }

    /**
     * @param string $path
     * @param string $to
     *
     * @return bool
     * @throws FilesystemException
     * @throws NotExistsException
     */
    public function copy(string $path, string $to): bool
    {
        if (!$this->has($path)) {
            throw new NotExistsException($path . ' File not Found');
        }
        $path = $this->applyPathPrefix($path);
        $to = $this->applyPathPrefix($to);

        if (!is_dir($to)) {
            $this->ensureDirectory(dirname($to));
            $this->copyThrow($path, $to);
        } else {
            $path = rtrim($path, '\\/') . '/';
            $to = rtrim($to, '\\/') . '/';
            /** @var \SplFileInfo $file */
            foreach ($this->getRecursiveDirectoryIterator($path, \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                $old_path = ($file->getType() == 'link') ? $file->getPathname() : $file->getRealPath();
                $new_path = str_replace($path, $to, $old_path);

                if (!$file->isDir()) {
                    $this->ensureDirectory(dirname($new_path));
                    $this->copyThrow($old_path, $new_path);
                } else {
                    $this->ensureDirectory($new_path);
                }
            }
        }

        /*if ($delete_from) {
            return $this->delete($from);
        }*/
        return true;
    }

    /**
     * @inheritdoc
     */
    public function has(string $path): bool
    {
        return \file_exists($this->applyPathPrefix($path));
    }

    /**
     * Ensure the root directory exists.
     *
     * @param string $dirname directory path
     *
     * @return void
     *
     * @throws \Exception in case the root directory can not be created
     */
    protected function ensureDirectory(string $dirname): void
    {
        if (!\is_dir($dirname)) {
            $error = !@\mkdir($dirname, $this->permissionMap['dir']['public'], true) ? error_get_last() : [];
            if (!@\mkdir($dirname, $this->permissionMap['dir']['public'], true)) {//?????????????? todo
                $error = \error_get_last();
            }
            $this->clearstatcache($dirname);
            if (!\is_dir($dirname)) {
                $errorMessage = $error['message'] ?? '';
                throw new FilesystemException(
                    \sprintf('Impossible to create the directory "%s". %s', $dirname, $errorMessage)
                );
            }
        }
    }

    protected function clearstatcache(string $path, bool $flag = false):void
    {
        \clearstatcache($flag, $this->applyPathPrefix($path));
    }

    /**
     * @param string $path
     * @param string $newPath
     *
     * @return bool
     * @throws FilesystemException
     */
    protected function copyThrow(string $path, string $newPath): bool
    {
        if (\copy($this->applyPathPrefix($path), $this->applyPathPrefix($newPath))) {
            return true;
        }
        throw new FilesystemException('File not copied : ' . $path);
    }

    /**
     * @param string $path
     * @param int    $mode
     *
     * @return \RecursiveIteratorIterator
     * @todo : убрать SPL !!!!! это говно плохо работает!!!!
     */
    public function getRecursiveDirectoryIterator($path, $mode = \RecursiveIteratorIterator::SELF_FIRST)
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->applyPathPrefix($path), \FilesystemIterator::SKIP_DOTS),
            $mode
        );
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function delete(string $path): bool
    {
        $fullPath = $this->applyPathPrefix($path);
        if (is_dir($fullPath)) {
            return $this->deleteDir($path);
        }

        return \unlink($fullPath);
    }

    public function deleteDir(string $path): bool
    {
        $path = $this->applyPathPrefix($path);
        if (!\is_dir($path)) {
            return false;
        }
        /** @var \SplFileInfo $file */
        foreach ($this->getRecursiveDirectoryIterator($path, \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            if (!$this->deleteFileInfoObject($file)) {
                return false;
            }
        }

        return \rmdir($path);
    }

    /**
     * @param \SplFileInfo $file
     *
     * @return bool
     */
    protected function deleteFileInfoObject(\SplFileInfo $file): bool
    {
        switch ($file->getType()) {
            case 'dir':
                return \rmdir($file->getRealPath());
            case 'link':
                return \unlink($file->getPathname());
            default:
                return \unlink($file->getRealPath());
        }
    }

    /**
     * @param string   $path
     * @param int|null $flock
     *
     * @return bool|string
     */
    public function read(string $path, int $flock = null): string|false
    {
        $path = $this->applyPathPrefix($path);
        if ($handle = \fopen($path, 'rb')) {
            try {
                if (null !== $flock) {
                    if (!\flock($handle, $flock)) {
                        return false;
                    }
                    \clearstatcache(true, $path);
                }
                return \fread($handle, \filesize($path));
            } finally {
                if (null !== $flock) {
                    \flock($handle, LOCK_UN);
                }
                fclose($handle);
            }
        }
        return false;
    }

    /**
     * Write file contents
     *
     * @param string $path relative file path
     * @param string $contents
     * @param array  $options
     *
     * @return bool
     */
    public function write(string $path, string $contents, array $options = []): bool
    {
        $path = $this->applyPathPrefix($path);
        $time = $this->has($path) ? filemtime($path) : time();

        $result = \file_put_contents($path, $contents, $options['flags'] ?? $this->writeFlags);
        if (\is_int($result) && !empty($options['no_touch'])) {
            @\touch($path, $time, $time);
        }

        return \is_int($result);
    }

    /**
     * @param string $path
     * @param string $newPath
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function rename(string $path, string $newPath): bool
    {
        $path = $this->applyPathPrefix($path);
        $newPath = $this->applyPathPrefix($newPath);
        if (!\file_exists($path)) {
            throw new NotExistsException($path);
        }
        if (\file_exists($newPath)) {
            throw new ExistsException($path);
        }
        $this->ensureDirectory(dirname($newPath));

        return rename($path, $newPath);
    }

    /**
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array|false
     */
    public function listContents(string $directory = '', bool $recursive = false): array|false
    {
        // TODO: Implement listContents() method.
    }

    public function getMetadata(string $path): array|false
    {
        // TODO: Implement getMetadata() method.
    }

    public function getSize(string $path): int|false
    {
        // TODO: Implement getSize() method.
    }

    public function getMimetype(string $path): string|false
    {
        // TODO: Implement getMimetype() method.
    }

    public function getTimestamp(string $path): int|false
    {
        // TODO: Implement getTimestamp() method.
    }

    public function setVisibility(string $path, string $visibility): bool
    {
        // TODO: Implement setVisibility() method.
    }

    public function getVisibility(string $path): string|false
    {
        // TODO: Implement getVisibility() method.
    }

    /**
     * @param string $path
     *
     * @return int|false
     */
    public function getMTime(string $path): int|false
    {
        $path = $this->applyPathPrefix($path);
        return file_exists($path) ? filemtime($path) : false;
    }

    /**
     * Get contents of a file with shared access.
     *
     * @param string $path
     *
     * @return string
     */
    public function getContent(string $path): string
    {
        $fullPath = $this->applyPathPrefix($path);
        $contents = '';

        $handle = fopen($fullPath, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $fullPath);
                    $contents = fread($handle, $this->getSize($path) ?: 1);
                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }
}
