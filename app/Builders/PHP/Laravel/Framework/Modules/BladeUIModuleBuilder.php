<?php

namespace App\Builders\PHP\Laravel\Framework\Modules;

use App\Builders\Processors\Modules\BladeUIModuleProcessor;

/**
 * Class BladeUIModuleBuilder
 * @package App\Builders\PHP\Laravel\Framework\Resources\Builders
 */
class BladeUIModuleBuilder extends ModuleBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        BladeUIModuleProcessor::class,
    ];
}
