<?php

declare(strict_types=1);

namespace Symbiotic\Http;

use Psr\Http\Message\UriInterface;

class UriHelper
{
    /**
     * @param string       $prefix
     * @param UriInterface $uri
     *
     * @return UriInterface
     */
    public function deletePrefix(string $prefix, UriInterface $uri): UriInterface
    {
        $prefix = $this->normalizePrefix($prefix);
        if (!empty($prefix)) {
            $path = $uri->getPath();
            $path = preg_replace('~^' . preg_quote($prefix, '~') . '~', '', $path);
            $uri = $uri->withPath($path);
        }

        return $uri;
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    public function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix, ' \\/');

        return $prefix == '' ? '' : '/' . $prefix;
    }
}