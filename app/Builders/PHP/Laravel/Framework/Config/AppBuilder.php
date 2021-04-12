<?php

/** @noinspection SpellCheckingInspection */

namespace App\Builders\PHP\Laravel\Framework\Config;

use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ConstFetch;
use Illuminate\Encryption\Encrypter;
use App\Builders\Processors\Config\AppProcessor;

/**
 * Class AppBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class AppBuilder extends FileBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        AppProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'app.php';
    /**
     * @var string
     */
    private $defaultAppName = 'Laravel';
    /**
     * @var string
     */
    private $defaultAppEnvironment = 'local';
    /**
     * @var bool
     */
    private $isInDebugMode = true;
    /**
     * @var string
     */
    private $defaultApplicationURL = 'http://localhost';
    /**
     * @var string
     */
    private $defaultTimeZone = 'UTC';
    /**
     * @var string
     */
    private $defaultLocale = 'en';
    /**
     * @var string
     */
    private $defaultFallbackLocale = 'en';
    /**
     * @var string
     */
    private $defaultFakerLocale = 'en_US';
    /**
     * @var string
     */
    private $defaultApplicationKey = 'base64:FvG8Ta/fAkfcJOJ4cxX5kLQEQnYaSpNqHIirxXLVqi0=';
    /**
     * @var string
     */
    private $defaultApplicationCipher = 'AES-256-CBC';
    /**
     * @var array
     */
    private $frameworkServiceProviders = [];
    /**
     * @var array
     */
    private $applicationServiceProviders = [];
    /**
     * @var array
     */
    private $packageServiceProviders = [];
    /**
     * @var array
     */
    private $classAliases = [];

    /**
     * @return AppBuilder
     */
    public function prepare(): AppBuilder
    {
        return $this->setDefaults();
    }

    /**
     * @return AppBuilder
     */
    private function setDefaults(): AppBuilder
    {
        $this->setDefaultApplicationKey(
            'base64:' . base64_encode(
                Encrypter::generateKey(
                    $this->getDefaultApplicationCipher()
                )
            )
        );

        $this->setIsInDebugMode(
            $this->strictNotEquals(
                $this->envFuncCall('APP_ENV'),
                $this->string('production')
            )
        );

        $this->setFrameworkServiceProviders(
            [
                $this->const('Illuminate\Auth\AuthServiceProvider::class', 'Laravel Framework Service Providers', ['doNotAppendNewline' => true]),
                $this->const('Illuminate\Broadcasting\BroadcastServiceProvider::class'),
                $this->const('Illuminate\Bus\BusServiceProvider::class'),
                $this->const('Illuminate\Cache\CacheServiceProvider::class'),
                $this->const('Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class'),
                $this->const('Illuminate\Cookie\CookieServiceProvider::class'),
                $this->const('Illuminate\Database\DatabaseServiceProvider::class'),
                $this->const('Illuminate\Encryption\EncryptionServiceProvider::class'),
                $this->const('Illuminate\Filesystem\FilesystemServiceProvider::class'),
                $this->const('Illuminate\Foundation\Providers\FoundationServiceProvider::class'),
                $this->const('Illuminate\Hashing\HashServiceProvider::class'),
                $this->const('Illuminate\Mail\MailServiceProvider::class'),
                $this->const('Illuminate\Notifications\NotificationServiceProvider::class'),
                $this->const('Illuminate\Pagination\PaginationServiceProvider::class'),
                $this->const('Illuminate\Pipeline\PipelineServiceProvider::class'),
                $this->const('Illuminate\Queue\QueueServiceProvider::class'),
                $this->const('Illuminate\Redis\RedisServiceProvider::class'),
                $this->const('Illuminate\Auth\Passwords\PasswordResetServiceProvider::class'),
                $this->const('Illuminate\Session\SessionServiceProvider::class'),
                $this->const('Illuminate\Translation\TranslationServiceProvider::class'),
                $this->const('Illuminate\Validation\ValidationServiceProvider::class'),
                $this->const('Illuminate\View\ViewServiceProvider::class'),
            ]
        );

        $this->addApplicationServiceProvider($this->const('App\Providers\AppServiceProvider::class', 'Application Service Providers', ['doNotAppendNewline' => true]));
        $this->addApplicationServiceProvider($this->const('App\Providers\AuthServiceProvider::class'));
        $this->addApplicationServiceProvider($this->const('App\Providers\EventServiceProvider::class'));
        $this->addApplicationServiceProvider($this->const('App\Providers\RouteServiceProvider::class'));

        $this->setClassAliases([
            $this->assoc('App', $this->const('Illuminate\Support\Facades\App::class')),
            $this->assoc('Arr', $this->const('Illuminate\Support\Arr::class')),
            $this->assoc('Artisan', $this->const('Illuminate\Support\Facades\Artisan::class')),
            $this->assoc('Auth', $this->const('Illuminate\Support\Facades\Auth::class')),
            $this->assoc('Blade', $this->const('Illuminate\Support\Facades\Blade::class')),
            $this->assoc('Broadcast', $this->const('Illuminate\Support\Facades\Broadcast::class')),
            $this->assoc('Bus', $this->const('Illuminate\Support\Facades\Bus::class')),
            $this->assoc('Cache', $this->const('Illuminate\Support\Facades\Cache::class')),
            $this->assoc('Config', $this->const('Illuminate\Support\Facades\Config::class')),
            $this->assoc('Cookie', $this->const('Illuminate\Support\Facades\Cookie::class')),
            $this->assoc('Crypt', $this->const('Illuminate\Support\Facades\Crypt::class')),
            $this->assoc('DB', $this->const('Illuminate\Support\Facades\DB::class')),
            $this->assoc('Eloquent', $this->const('Illuminate\Database\Eloquent\Model::class')),
            $this->assoc('Event', $this->const('Illuminate\Support\Facades\Event::class')),
            $this->assoc('File', $this->const('Illuminate\Support\Facades\File::class')),
            $this->assoc('Gate', $this->const('Illuminate\Support\Facades\Gate::class')),
            $this->assoc('Hash', $this->const('Illuminate\Support\Facades\Hash::class')),
            $this->assoc('Http', $this->const('Illuminate\Support\Facades\Http::class')),
            $this->assoc('Lang', $this->const('Illuminate\Support\Facades\Lang::class')),
            $this->assoc('Log', $this->const('Illuminate\Support\Facades\Log::class')),
            $this->assoc('Mail', $this->const('Illuminate\Support\Facades\Mail::class')),
            $this->assoc('Notification', $this->const('Illuminate\Support\Facades\Notification::class')),
            $this->assoc('Password', $this->const('Illuminate\Support\Facades\Password::class')),
            $this->assoc('Queue', $this->const('Illuminate\Support\Facades\Queue::class')),
            $this->assoc('Redirect', $this->const('Illuminate\Support\Facades\Redirect::class')),
            $this->assoc('Redis', $this->const('Illuminate\Support\Facades\Redis::class')),
            $this->assoc('Request', $this->const('Illuminate\Support\Facades\Request::class')),
            $this->assoc('Response', $this->const('Illuminate\Support\Facades\Response::class')),
            $this->assoc('Route', $this->const('Illuminate\Support\Facades\Route::class')),
            $this->assoc('Schema', $this->const('Illuminate\Support\Facades\Schema::class')),
            $this->assoc('Session', $this->const('Illuminate\Support\Facades\Session::class')),
            $this->assoc('Storage', $this->const('Illuminate\Support\Facades\Storage::class')),
            $this->assoc('Str', $this->const('Illuminate\Support\Str::class')),
            $this->assoc('URL', $this->const('Illuminate\Support\Facades\URL::class')),
            $this->assoc('Validator', $this->const('Illuminate\Support\Facades\Validator::class')),
            $this->assoc('View', $this->const('Illuminate\Support\Facades\View::class')),
        ]);

        return $this;
    }

    /**
     * @return bool
     */
    public function build(): bool
    {
        return $this
            ->buildConfigArray()
            ->toDisk();
    }

    /**
     * @return AppBuilder
     */
    private function buildConfigArray(): AppBuilder
    {
        $this->retArr([
            $this->getNameKey(),
            $this->getEnvKey(),
            $this->getDebugKey(),
            $this->getUrlKey(),
            $this->getAssetURLKey(),
            $this->getTimeZoneKey(),
            $this->getLocaleKey(),
            $this->getFallbackLocaleKey(),
            $this->getFakerLocaleKey(),
            $this->getKeyKey(),
            $this->getCipherKey(),
            $this->getProvidersKey(),
            $this->getAliasesKey()
        ]);

        return $this;
    }

    /**
     * @param ConstFetch $applicationServiceProvider
     * @return $this
     */
    public function addApplicationServiceProvider(ConstFetch $applicationServiceProvider): AppBuilder
    {
        $this->applicationServiceProviders[] = $applicationServiceProvider;
        return $this;
    }

    /**
     * @param $alias
     * @param $class
     * @return AppBuilder
     */
    public function addClassAlias($alias, $class): AppBuilder
    {
        $this->classAliases[] = $this->assoc($alias, $this->const($class));
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultApplicationCipher(): string
    {
        return $this->defaultApplicationCipher;
    }

    /**
     * @return mixed
     */
    public function getDefaultAppName(): string
    {
        return $this->defaultAppName;
    }

    /**
     * @return mixed
     */
    public function getDefaultAppEnvironment(): string
    {
        return $this->defaultAppEnvironment;
    }

    /**
     * @param $defaultAppEnvironment
     * @return AppBuilder
     */
    public function setDefaultAppEnvironment($defaultAppEnvironment): AppBuilder
    {
        $this->defaultAppEnvironment = $defaultAppEnvironment;
        return $this;
    }

    /**
     * @return bool
     *
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function isInDebugMode()
    {
        return $this->isInDebugMode;
    }

    /**
     * @param $isInDebugMode
     * @return AppBuilder
     */
    public function setIsInDebugMode($isInDebugMode): AppBuilder
    {
        $this->isInDebugMode = $isInDebugMode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultApplicationURL(): string
    {
        return $this->defaultApplicationURL;
    }

    /**
     * @return mixed
     */
    public function getDefaultTimeZone(): string
    {
        return $this->defaultTimeZone;
    }

    /**
     * @param $defaultTimeZone
     * @return AppBuilder
     */
    public function setDefaultTimeZone($defaultTimeZone): AppBuilder
    {
        $this->defaultTimeZone = $defaultTimeZone;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * @return mixed
     */
    public function getDefaultFallbackLocale(): string
    {
        return $this->defaultFallbackLocale;
    }

    /**
     * @return mixed
     */
    public function getDefaultFakerLocale(): string
    {
        return $this->defaultFakerLocale;
    }

    /**
     * @return mixed
     */
    public function getDefaultApplicationKey(): string
    {
        return $this->defaultApplicationKey;
    }

    /**
     * @param $defaultApplicationKey
     * @return AppBuilder
     */
    public function setDefaultApplicationKey($defaultApplicationKey): AppBuilder
    {
        $this->defaultApplicationKey = $defaultApplicationKey;
        return $this;
    }

    /**
     * @return array
     */
    public function getAutoLoadedServiceProviders(): array
    {
        return array_merge(array_merge($this->getFrameworkServiceProviders(), $this->getApplicationServiceProviders()), $this->getPackageServiceProviders());
    }

    /**
     * @return array
     */
    public function getFrameworkServiceProviders(): array
    {
        return $this->frameworkServiceProviders;
    }

    /**
     * @param array $frameworkServiceProviders
     * @return AppBuilder
     */
    public function setFrameworkServiceProviders(array $frameworkServiceProviders): AppBuilder
    {
        $this->frameworkServiceProviders = $frameworkServiceProviders;
        return $this;
    }

    /**
     * @return array
     */
    public function getApplicationServiceProviders(): array
    {
        return $this->applicationServiceProviders;
    }

    /**
     * @param array $applicationServiceProviders
     * @return AppBuilder
     */
    public function setApplicationServiceProviders(array $applicationServiceProviders): AppBuilder
    {
        $this->applicationServiceProviders = $applicationServiceProviders;
        return $this;
    }

    /**
     * @return array
     */
    public function getPackageServiceProviders(): array
    {
        return $this->packageServiceProviders;
    }

    /**
     * @param array $packageServiceProviders
     * @return AppBuilder
     */
    public function setPackageServiceProviders(array $packageServiceProviders): AppBuilder
    {
        $this->packageServiceProviders = $packageServiceProviders;
        return $this;
    }

    /**
     * @return array
     */
    public function getClassAliases(): array
    {
        return $this->classAliases;
    }

    /**
     * @param array $classAliases
     * @return AppBuilder
     */
    public function setClassAliases(array $classAliases): AppBuilder
    {
        $this->classAliases = $classAliases;
        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getNameKey(): ArrayItem
    {
        return $this->assoc('name', $this->funcCall('env', [
            $this->string('APP_NAME'),
            $this->string($this->getDefaultAppName()),
        ]), 'Application Name', 'This value is the name of your application. This value is used when the framework needs to place the application\'s name in a notification or any other location as required by the application or its packages.');
    }

    /**
     * @return ArrayItem
     */
    private function getEnvKey(): ArrayItem
    {
        return $this->assoc('env', $this->funcCall('env', [
            $this->string('APP_ENV'),
            $this->string($this->getDefaultAppEnvironment()),
        ]), 'Application Environment', 'This value determines the "environment" your application is currently running in. This may determine how you prefer to configure various services the application utilizes. Set this in your ".env" file.');
    }

    /**
     * @return ArrayItem
     */
    private function getDebugKey(): ArrayItem
    {
        return $this->assoc('debug', $this->isInDebugMode(), 'Application Debug Mode', 'When your application is in debug mode, detailed error messages with stack traces will be shown on every error that occurs within your application. If disabled, a simple generic error page is shown.');
    }

    /**
     * @return ArrayItem
     */
    private function getUrlKey(): ArrayItem
    {
        return $this->assoc('url', $this->funcCall('env', [
            $this->string('APP_URL'),
            $this->string($this->getDefaultApplicationURL()),
        ]), 'Application URL', 'This URL is used by the console to properly generate URLs when using the Artisan command line tool. You should set this to the root of your application so that it is used when running Artisan tasks.');
    }

    /**
     * @return ArrayItem
     */
    private function getTimeZoneKey(): ArrayItem
    {
        return $this->assoc('timezone', $this->string($this->getDefaultTimeZone()), 'Application Timezone', 'Here you may specify the default timezone for your application, which will be used by the PHP date and date-time functions. We have gone ahead and set this to a sensible default for you out of the box.');
    }

    /**
     * @return ArrayItem
     */
    private function getLocaleKey(): ArrayItem
    {
        return $this->assoc('locale', $this->string($this->getDefaultLocale()), 'Application Locale Configuration', 'The application locale determines the default locale that will be used by the translation service provider. You are free to set this value to any of the locales which will be supported by the application.');
    }

    /**
     * @return ArrayItem
     */
    private function getFallbackLocaleKey(): ArrayItem
    {
        return $this->assoc('fallback_locale', $this->string($this->getDefaultFallbackLocale()), 'Application Fallback Locale', 'The fallback locale determines the locale to use when the current one is not available. You may change the value to correspond to any of the language folders that are provided through your application.');
    }

    /**
     * @return ArrayItem
     */
    private function getFakerLocaleKey(): ArrayItem
    {
        return $this->assoc('faker_locale', $this->string($this->getDefaultFakerLocale()), 'Faker Locale', 'This locale will be used by the Faker PHP library when generating fake data for your database seeds. For example, this will be used to get localized telephone numbers, street address information and more.');
    }

    /**
     * @return ArrayItem
     */
    private function getKeyKey(): ArrayItem
    {
        return $this->assoc('key', $this->funcCall('env', [
            $this->string('APP_KEY'),
            $this->string($this->getDefaultApplicationKey()),
        ]), 'Encryption Key', 'This key is used by the Illuminate encryptor service and should be set to a random, 32 character string, otherwise these encrypted strings will not be safe. Please do this before deploying an application!');
    }

    /**
     * @return ArrayItem
     */
    private function getCipherKey(): ArrayItem
    {
        return $this->assoc('cipher', $this->string($this->getDefaultApplicationCipher()));
    }

    /**
     * @return ArrayItem
     */
    private function getProvidersKey(): ArrayItem
    {
        return $this->assoc('providers', $this->arr($this->getAutoLoadedServiceProviders()), 'Auto-loaded Service Providers', 'The service providers listed here will be automatically loaded on the request to your application. Feel free to add your own services to this array to grant expanded functionality to your applications.');
    }

    /**
     * @return ArrayItem
     */
    private function getAliasesKey(): ArrayItem
    {
        return $this->assoc('aliases', $this->arr($this->getClassAliases()), 'Class Aliases', 'This array of class aliases will be registered when this application is started. However, feel free to register as many as you wish as the aliases are "lazy" loaded so they don\'t hinder performance.');
    }

    /**
     * @return ArrayItem
     */
    private function getAssetURLKey(): ArrayItem
    {
        return $this->assoc('asset_url', $this->funcCall('env', [
            $this->string('ASSET_URL'),
            $this->nopExpr()
        ]));
    }
}
