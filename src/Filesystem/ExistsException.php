<?php
declare(strict_types=1);

namespace Symbiotic\Filesystem;


use Throwable;

class ExistsException extends FilesystemException
{

    public function __construct(string $message = "", $code = 0, Throwable $previous = null)
    {
        $message = 'File not exists['.$message.']!';
        parent::__construct($message, $code, $previous);
    }
}
