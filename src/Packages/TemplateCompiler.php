<?php

declare(strict_types=1);

namespace Symbiotic\Packages;

use Symbiotic\Mimetypes\MimeTypesMini;

class TemplateCompiler
{

    /**
     * ['ext' => compilerObject,...]
     * @var array
     */
    protected array $extensions = [];

    /**
     * @param TemplateCompilerInterface $compiler
     */
    public function addCompiler(TemplateCompilerInterface $compiler): void
    {
        // todo: нужно сделать по именам
        foreach ($compiler->getExtensions() as $v) {
            $this->extensions[$v] = $compiler;
        }
    }

    /**
     * @param string $path     The path to the file or its name to determine the template compiler
     * @param string $template The content of the file to convert
     *
     * @return string  html / php Valid code to execute via include or eval
     *
     * @see  https://www.php.net/manual/en/function.include.php
     * @see  https://www.php.net/manual/en/function.eval.php
     */
    public function compile(string $path, string $template): string
    {
        $ext = (new MimeTypesMini())->findExtension($path, array_keys($this->extensions));
        return ($ext !== false) ? $this->extensions[$ext]->compile($template) : $template;
    }
}