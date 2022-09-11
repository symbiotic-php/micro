<?php

declare(strict_types=1);

namespace Symbiotic\Filesystem;


interface AdapterInterface
{
    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function has(string $path): bool;

    /**
     * Read a file.
     *
     * @param string $path The path to the file.
     *
     * @return string|false The file contents or false on failure.
     * @throws \Exception
     *
     */
    public function read(string $path): string|false;

    /**
     * List contents of a directory.
     *
     * @param string $directory The directory to list.
     * @param bool   $recursive Whether to list recursively.
     *
     * @return array A list of file metadata.
     */
    public function listContents(string $directory = '', bool $recursive = false);

    /**
     * Get a file's metadata.
     *
     * @param string $path The path to the file.
     *
     * @return array|false The file metadata or false on failure.
     * @throws NotExistsException
     *
     */
    public function getMetadata(string $path);

    /**
     * Get a file's size.
     *
     * @param string $path The path to the file.
     *
     * @return int|false The file size or false on failure.
     * @throws NotExistsException
     *
     */
    public function getSize($path);

    /**
     * Get a file's mime-type.
     *
     * @param string $path The path to the file.
     *
     * @return string|false The file mime-type or false on failure.
     * @throws NotExistsException
     *
     */
    public function getMimetype($path);

    /**
     * Get a file's timestamp.
     *
     * @param string $path The path to the file.
     *
     * @return string|false The timestamp or false on failure.
     * @throws NotExistsException
     *
     */
    public function getTimestamp($path);


    /**
     * Write a new file.
     *
     * @param string $path     The path of the new file.
     * @param string $contents The file contents.
     * @param array  $options  An optional configuration array.
     *                         ['no_touch'] = true|false
     *                         ['flags'] = FILE_USE_INCLUDE_PATH|FILE_APPEND|LOCK_EX
     *
     * @return bool True on success, false on failure.
     * @throws NotExistsException
     *
     */
    public function write(string $path, string $contents, array $options = []): bool;


    /**
     * Rename a file.
     *
     * @param string $path    Path to the existing file.
     * @param string $newPath The new path of the file.
     *
     * @return bool True on success, false on failure.
     * @throws NotExistsException Thrown if $path does not exist.
     *
     * @throws ExistsException   Thrown if $newpath exists.
     */
    public function rename(string $path, string $newPath);

    /**
     * Copy a file.
     *
     * @param string $path    Path to the existing file.
     * @param string $to The new path of the file.
     *
     * @return bool True on success, false on failure.
     * @throws NotExistsException Thrown if $path does not exist.
     *
     */
    public function copy(string $path, string $to):bool;

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool True on success, false on failure.
     * @throws NotExistsException
     *
     */
    public function delete(string $path):bool;


    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool True on success, false on failure.
     * @throws NotExistsException
     *
     */
    public function deleteDir(string $path):bool;

    /**
     * Create a directory.
     *
     * @param string $dirname The name of the new directory.
     * @param array  $options An optional configuration array.
     *
     * @return bool True on success, false on failure.
     */
    public function createDir(string $dirname, array $options = []):bool;

    /**
     * Set the visibility for a file.
     *
     * @param string $path       The path to the file.
     * @param string $visibility One of 'public' or 'private'.
     *
     * @return bool True on success, false on failure.
     * @throws NotExistsException
     *
     */
    public function setVisibility(string $path, string $visibility):bool;

    /**
     * Get a file's visibility.
     *
     * @param string $path The path to the file.
     *
     * @return string|false The visibility (public|private) or false on failure.
     * @throws NotExistsException
     *
     */
    public function getVisibility(string $path):string|false;

    /**
     * Create a file or update if exists.
     *
     * @param string   $path     The path to the file.
     * @param resource $resource The file handle.
     * @param array    $options  An optional configuration array.
     *
     * @return bool True on success, false on failure.
     * @throws \InvalidArgumentException Thrown if $resource is not a resource.
     *
     */

    /*  public function putStream($path, $resource, array $options = []);*/

    /**
     * @param string $path
     *
     * @return int|false if not exists
     */
    public function getMTime(string $path): int|false;
}
