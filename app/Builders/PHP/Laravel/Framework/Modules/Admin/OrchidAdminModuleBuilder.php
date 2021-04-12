<?php

namespace App\Builders\PHP\Laravel\Framework\Modules\Admin;

use App\Builders\Contracts\IBuildDelegator;
use App\Builders\Processors\Modules\OrchidAdminModuleProcessor;

/**
 * Class OrchidAdminModuleBuilder
 * @package App\Builders\PHP\Laravel\Framework\Modules\Admin
 */
class OrchidAdminModuleBuilder implements IBuildDelegator
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        OrchidAdminModuleProcessor::class,
    ];

    /**
     * @return $this
     */
    public function prepare(): OrchidAdminModuleBuilder
    {
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }
}
