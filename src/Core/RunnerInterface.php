<?php

declare(strict_types=1);

namespace Symbiotic\Core;


interface RunnerInterface
{
    /**
     * @return bool
     */
    public function isHandle(): bool;

    /**
     * Returns the result of the handler operation
     *
     * upon successful completion, the work will be completed and the {@see CoreInterface::runComplete()} event will be
     * executed if unsuccessful, the handler {@see CoreInterface::ruNext()} will be launched and the script will
     * continue to work
     *
     * @return bool
     */
    public function run(): bool;
}
