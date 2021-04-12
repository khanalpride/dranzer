<?php

namespace App\Builders\Processors\Config;

use Closure;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\Config\AuthBuilder;

/**
 * Class AuthProcessor
 * @package App\Builders\Processors\Config
 */
class AuthProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this->processAPI($builder);

        $next($builder);

        return true;
    }

    /**
     * @param AuthBuilder $builder
     * @return void
     */
    private function processAPI(AuthBuilder $builder): void
    {
        $apiConfig = app('mutations')->for('api');

        if ($apiConfig['generate'] && $apiConfig['jwtAuth']) {
            $builder->setApiDriver('jwt');
        }

    }
}
