<?php

namespace App\Builders\PHP\Laravel\Framework\Config;

use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;
use App\Providers\RouteServiceProvider;
use App\Builders\Processors\Config\FortifyProcessor;

/**
 * Class FortifyBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class FortifyBuilder extends FileBuilder
{
    /**
     * @var array|string[]
     */
    protected array $processors = [
        FortifyProcessor::class,
    ];
    /**
     * @var string
     */
    protected string $filename = 'fortify.php';
    /**
     * @var array
     */
    private $features = [];
    /**
     * @var bool
     */
    private $viewRoutes = false;

    /**
     * @return FortifyBuilder
     */
    public function prepare(): FortifyBuilder
    {
        return $this->buildUseStatements();
    }

    /**
     * @return FortifyBuilder
     */
    private function buildUseStatements(): FortifyBuilder
    {
        $this->use(RouteServiceProvider::class);
        $this->use('Laravel\Fortify\Features');

        return $this;
    }

    /**
     *
     */
    public function build(): bool
    {
        return $this
            ->buildConfigArray()
            ->toDisk();
    }

    /**
     * @return FortifyBuilder
     */
    private function buildConfigArray(): FortifyBuilder
    {
        $this->stmt($this->nop());

        $this->retArr(([
            $this->getGuardsKey(),
            $this->getPasswordsKey(),
            $this->getUsernameKey(),
            $this->getEmailKey(),
            $this->getHomeKey(),
            $this->getPrefixKey(),
            $this->getDomainKey(),
            $this->getMiddlewareKey(),
            $this->getLimitersKey(),
            $this->getViewsKey(),
            $this->getFeaturesKey(),
        ]));

        return $this;
    }

    /**
     * @param $feature
     * @return $this
     */
    public function addFeature($feature): FortifyBuilder
    {
        $this->features[] = $feature;

        return $this;
    }

    /**
     * @return array
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * @param array $features
     * @return FortifyBuilder
     */
    public function setFeatures(array $features): FortifyBuilder
    {
        $this->features = $features;

        return $this;
    }

    /**
     * @return bool
     */
    public function canViewRoutes(): bool
    {
        return $this->viewRoutes;
    }

    /**
     * @param bool $viewRoutes
     * @return FortifyBuilder
     */
    public function setViewRoutes(bool $viewRoutes): FortifyBuilder
    {
        $this->viewRoutes = $viewRoutes;

        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getGuardsKey(): ArrayItem
    {
        return $this->assoc('guard', $this->string('web'), 'Fortify Guard', 'Here you may specify which authentication guard Fortify will use while authenticating users. This value should correspond with one of your guards that is already present in your "auth" configuration file.');
    }

    /**
     * @return ArrayItem
     */
    private function getPasswordsKey(): ArrayItem
    {
        return $this->assoc('passwords', $this->string('users'), 'Fortify Password Broker', 'Here you may specify which password broker Fortify can use when a user is resetting their password. This configured value should match one of your password brokers setup in your "auth" configuration file.');
    }

    /**
     * @return ArrayItem
     */
    private function getUsernameKey(): ArrayItem
    {
        return $this->assoc('username', $this->string('email'), 'Username / Email', "This value defines which model attribute should be considered as your application's 'username' field. Typically, this might be the email address of the users but you are free to change this value here. Out of the box, Fortify expects forgot password and reset password requests to have a field named 'email'. If the application uses another name for the field you may define it below as needed.");
    }

    /**
     * @return ArrayItem
     */
    private function getEmailKey(): ArrayItem
    {
        return $this->assoc('email', $this->string('email'));
    }

    /**
     * @return ArrayItem
     */
    private function getHomeKey(): ArrayItem
    {
        return $this->assoc('home', $this->const('RouteServiceProvider::HOME'), 'Home Path', 'Here you may configure the path where users will get redirected during password or authentication reset when the operations are successful. You are free to change this value.');
    }

    /**
     * @return ArrayItem
     */
    private function getPrefixKey(): ArrayItem
    {
        return $this->assoc('prefix', $this->string(''), 'Fortify Routes Prefix / Subdomain', 'Here you may specify which prefix Fortify will assign to all the routes that it registers with the application. If necessary, you may change subdomain under which all of the Fortify routes will be available.');
    }

    /**
     * @return ArrayItem
     */
    private function getDomainKey(): ArrayItem
    {
        return $this->assoc('domain', $this->const('null'));
    }

    /**
     * @return ArrayItem
     */
    private function getMiddlewareKey(): ArrayItem
    {
        return $this->assoc('middleware', $this->arr([$this->string('web')]), 'Fortify Routes Prefix / Subdomain', 'Here you may specify which middleware Fortify will assign to the routes that it registers with the application. If necessary, you may change these middleware but typically this provided default is preferred.');
    }

    /**
     * @return ArrayItem
     */
    private function getLimitersKey(): ArrayItem
    {
        return $this->assoc('limiters', $this->arr([
            $this->assoc('login', $this->const('null'))
        ]), 'Rate Limiting', 'By default, Fortify will throttle logins to five requests per minute for every email and IP address combination. However, if you would like to specify a custom rate limiter to call then you may specify it here.');
    }

    /**
     * @return ArrayItem
     */
    private function getViewsKey(): ArrayItem
    {
        return $this->assoc('views', $this->const($this->viewRoutes), 'Register View Routes', 'Here you may specify if the routes returning views should be disabled as you may not need them when building your own application. This may be especially true if you\'re writing a custom single-page application.');
    }

    /**
     * @return ArrayItem
     */
    private function getFeaturesKey(): ArrayItem
    {
        return $this->assoc('features', $this->arr($this->features), 'Features', "Here you may specify if the routes returning views should be disabled as you may not need them when building your own application. This may be especially true if you\'re writing a custom single-page application.");
    }
}
