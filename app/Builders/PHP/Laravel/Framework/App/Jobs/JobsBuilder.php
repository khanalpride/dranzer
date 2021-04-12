<?php

namespace App\Builders\PHP\Laravel\Framework\App\Jobs;

use App\Builders\PHP\ClassBuilder;
use App\Builders\Processors\App\Jobs\JobsProcessor;

class JobsBuilder extends ClassBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        JobsProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $namespace = 'App\Jobs';
}
