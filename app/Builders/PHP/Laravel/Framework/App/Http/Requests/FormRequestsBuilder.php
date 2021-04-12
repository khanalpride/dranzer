<?php

namespace App\Builders\PHP\Laravel\Framework\App\Http\Requests;

use App\Builders\PHP\ClassBuilder;
use App\Builders\Processors\App\Http\Requests\FormRequestsProcessor;

class FormRequestsBuilder extends ClassBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        FormRequestsProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $namespace = 'App\Http\Requests';
}
