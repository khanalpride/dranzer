<?php

namespace App\Builders\PHP\Laravel\Framework\Modules;

use App\Builders\Processors\Modules\AuthModuleProcessor;

/**
 * Class AuthModuleBuilder
 * @package App\Builders\PHP\Laravel\Framework\Modules
 */
class AuthModuleBuilder extends ModuleBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        AuthModuleProcessor::class,
    ];
}
