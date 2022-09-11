<?php

declare(strict_types=1);

namespace Symbiotic\Http;

use Psr\Http\Message\StreamInterface;


class DownloadResponse extends Response
{

    public function __construct(
        StreamInterface $body,
        string $filename,
        int $status = 200,
        array $headers = [],
        string $version = '1.1',
        string $reason = null
    ) {
        /**
         * @todo: test validate headers
         */
        parent::__construct(
            $status,
            array_merge($headers, [
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . basename($filename) . '"',
                'Content-Transfer-Encoding' => 'binary',
                'Expires' => '0',
                'Cache-Control' => 'must-revalidate',
                'Pragma' => 'public',
                'Content-Length' => $body->getSize(),
            ]),
            $body,
            $version,
            $reason
        );
    }
}