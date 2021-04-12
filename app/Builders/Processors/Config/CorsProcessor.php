<?php

namespace App\Builders\Processors\Config;

use Closure;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\Config\CorsBuilder;

/**
 * Class CorsProcessor
 * @package App\Builders\Processors\Config
 */
class CorsProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this->processAuth($builder);

        $next($builder);

        return true;
    }

    /**
     * @param CorsBuilder $builder
     * @return void
     */
    private function processAuth(CorsBuilder $builder): void
    {
        $apiConfig = app('mutations')->for('api');

        $generateAPI = $apiConfig['generate'];

        $sanctumAuth = $apiConfig['sanctumAuth'] ?? true;

        if ($generateAPI && $sanctumAuth) {
            $builder->setSupportsCredentials(true);
        }

    }
}
