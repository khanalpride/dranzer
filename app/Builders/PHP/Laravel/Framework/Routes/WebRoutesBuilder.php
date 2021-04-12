<?php

namespace App\Builders\PHP\Laravel\Framework\Routes;

use App\Builders\Processors\Routes\WebRoutesProcessor;

/**
 * Class WebRoutesBuilder
 * @package App\Builders\PHP\Laravel\Framework\Routes
 */
class WebRoutesBuilder extends RoutesBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        WebRoutesProcessor::class,
    ];

    /**
     * @var string
     */
    protected string $filename = 'web.php';
}
