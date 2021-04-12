<?php

namespace App\Builders\PHP\Laravel\Framework\App\Console\Commands;

use App\Builders\PHP\ClassBuilder;
use App\Builders\Processors\App\Console\Commands\ConsoleCommandsProcessor;

class ConsoleCommandsBuilder extends ClassBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        ConsoleCommandsProcessor::class,
    ];

    protected string $namespace = 'App\Console\Commands';
}
