<?php

namespace App\Builders\Processors\Config;

use Closure;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\Helpers\Mutations\UIMutationHelpers;
use App\Builders\PHP\Laravel\Framework\Config\AppBuilder;

/**
 * Class AppProcessor
 * @package App\Builders\Processors\Config
 */
class AppProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this->processServiceProviders($builder);

        $next($builder);

        return true;
    }

    /**
     * @param AppBuilder $builder
     * @return void
     */
    private function processServiceProviders(AppBuilder $builder): void
    {
        $installAdmin = UIMutationHelpers::installAdmin();

        $roles = app('mutations')->for('authorization')['roles'] ?? [];

        if ($installAdmin && count($roles)) {
            $builder->addApplicationServiceProvider($this->const('App\Providers\PermissionServiceProvider::class'));
        }

    }
}
