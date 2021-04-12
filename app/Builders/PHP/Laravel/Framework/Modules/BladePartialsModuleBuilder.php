<?php

namespace App\Builders\PHP\Laravel\Framework\Modules;

use App\Builders\Processors\Modules\BladePartialsModuleProcessor;

/**
 * Class BladePartialsModuleBuilder
 * @package App\Builders\PHP\Laravel\Framework\Modules
 */
class BladePartialsModuleBuilder extends ModuleBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        BladePartialsModuleProcessor::class,
    ];
}
