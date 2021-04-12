<?php

namespace App\Builders\PHP\Laravel\Framework\Config;

use App\Models\User;
use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;
use App\Builders\Processors\Config\AuthProcessor;

/**
 * Class AuthBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class AuthBuilder extends FileBuilder
{
    protected array $processors = [
        AuthProcessor::class,
    ];

    /**
     * @var string
     */
    protected string $filename = 'auth.php';
    /**
     * @var string
     */
    private $defaultGuard = 'web';
    /**
     * @var string
     */
    private $apiDriver = 'token';

    public function prepare(): AuthBuilder
    {
        return $this->buildUseStatements();
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
     * @return AuthBuilder
     */
    private function buildUseStatements(): AuthBuilder
    {
        $this->use(User::class);

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultGuard(): string
    {
        return $this->defaultGuard;
    }

    /**
     * @param string $defaultGuard
     * @return AuthBuilder
     */
    public function setDefaultGuard(string $defaultGuard): AuthBuilder
    {
        $this->defaultGuard = $defaultGuard;
        return $this;
    }

    /**
     * @param string $apiDriver
     * @return AuthBuilder
     */
    public function setApiDriver(string $apiDriver): AuthBuilder
    {
        $this->apiDriver = $apiDriver;
        return $this;
    }

    /**
     * @return AuthBuilder
     */
    private function buildConfigArray(): AuthBuilder
    {
        $this->retArr([
            $this->getDefaultsKey(),
            $this->getGuardsKey(),
            $this->getProvidersKey(),
            $this->getPasswordsKey(),
            $this->getPasswordTimeoutKey()
        ]);

        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getDefaultsKey(): ArrayItem
    {
        return $this->assoc('defaults', $this->arr([
            $this->assoc('guard', $this->getDefaultGuard()),
            $this->assoc('passwords', 'users'),
        ]), 'Authentication Defaults', 'This option controls the default authentication "guard" and password reset options for your application. You may change these defaults as required, but they\'re a perfect start for most applications.');
    }

    /**
     * @return ArrayItem
     */
    private function getGuardsKey(): ArrayItem
    {
        return $this->assoc('guards', $this->arr([
            $this->assoc('web', $this->arr([
                $this->assoc('driver', 'session'),
                $this->assoc('provider', 'users'),
            ])),
            $this->assoc('api', $this->arr([
                $this->assoc('driver', $this->apiDriver),
                $this->assoc('provider', 'users'),
                $this->assoc('hash', $this->const('false')),
            ])),
        ]), 'Authentication Guards', 'Next, you may define every authentication guard for your application. Of course, a great default configuration has been defined for you here which uses session storage and the Eloquent user provider. All authentication drivers have a user provider. This defines how the users are actually retrieved out of your database or other storage mechanisms used by this application to persist your user\'s data. Supported: "session", "token"');
    }

    /**
     * @return ArrayItem
     */
    private function getProvidersKey(): ArrayItem
    {
        return $this->assoc('providers', $this->arr([
            $this->assoc('users', $this->arr([
                $this->assoc('driver', 'eloquent'),
                $this->assoc('model', $this->const('User::class')),
            ]))
        ]), 'User Providers', 'All authentication drivers have a user provider. This defines how the users are actually retrieved out of your database or other storage mechanisms used by this application to persist your user\'s data. If you have multiple user tables or models you may configure multiple sources which represent each model / table. These sources may then be assigned to any extra authentication guards you have defined. Supported: "database", "eloquent"');
    }

    /**
     * @return ArrayItem
     */
    private function getPasswordsKey(): ArrayItem
    {
        return $this->assoc('passwords', $this->arr([
            $this->assoc('users', $this->arr([
                $this->assoc('provider', 'users'),
                $this->assoc('table', 'password_resets'),
                $this->assoc('expire', $this->int(60)),
                $this->assoc('throttle', $this->int(60)),
            ]))
        ]), 'Resetting Passwords', 'You may specify multiple password reset configurations if you have more than one user table or model in the application and you want to have separate password reset settings based on the specific user types. The expire time is the number of minutes that the reset token should be considered valid. This security feature keeps tokens short-lived so they have less time to be guessed. You may change this as needed.');
    }

    /**
     * @return ArrayItem
     */
    private function getPasswordTimeoutKey(): ArrayItem
    {
        return $this->assoc('password_timeout', $this->int(10800), 'Password Confirmation Timeout', 'Here you may define the amount of seconds before a password confirmation times out and the user is prompted to re-enter their password via the confirmation screen. By default, the timeout lasts for three hours..');
    }

}
