<?php

declare(strict_types=1);

namespace Symbiotic\Http;

use Symbiotic\Core\Support\RenderableInterface;
use Psr\Http\Message\ResponseInterface;


class ResponseSender implements RenderableInterface
{
    /**
     * @var ResponseInterface
     */
    protected ResponseInterface $response;

    /**
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Send headers and the body of the response to the output
     *
     * @return void
     */
    public function render(): void
    {
        $response = $this->response;

        $http_line = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        header($http_line, true, $response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }

        $stream = $response->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        while (!$stream->eof()) {
            echo $stream->read(1024 * 8);
        }
        $stream->close();
    }

    public function __toString(): string
    {
        ob_start();
        $this->render();
        return ob_get_clean();
    }


}