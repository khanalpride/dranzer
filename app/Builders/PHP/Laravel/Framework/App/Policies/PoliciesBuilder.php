<?php

namespace App\Builders\PHP\Laravel\Framework\App\Policies;

use App\Builders\PHP\ClassBuilder;
use App\Builders\Processors\App\Policies\PoliciesProcessor;

class PoliciesBuilder extends ClassBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        PoliciesProcessor::class,
    ];
    /**
     * @var string|null
     */
    protected string $namespace = 'App\\Policies';
}
