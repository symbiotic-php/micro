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
     * @param int    $linkHandling
     * @param array  $permissions
     *
     * @throws \LogicException
     */
    public function __construct($root = '', $writeFlags = LOCK_EX, array $permissions = [])
    {
        $root = is_link($root) ? realpath($root) : $root;
        $this->permissionMap = array_replace_recursive(static::permissions, $permissions);
        //  $this->ensureDirectory($root);

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
    public function listDir(string $path)
    {
        $path = $this->applyPathPrefix($this->normalizePath($path));
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
    public function createDir(string $dirname, array $options = []):bool
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
     * @param string $time
     *
     * @return bool
     */
    public function touch(string $path, int $time)
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
    public function copy(string $path, string $to):bool
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
    public function has($path): bool
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
                throw new FilesystemException(\sprintf('Impossible to create the directory "%s". %s', $dirname, $errorMessage));
            }
        }
    }

    protected function clearstatcache($path, $flag = false)
    {
        \clearstatcache($flag, $this->applyPathPrefix($path));
    }

    protected function copyThrow(string $path, string $newpath)
    {
        if ($result = \copy($this->applyPathPrefix($path), $this->applyPathPrefix($newpath))) {
            return $result;
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
    public function delete(string $path):bool
    {
        $path = $this->applyPathPrefix($path);
        if (is_dir($path)) {
            return $this->deleteDir($this->removePathPrefix($path));
        }

        return \unlink($path);
    }

    public function deleteDir(string $path):bool
    {
        $path = $this->applyPathPrefix($path);
        if (!is_dir($path)) {
            return false;
        }
        /** @var \SplFileInfo $file */
        foreach ($this->getRecursiveDirectoryIterator($path, \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $this->deleteFileInfoObject($file);
        }

        return rmdir($path);
    }

    /**
     * @param \SplFileInfo $file
     */
    protected function deleteFileInfoObject(\SplFileInfo $file)
    {
        switch ($file->getType()) {
            case 'dir':
                return rmdir($file->getRealPath());
                break;
            case 'link':
                return unlink($file->getPathname());
                break;
            default:
                return unlink($file->getRealPath());
        }
    }

    /**
     * @param $path
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

        $result = file_put_contents($path, $contents, $options['flags'] ?? $this->writeFlags);
        if ($result && !empty($options['no_touch'])) {
            @touch($path, $time, $time);
        }

        return is_int($result);
    }

    /**
     * @param string $path
     * @param string $newPath
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function rename(string $path, string $newPath):bool
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


    public function listContents($directory = '', $recursive = false)
    {
        // TODO: Implement listContents() method.
    }

    public function getMetadata($path)
    {
        // TODO: Implement getMetadata() method.
    }

    public function getSize($path)
    {
        // TODO: Implement getSize() method.
    }

    public function getMimetype($path)
    {
        // TODO: Implement getMimetype() method.
    }

    public function getTimestamp($path)
    {
        // TODO: Implement getTimestamp() method.
    }

    public function setVisibility($path, $visibility):bool
    {
        // TODO: Implement setVisibility() method.
    }

    public function getVisibility($path):string|false
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
    public function getContent(string $path, int $flock = null): string
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $contents = fread($handle, $this->size($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }
}
