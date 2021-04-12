<?php

namespace App\Builders\Contracts;

/**
 * Interface IBuildDelegator
 * @package App\Builders\Contracts
 */
interface IBuildDelegator
{
    /**
     * @return $this
     */
    public function prepare(): self;

    /**
     * @return array
     */
    public function getProcessors(): array;
}
