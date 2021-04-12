<?php

namespace App\Builders\PHP\Laravel\Framework\Routes;

use App\Builders\Processors\Routes\APIRoutesProcessor;

/**
 * Class APIRoutesBuilder
 * @package App\Builders\PHP\Laravel\Framework\Routes
 */
class APIRoutesBuilder extends RoutesBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        APIRoutesProcessor::class,
    ];

    /**
     * @var string
     */
    protected string $filename = 'api.php';
}
