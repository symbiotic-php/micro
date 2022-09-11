<?php
declare(strict_types=1);

namespace Symbiotic\Core\Support;

interface RenderableInterface extends \Stringable
{
    public function render();

    public function __toString():string;
}