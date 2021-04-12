<?php

namespace App\Builders\Processors\App\Providers;

use Closure;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\App\Providers\RouteServiceProviderBuilder;

/**
 * Class RouteServiceProviderProcessor
 * @package App\Builders\Processors\App\Providers
 */
class RouteServiceProviderProcessor extends PHPBuilderProcessor
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
     * @param RouteServiceProviderBuilder $builder
     * @return void
     */
    private function processAuth(RouteServiceProviderBuilder $builder): void
    {
        $authMutations = app('mutations')->for('auth');

        $authEnabled = $authMutations['config']['enabled'];

        if ($authEnabled) {
            $authModule = $authMutations['module'];
            if ($authModule === 'breeze') {
                $builder->setHome('/dashboard');
            }
        }

    }
}
