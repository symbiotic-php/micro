<?php

declare(strict_types=1);

namespace Symbiotic\View;


use Symbiotic\Core\SymbioticException;

final class ViewException extends SymbioticException
{

    /**
     * @var string
     */
    private string $template = "";

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function create(
        string $template,
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null
    ): ViewException {
        $e = new ViewException($message, $code, $previous);
        $e->setTemplate($template);
        return $e;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }


}