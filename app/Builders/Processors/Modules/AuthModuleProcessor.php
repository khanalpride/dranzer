<?php

namespace App\Builders\Processors\Modules;

use Closure;
use Illuminate\Support\Facades\File;
use App\Builders\Processors\PHPBuilderProcessor;

/**
 * Class AuthModuleProcessor
 * @package App\Builders\Processors\Modules
 */
class AuthModuleProcessor extends PHPBuilderProcessor
{
    /**
     * @var string
     */
    private string $staticAssetsDir;
    /**
     * @var string
     */
    private string $projectRoot;

    /**
     * @param $builder
     * @param Closure $next
     * @return bool
     */
    public function process($builder, Closure $next): bool
    {
        $authMutations = app('mutations')->for('auth');

        $authEnabled = $authMutations['config']['enabled'];

        if ($authEnabled) {
            $authModule = $authMutations['module'];

            $this->staticAssetsDir = app('static-assets');
            $this->projectRoot = app('project-dir');

            if ($authModule === 'ui') {
                $this->processLaravelUI($authMutations['ui']);
            }

            if ($authModule === 'breeze') {
                $this->processLaravelBreeze($authMutations['breeze']);
            }

            if ($authModule === 'fortify') {
                $this->processFortify($authMutations['fortify']);
            }
        }

        $next($builder);

        return true;
    }

    /**
     * @param $config
     * @return void
     */
    private function processFortify($config): void
    {
        $this->createLaravelFortifyAuthScaffolding($config);

    }

    /**
     * @param $config
     * @return void
     */
    private function processLaravelBreeze($config): void
    {
        $this->createLaravelBreezeAuthScaffolding($config);

    }

    /**
     * @param $moduleConfig
     * @return void
     */
    private function processLaravelUI($moduleConfig): void
    {
        $this->createLaravelUIAuthScaffolding($moduleConfig);

    }

    /**
     * @param $config
     * @return void
     */
    private function createLaravelBreezeAuthScaffolding($config): void
    {
        $projectRoot = $this->projectRoot;
        $staticAssetsDir = $this->staticAssetsDir;

        $allowResets = $config['resets'];
        $verifyEmails = $config['verify'];
        $allowRegistration = $config['registration'];

        $rootControllersPath = $projectRoot . '/app/Http/Controllers';
        $controllersPath = $rootControllersPath . '/Auth';

        File::ensureDirectoryExists($controllersPath);
        File::ensureDirectoryExists($projectRoot . '/app/Http/Requests');
        File::ensureDirectoryExists($projectRoot . '/app/View');

        File::copyDirectory($staticAssetsDir . '/app/Http/Requests/Breeze/', $projectRoot . '/app/Http/Requests/');
        File::copyDirectory($staticAssetsDir . '/app/View/Breeze/', $projectRoot . '/app/View/');

        // Controllers
        File::copy($staticAssetsDir . '/app/Http/Controllers/Breeze/ConfirmablePasswordController.php', $controllersPath . '/ConfirmablePasswordController.php');
        File::copy($staticAssetsDir . '/app/Http/Controllers/Breeze/AuthenticatedSessionController.php', $controllersPath . '/AuthenticatedSessionController.php');

        if ($allowResets) {
            File::copy($staticAssetsDir . '/app/Http/Controllers/Breeze/PasswordResetLinkController.php', $controllersPath . '/PasswordResetLinkController.php');
            File::copy($staticAssetsDir . '/app/Http/Controllers/Breeze/NewPasswordController.php', $controllersPath . '/NewPasswordController.php');
            File::copy(
                $staticAssetsDir . '/database/migrations/breeze/2014_10_12_100000_create_password_resets_table.php',
                $projectRoot . '/database/migrations/2014_10_12_100000_create_password_resets_table.php'
            );
        }

        if ($allowRegistration) {
            File::copy($staticAssetsDir . '/app/Http/Controllers/Breeze/RegisteredUserController.php', $controllersPath . '/RegisteredUserController.php');
        }

        if ($verifyEmails) {
            File::copy($staticAssetsDir . '/app/Http/Controllers/Breeze/EmailVerificationNotificationController.php', $controllersPath . '/EmailVerificationNotificationController.php');
            File::copy($staticAssetsDir . '/app/Http/Controllers/Breeze/EmailVerificationPromptController.php', $controllersPath . '/EmailVerificationPromptController.php');
            File::copy($staticAssetsDir . '/app/Http/Controllers/Breeze/VerifyEmailController.php', $controllersPath . '/VerifyEmailController.php');
        }

        File::ensureDirectoryExists($projectRoot . '/resources/css/');
        File::ensureDirectoryExists($projectRoot . '/resources/js/');
        File::ensureDirectoryExists($projectRoot . '/resources/views/');
        File::ensureDirectoryExists($projectRoot . '/resources/views/auth');
        File::ensureDirectoryExists($projectRoot . '/resources/views/components');
        File::ensureDirectoryExists($projectRoot . '/resources/views/layouts');

        File::copyDirectory($staticAssetsDir . '/resources/css/breeze/', $projectRoot . '/resources/css/');
        File::copyDirectory($staticAssetsDir . '/resources/js/breeze/', $projectRoot . '/resources/js/');

        // Views
        File::copyDirectory($staticAssetsDir . '/resources/views/breeze/components/', $projectRoot . '/resources/views/components/');
        File::copyDirectory($staticAssetsDir . '/resources/views/breeze/layouts/', $projectRoot . '/resources/views/layouts/');

        File::copy($staticAssetsDir . '/resources/views/breeze/auth/confirm-password.blade.php', $projectRoot . '/resources/views/auth/confirm-password.blade.php');

        if ($allowResets) {
            File::copy($staticAssetsDir . '/resources/views/breeze/auth/login.blade.php', $projectRoot . '/resources/views/auth/login.blade.php');
            File::copy($staticAssetsDir . '/resources/views/breeze/auth/reset-password.blade.php', $projectRoot . '/resources/views/auth/reset-password.blade.php');
            File::copy($staticAssetsDir . '/resources/views/breeze/auth/forgot-password.blade.php', $projectRoot . '/resources/views/auth/forgot-password.blade.php');
        } else {
            File::copy($staticAssetsDir . '/resources/views/breeze/auth/login-no-reset.blade.php', $projectRoot . '/resources/views/auth/login.blade.php');
        }

        if ($allowRegistration) {
            File::copy($staticAssetsDir . '/resources/views/breeze/auth/register.blade.php', $projectRoot . '/resources/views/auth/register.blade.php');
        }

        if ($verifyEmails) {
            File::copy($staticAssetsDir . '/resources/views/breeze/auth/verify-email.blade.php', $projectRoot . '/resources/views/auth/verify-email.blade.php');
        }

        File::copy($staticAssetsDir . '/resources/views/breeze/dashboard.blade.php', $projectRoot . '/resources/views/dashboard.blade.php');

        // Routes
        if ($allowRegistration) {
            if ($allowResets && $verifyEmails) {
                File::copy($staticAssetsDir . '/routes/breeze/auth.php', $projectRoot . '/routes/auth.php');
            }

            if (!$allowResets && !$verifyEmails) {
                File::copy($staticAssetsDir . '/routes/breeze/auth-no-reset-no-verify.php', $projectRoot . '/routes/auth.php');
            }

            if (!$allowResets && $verifyEmails) {
                File::copy($staticAssetsDir . '/routes/breeze/auth-no-reset.php', $projectRoot . '/routes/auth.php');
            }

            if ($allowResets && !$verifyEmails) {
                File::copy($staticAssetsDir . '/routes/breeze/auth-no-verify.php', $projectRoot . '/routes/auth.php');
            }
        } else if (!$allowResets) {
            File::copy($staticAssetsDir . '/routes/breeze/auth-no-register-no-reset.php', $projectRoot . '/routes/auth.php');
        } else {
            File::copy($staticAssetsDir . '/routes/breeze/auth-no-register.php', $projectRoot . '/routes/auth.php');
        }

    }

    /**
     * @param $config
     * @return void
     */
    private function createLaravelFortifyAuthScaffolding($config): void
    {
        $projectRoot = $this->projectRoot;
        $staticAssetsDir = $this->staticAssetsDir;

        $allowResets = $config['resets'];
        $twoFactor = $config['twoFactor'];

        File::copyDirectory($staticAssetsDir . '/app/Actions/Fortify/', $projectRoot . '/app/Actions');
        File::copy($staticAssetsDir . '/app/Providers/Fortify/FortifyServiceProvider.php', $projectRoot . '/app/Providers/FortifyServiceProvider.php');

        if ($allowResets) {
            File::copy($staticAssetsDir . '/database/migrations/fortify/2014_10_12_100000_create_password_resets_table.php', $projectRoot . '/database/migrations/2014_10_12_100000_create_password_resets_table.php');
        }

        if ($twoFactor) {
            File::copy($staticAssetsDir . '/database/migrations/fortify/2014_10_12_200000_add_two_factor_columns_to_users_table.php', $projectRoot . '/database/migrations/2014_10_12_200000_add_two_factor_columns_to_users_table.php');
        }

    }

    /**
     * @param $config
     * @return void
     */
    private function createLaravelUIAuthScaffolding($config): void
    {
        $projectRoot = $this->projectRoot;
        $staticAssetsDir = $this->staticAssetsDir;

        $uiLibrary = $config['library'] ?? 'vue';

        $allowResets = $config['resets'];
        $verifyEmails = $config['verify'];
        $allowRegistration = $config['registration'];

        $rootControllersPath = $projectRoot . '/app/Http/Controllers';
        $authControllersPath = $rootControllersPath . '/Auth';
        $rootViewsPath = $projectRoot . '/resources/views';
        $rootJsPath = $projectRoot . '/resources/js';
        $viewsPath = $rootViewsPath . '/auth';
        $layoutsPath = $rootViewsPath . '/layouts';
        $sassPath = $projectRoot . '/resources/sass';

        // Sass
        File::ensureDirectoryExists($sassPath);
        File::copyDirectory($staticAssetsDir . '/resources/sass/laravel-ui', $sassPath . '/');

        // Controllers
        File::ensureDirectoryExists($authControllersPath);

        File::copy($staticAssetsDir . '/app/Http/Controllers/LaravelUI/ConfirmPasswordController.php', $authControllersPath . '/ConfirmPasswordController.php');
        File::copy($staticAssetsDir . '/app/Http/Controllers/LaravelUI/LoginController.php', $authControllersPath . '/LoginController.php');
        File::copy($staticAssetsDir . '/app/Http/Controllers/LaravelUI/HomeController.php', $rootControllersPath . '/HomeController.php');

        if ($allowResets) {
            File::copy($staticAssetsDir . '/app/Http/Controllers/LaravelUI/ResetPasswordController.php', $authControllersPath . '/ResetPasswordController.php');
            File::copy($staticAssetsDir . '/app/Http/Controllers/LaravelUI/ForgotPasswordController.php', $authControllersPath . '/ForgotPasswordController.php');
            File::copy(
                $staticAssetsDir . '/database/migrations/laravel-ui/2014_10_12_100000_create_password_resets_table.php',
                $projectRoot . '/database/migrations/2014_10_12_100000_create_password_resets_table.php'
            );
        }

        if ($allowRegistration) {
            File::copy($staticAssetsDir . '/app/Http/Controllers/LaravelUI/RegisterController.php', $authControllersPath . '/RegisterController.php');
        }

        if ($verifyEmails) {
            File::copy($staticAssetsDir . '/app/Http/Controllers/LaravelUI/VerificationController.php', $authControllersPath . '/VerificationController.php');
        }

        // JS
        if (!File::exists($rootJsPath)) {
            File::makeDirectory($rootJsPath, 493, true);
        }

        File::copy($staticAssetsDir . '/resources/js/laravel-ui/bootstrap.js', $rootJsPath . '/bootstrap.js');

        File::copy($staticAssetsDir . "/resources/js/laravel-ui/app-$uiLibrary.js", $rootJsPath . '/app.js');

        // Views
        File::ensureDirectoryExists($layoutsPath);
        File::ensureDirectoryExists($viewsPath);
        File::ensureDirectoryExists($viewsPath . '/passwords');

        File::copy($staticAssetsDir . '/resources/views/laravel-ui/home.blade.php', $rootViewsPath . '/home.blade.php');
        File::copy($staticAssetsDir . '/resources/views/laravel-ui/layouts/app.blade.php', $layoutsPath . '/app.blade.php');

        if ($allowResets) {
            File::copy($staticAssetsDir . '/resources/views/laravel-ui/passwords/confirm.blade.php', $viewsPath . '/passwords/confirm.blade.php');
            File::copy($staticAssetsDir . '/resources/views/laravel-ui/login.blade.php', $viewsPath . '/login.blade.php');
            File::copy($staticAssetsDir . '/resources/views/laravel-ui/passwords/reset.blade.php', $viewsPath . '/passwords/reset.blade.php');
            File::copy($staticAssetsDir . '/resources/views/laravel-ui/passwords/email.blade.php', $viewsPath . '/passwords/email.blade.php');
        } else {
            File::copy($staticAssetsDir . '/resources/views/laravel-ui/passwords/confirm-no-reset.blade.php', $viewsPath . '/passwords/confirm.blade.php');
            File::copy($staticAssetsDir . '/resources/views/laravel-ui/login-no-reset.blade.php', $viewsPath . '/login.blade.php');
        }

        if ($allowRegistration) {
            File::copy($staticAssetsDir . '/resources/views/laravel-ui/register.blade.php', $viewsPath . '/register.blade.php');
        }

        if ($verifyEmails) {
            File::copy($staticAssetsDir . '/resources/views/laravel-ui/verify.blade.php', $viewsPath . '/verify.blade.php');
        }

    }
}
