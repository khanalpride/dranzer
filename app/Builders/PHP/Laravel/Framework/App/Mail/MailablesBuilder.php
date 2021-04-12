<?php

namespace App\Builders\PHP\Laravel\Framework\App\Mail;

use App\Builders\PHP\ClassBuilder;
use App\Builders\Processors\App\Mail\MailablesProcessor;

class MailablesBuilder extends ClassBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        MailablesProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $namespace = 'App\Mail';
}
