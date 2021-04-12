<?php

namespace App\Builders\Processors\Config;

use Closure;
use App\Builders\Processors\PHPBuilderProcessor;
use App\Builders\PHP\Laravel\Framework\Config\FortifyBuilder;

/**
 * Class FortifyProcessor
 * @package App\Builders\Processors\Config
 */
class FortifyProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this->processFeatures($builder);

        $next($builder);

        return true;
    }

    /**
     * @param FortifyBuilder $builder
     * @return void
     */
    private function processFeatures(FortifyBuilder $builder): void
    {
        $authMutations = app('mutations')->for('auth');

        $authModule = $authMutations['module'];

        if (!$authMutations['install'] || $authModule !== 'fortify') {
            $builder->setCanBuild(false);
            return;
        }

        $fortifyConfig = $authMutations['fortify'];

        $builder->setViewRoutes(!$fortifyConfig['disableViewRoutes']);

        $registration = $fortifyConfig['registration'];
        $resets = $fortifyConfig['resets'];
        $verify = $fortifyConfig['verify'];
        $update = $fortifyConfig['update'];
        $twoFactor = $fortifyConfig['twoFactor'];

        if ($registration) {
            $builder->addFeature($this->staticCall('Features', 'registration'));
        }

        if ($resets) {
            $builder->addFeature($this->staticCall('Features', 'resetPasswords'));
        }

        if ($verify) {
            $builder->addFeature($this->staticCall('Features', 'emailVerification'));
        }

        if ($update) {
            $builder->addFeature($this->staticCall('Features', 'updatePasswords'));
        }

        if ($twoFactor) {
            $builder->addFeature($this->staticCall('Features', 'twoFactorAuthentication', [
                $this->arr([$this->assoc('confirmPassword', $this->const(true))])
            ]));
        }

    }
}
