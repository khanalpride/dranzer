<?php

namespace App\Builders\PHP\Laravel\Framework\Modules;

use App\Builders\Processors\Modules\VueModuleProcessor;

/**
 * Class VueModuleBuilder
 * @package App\Builders\PHP\Laravel\Framework\Modules
 */
class VueModuleBuilder extends ModuleBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        VueModuleProcessor::class,
    ];
}
