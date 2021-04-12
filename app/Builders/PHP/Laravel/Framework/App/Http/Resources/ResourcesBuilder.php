<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http\Resources;

use App\Builders\PHP\ClassBuilder;
use App\Builders\Processors\App\Http\Resources\ResourcesProcessor;

class ResourcesBuilder extends ClassBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        ResourcesProcessor::class
    ];
    /**
     * @var string
     */
    protected string $namespace = 'App\Http\Resources';
}
