<?php

namespace App\Builders\Processors;

use Closure;
use App\Builders\PHP\Laravel\ArtisanCommands;
use App\Builders\Helpers\Mutations\UIMutationHelpers;
use App\Builders\PHP\Laravel\Framework\ComposerBuilder;

/**
 * Class ComposerProcessor
 * @package App\Builders\Processors
 */
class ComposerProcessor extends PHPBuilderProcessor
{
    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $this
            ->processSentry($builder)
            ->processMiddlewares($builder)
            ->processTelescope($builder)
            ->processHorizon($builder)
            ->processAdminPanel($builder)
            ->processDecomposer($builder)
            ->processDebugBar($builder)
            ->processIdeHelper($builder)
            ->processCookieConsent($builder)
            ->processAuth($builder)
            ->processAPI($builder)
            ->processNotifications($builder);

        $next($builder);

        return true;
    }

    /**
     * @param ComposerBuilder $builder
     * @return ComposerProcessor
     */
    private function processAPI(ComposerBuilder $builder): ComposerProcessor
    {
        $apiConfig = app('mutations')->for('api');

        $generateAPI = $apiConfig['generate'];

        // Orion
        if ($generateAPI) {
            $builder->addRequired('tailflow/laravel-orion', '^1.2');
        }

        // JWT
        if ($generateAPI && $apiConfig['jwtAuth']) {
            // TODO: Stop using develop branch once php 8.0 is supported.
            $builder->addRequired('tymon/jwt-auth', 'dev-develop');

            ArtisanCommands::add('vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"');

            ArtisanCommands::add('jwt:secret --force');
        }

        // Sanctum
        $sanctumAuth = $apiConfig['sanctumAuth'] ?? true;

        if ($generateAPI && $sanctumAuth) {
            $builder->addRequired('laravel/sanctum', '^2.9.0');
        }

        return $this;
    }

    /**
     * @param ComposerBuilder $builder
     * @return ComposerProcessor
     */
    private function processAdminPanel(ComposerBuilder $builder): ComposerProcessor
    {
        // Orchid
        if (UIMutationHelpers::installOrchid()) {
            $builder
                ->addRequired('orchid/platform', '^9.13')
                ->addRequired('algolia/algoliasearch-client-php', '^2.7');
        }

        return $this;
    }

    /**
     * @param ComposerBuilder $builder
     * @return ComposerProcessor
     */
    private function processAuth(ComposerBuilder $builder): ComposerProcessor
    {
        $authConfig = app('mutations')->for('auth');

        $authEnabled = $authConfig['config']['enabled'];

        if (!$authEnabled) {
            return $this;
        }

        $authModule = $authConfig['module'];

        if ($authModule === 'ui') {
            $builder->addRequired('laravel/ui', '^3.1');
        }

        if ($authModule === 'breeze') {
            $builder->addRequired('laravel/breeze', '^1.0');
        }

        if ($authModule === 'fortify') {
            $builder->addRequired('laravel/fortify', '^1.7');
        }

        return $this;
    }

    /**
     * @param ComposerBuilder $builder
     * @return ComposerProcessor
     */
    private function processCookieConsent(ComposerBuilder $builder): ComposerProcessor
    {
        $cookieConsent = app('mutations')->for('compliance')['cookieConsent'];

        $installCookieConsentPackage = $cookieConsent['install'];

        if ($installCookieConsentPackage) {
            $publishLang = $cookieConsent['publishLang'] ?? false;
            $publishViews = $cookieConsent['publishViews'] ?? false;

            $builder->addRequiredDev('spatie/laravel-cookie-consent', '^2.12');

            ArtisanCommands::add(
                'vendor:publish --provider="Spatie\CookieConsent\CookieConsentServiceProvider" --tag="config"'
            );

            if ($publishLang) {
                ArtisanCommands::add(
                    'vendor:publish --provider="Spatie\CookieConsent\CookieConsentServiceProvider" --tag="lang"'
                );
            }

            if ($publishViews) {
                ArtisanCommands::add(
                    'vendor:publish --provider="Spatie\CookieConsent\CookieConsentServiceProvider" --tag="views"'
                );
            }
        }

        return $this;
    }

    /**
     * @param ComposerBuilder $builder
     * @return ComposerProcessor
     */
    private function processDebugBar(ComposerBuilder $builder): ComposerProcessor
    {
        $installDebugBar = app('mutations')->for('devTools')['installDebugBar'];

        if ($installDebugBar) {
            $builder->addRequiredDev('barryvdh/laravel-debugbar', '^3.5');
        }

        return $this;
    }

    /**
     * @param ComposerBuilder $builder
     * @return ComposerProcessor
     */
    private function processDecomposer(ComposerBuilder $builder): ComposerProcessor
    {
        $installDecomposer = app('mutations')->for('devTools')['installDecomposer'];

        if ($installDecomposer) {
            $builder->addRequiredDev('nguyentranchung/laravel-decomposer', '^1.2');
        }

        return $this;
    }

    /**
     * @param ComposerBuilder $builder
     * @return ComposerProcessor
     */
    private function processHorizon(ComposerBuilder $builder): ComposerProcessor
    {
        $installHorizon = app('mutations')->for('queues')['packages']['installHorizon'];

        if ($installHorizon) {
            $builder->addRequired('laravel/horizon', '^5.6');

            ArtisanCommands::add('horizon:install');
        }

        return $this;
    }

    /**
     * @param ComposerBuilder $builder
     * @return ComposerProcessor
     */
    private function processIdeHelper(ComposerBuilder $builder): ComposerProcessor
    {
        $installIdeHelper = app('mutations')->for('devTools')['installIdeHelper'];

        if ($installIdeHelper) {
            $builder->addRequiredDev('barryvdh/laravel-ide-helper', '^2.8');

            ArtisanCommands::add('ide-helper:generate');

            ArtisanCommands::add('ide-helper:models --write');

            ArtisanCommands::add('ide-helper:meta');
        }

        return $this;
    }

    /**
     * @param ComposerBuilder $builder
     * @return ComposerProcessor
     */
    private function processMiddlewares(ComposerBuilder $builder): ComposerProcessor
    {
        $middlewares = app('mutations')->for('middlewares');

        $minifyHtml = $middlewares['minifyHtml'] ?? true;

        if ($minifyHtml) {
            $builder->addRequired('htmlmin/htmlmin', 'dev-master');

            // Copy the htmlmin config file.
            file_put_contents(
                $builder->getOutputDir() . '/config/htmlmin.php',
                file_get_contents(app('static-assets') . '/config/htmlmin.php')
            );

            ArtisanCommands::add('vendor:publish --provider="HTMLMin\HTMLMin\HTMLMinServiceProvider"');
        }

        return $this;
    }

    /**
     * @param ComposerBuilder $builder
     * @return void
     */
    private function processNotifications(ComposerBuilder $builder): void
    {
        $notificationConfig = app('mutations')->for('notifications');

        $notifications = collect($notificationConfig['notifications']);

        $addSlackNotificationPackage = $notifications->first(
                fn ($n) => $n['via']['slack']['enabled'] ?? false
            ) !== null;

        if ($addSlackNotificationPackage) {
            $builder->addRequired('laravel/slack-notification-channel', '^2.3');
        }

        $addNexmoPackages = $notifications->first(fn ($n) => $n['via']['sms']['enabled'] ?? false) !== null;

        if ($addNexmoPackages) {
            $builder->addRequired('laravel/nexmo-notification-channel', '^2.5');
            $builder->addRequired('nexmo/laravel', '^2.4');
        }

    }

    /**
     * @param ComposerBuilder $builder
     * @return ComposerProcessor
     */
    private function processSentry(ComposerBuilder $builder): ComposerProcessor
    {
        $exceptionsConfig = app('mutations')->for('exceptions');

        $isSentryEnabled = $exceptionsConfig['sentry']['enabled'] ?? false;

        if ($isSentryEnabled) {
            $builder->addRequired('sentry/sentry-laravel', '^2.4');

            ArtisanCommands::add('vendor:publish --provider="Sentry\Laravel\ServiceProvider"');
        }

        return $this;
    }

    /**
     * @param ComposerBuilder $builder
     * @return ComposerProcessor
     */
    private function processTelescope(ComposerBuilder $builder): ComposerProcessor
    {
        $installTelescope = app('mutations')->for('logging')['packages']['installTelescope'];

        if ($installTelescope) {
            $builder
                ->addRequiredDev('laravel/telescope', '^4.4')
                ->dontDiscoverPackage('laravel/telescope');

            ArtisanCommands::add('telescope:install');

            ArtisanCommands::add('vendor:publish --tag=telescope-migrations');
        }

        return $this;
    }
}
