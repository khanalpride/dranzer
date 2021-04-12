<?php

namespace App\Builders\Processors\Contracts;

use Closure;

/**
 * Interface IBuilderProcessor
 * @package App\Builders\Processors\Contracts
 */
interface IBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool;
}
