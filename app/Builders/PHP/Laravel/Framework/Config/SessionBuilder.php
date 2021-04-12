<?php

namespace App\Builders\PHP\Laravel\Framework\Config;

use Illuminate\Support\Str;
use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;

/**
 * Class SessionBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class SessionBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'session.php';

    /**
     * @return SessionBuilder
     */
    public function prepare(): SessionBuilder
    {
        return $this->buildUseStatements();
    }

    /**
     * @return SessionBuilder
     */
    private function buildUseStatements(): SessionBuilder
    {
        return $this->use(Str::class);
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
     * @return SessionBuilder
     */
    private function buildConfigArray(): SessionBuilder
    {
        $this->retArr([
            $this->getDriverKey(),
            $this->getLifeTimeKey(),
            $this->getExpireOnCloseKey(),
            $this->getEncryptKey(),
            $this->getFilesKey(),
            $this->getConnectionKey(),
            $this->getTableKey(),
            $this->getStoreKey(),
            $this->getLotteryKey(),
            $this->getCookieKey(),
            $this->getPathKey(),
            $this->getDomainKey(),
            $this->getSecureKey(),
            $this->getHttpOnlyKey(),
            $this->getSameSiteKey(),
        ]);
        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getDriverKey(): ArrayItem
    {
        return $this->assoc('driver', $this->envFuncCall('SESSION_DRIVER', [$this->string('file')]), 'Default Session Driver', 'This option controls the default session "driver" that will be used on requests. By default, we will use the lightweight native driver but you may specify any of the other wonderful drivers provided here. Supported: "file", "cookie", "database", "apc", "memcached", "redis", "array"');
    }

    /**
     * @return ArrayItem
     */
    private function getLifeTimeKey(): ArrayItem
    {
        return $this->assoc('lifetime', $this->envFuncCall('SESSION_LIFETIME', [$this->int(120)]), 'Session Lifetime', 'Here you may specify the number of minutes that you wish the session to be allowed to remain idle before it expires. If you want them to immediately expire on the browser closing, set that option.');
    }

    /**
     * @return ArrayItem
     */
    private function getExpireOnCloseKey(): ArrayItem
    {
        return $this->assoc('expire_on_close', $this->const('false'));
    }

    /**
     * @return ArrayItem
     */
    private function getEncryptKey(): ArrayItem
    {
        return $this->assoc('encrypt', $this->const('false'), 'Session Encryption', 'This option allows you to easily specify that all of your session data should be encrypted before it is stored. All encryption will be run automatically by Laravel and you can use the Session like normal.');
    }

    /**
     * @return ArrayItem
     */
    private function getFilesKey(): ArrayItem
    {
        return $this->assoc('files', $this->funcCall('storage_path', [
            $this->string('framework/sessions')
        ]), 'Session File Location', 'When using the native session driver, we need a location where session files may be stored. A default has been set for you but a different location may be specified. This is only needed for file sessions.');
    }

    /**
     * @return ArrayItem
     */
    private function getConnectionKey(): ArrayItem
    {
        return $this->assoc('connection', $this->envFuncCall('SESSION_CONNECTION'), 'Session Database Connection', 'When using the "database" or "redis" session drivers, you may specify a connection that should be used to manage these sessions. This should correspond to a connection in your database configuration options.');
    }

    /**
     * @return ArrayItem
     */
    private function getTableKey(): ArrayItem
    {
        return $this->assoc('table', 'sessions', 'Session Database Table', 'When using the "database" session driver, you may specify the table we should use to manage the sessions. Of course, a sensible default is provided for you; however, you are free to change this as needed.');
    }

    /**
     * @return ArrayItem
     */
    private function getStoreKey(): ArrayItem
    {
        return $this->assoc('store', $this->envFuncCall('SESSION_STORE'), 'Session Cache Store', 'When using the "apc" or "memcached" session drivers, you may specify a cache store that should be used for these sessions. This value must correspond with one of the application\'s configured cache stores.');
    }

    /**
     * @return ArrayItem
     */
    private function getLotteryKey(): ArrayItem
    {
        return $this->assoc('lottery', $this->arr([
            $this->int(2),
            $this->int(100),
        ]), 'Session Sweeping Lottery', 'Some session drivers must manually sweep their storage location to get rid of old sessions from storage. Here are the chances that it will happen on a given request. By default, the odds are 2 out of 100.');
    }

    /**
     * @return ArrayItem
     */
    private function getCookieKey(): ArrayItem
    {
        return $this->assoc('cookie', $this->envFuncCall('SESSION_COOKIE', [
            $this->concat(
                $this->staticCall('Str', 'slug', [
                    $this->envFuncCall('APP_NAME', [$this->string('laravel')]),
                    $this->string('_')
                ]),
                $this->string('_session')
            )
        ]), 'Session Cookie Name', 'Here you may change the name of the cookie used to identify a session instance by ID. The name specified here will get used every time a new session cookie is created by the framework for every driver.');
    }

    /**
     * @return ArrayItem
     */
    private function getPathKey(): ArrayItem
    {
        return $this->assoc('path', '/', 'Session Cookie Path', 'The session cookie path determines the path for which the cookie will be regarded as available. Typically, this will be the root path of your application but you are free to change this when necessary.');
    }

    /**
     * @return ArrayItem
     */
    private function getDomainKey(): ArrayItem
    {
        return $this->assoc('domain', $this->envFuncCall('SESSION_DOMAIN'), 'Session Cookie Domain', 'Here you may change the domain of the cookie used to identify a session in your application. This will determine which domains the cookie is available to in your application. A sensible default has been set.');
    }

    /**
     * @return ArrayItem
     */
    private function getSecureKey(): ArrayItem
    {
        return $this->assoc('secure', $this->envFuncCall('SESSION_SECURE_COOKIE', [$this->const('false')]), 'HTTPS Only Cookies', 'By setting this option to true, session cookies will only be sent back to the server if the browser has a HTTPS connection. This will keep the cookie from being sent to you if it can not be done securely.');
    }

    /**
     * @return ArrayItem
     */
    private function getHttpOnlyKey(): ArrayItem
    {
        return $this->assoc('http_only', $this->const('true'), 'HTTP Access Only', 'Setting this value to true will prevent JavaScript from accessing the value of the cookie and the cookie will only be accessible through the HTTP protocol. You are free to modify this option if needed.');
    }

    /**
     * @return ArrayItem
     */
    private function getSameSiteKey(): ArrayItem
    {
        return $this->assoc('same_site', $this->string('lax'), 'Same-Site Cookies', 'This option determines how your cookies behave when cross-site requests take place, and can be used to mitigate CSRF attacks. By default, we do not enable this as other CSRF protection services are in place. Supported: "lax", "strict", "none", null');
    }

}
