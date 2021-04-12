<?php

namespace App\Builders\PHP\Laravel\Framework\Modules;

use App\Builders\Processors\Modules\APIModuleProcessor;

/**
 * Class APIModuleBuilder
 * @package App\Builders\PHP\Laravel\Framework\Modules
 */
class APIModuleBuilder extends ModuleBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        APIModuleProcessor::class,
    ];
}
