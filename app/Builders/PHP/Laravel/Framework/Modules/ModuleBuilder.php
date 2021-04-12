<?php

namespace App\Builders\PHP\Laravel\Framework\Modules;

use App\Builders\Contracts\IBuildDelegator;

/**
 * Class ModuleBuilder
 * @package App\Builders\PHP\Laravel\Framework\Modules
 */
abstract class ModuleBuilder implements IBuildDelegator
{
    /**
     * @var array
     */
    protected array $processors = [];

    /**
     * @return IBuildDelegator
     */
    public function prepare(): IBuildDelegator
    {
        return $this;
    }

    /**
     * @return array
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }
}
