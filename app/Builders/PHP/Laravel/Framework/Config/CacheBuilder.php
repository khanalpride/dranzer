<?php

/** @noinspection PhpMissingReturnTypeInspection */

namespace App\Builders\PHP\Laravel\Framework\Config;

use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;
use Illuminate\Support\Str;

/**
 * Class CacheBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class CacheBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'cache.php';
    /**
     * @var string
     */
    private $defaultCacheDriver = 'file';
    /**
     * @var string
     */
    private $apcDriver = 'apc';
    /**
     * @var string
     */
    private $arrayDriver = 'array';
    /**
     * @var string
     */
    private $databaseDriver = 'database';
    /**
     * @var string
     */
    private $databaseTable = 'cache';
    /**
     * @var string
     */
    private $databaseConnection = 'null'; // Will be converted to a const.
    /**
     * @var string
     */
    private $fileDriver = 'file';
    /**
     * @var
     */
    private $filePath;
    /**
     * @var string
     */
    private $memcachedDriver = 'memcached';
    /**
     * @var
     */
    private $memcachedPersistentId;
    /**
     * @var array
     */
    private $memcachedSASL = [];
    /**
     * @var array
     */
    private $memcachedOptions = [];
    /**
     * @var string
     */
    private $memcachedDefaultServerHost = '127.0.0.1';
    /**
     * @var int
     */
    private $memcachedDefaultServerPort = 11211;
    /**
     * @var int
     */
    private $memcachedDefaultServerWeight = 100;
    /**
     * @var string
     */
    private $redisDriver = 'redis';
    /**
     * @var string
     */
    private $dynamoDbDriver = 'dynamodb';
    /**
     * @var string
     */
    private $redisConnection = 'cache';

    /**
     * @return CacheBuilder
     */
    public function prepare(): CacheBuilder
    {
        return $this->setDefaults();
    }

    /**
     * @return CacheBuilder
     */
    private function setDefaults(): CacheBuilder
    {
        $this->setDefaultCacheDriver($this->string($this->getDefaultCacheDriver()));

        $this->setApcDriver($this->string($this->getApcDriver()));

        $this->setArrayDriver($this->string($this->getArrayDriver()));

        $this->setDatabaseDriver($this->string($this->getDatabaseDriver()));
        $this->setDatabaseTable($this->string($this->getDatabaseTable()));
        $this->setDatabaseConnection($this->const($this->getDatabaseConnection()));

        $this->setFileDriver($this->string($this->getFileDriver()));
        $this->setFilePath($this->funcCall('storage_path', [$this->string('framework/cache/data')]));

        $this->setMemcachedDriver($this->string($this->getMemcachedDriver()));
        $this->setMemcachedPersistentId($this->envFuncCall('MEMCACHED_PERSISTENT_ID'));

        $this->setMemcachedSASL(
            [
                $this->envFuncCall('MEMCACHED_USERNAME'),
                $this->envFuncCall('MEMCACHED_PASSWORD'),
            ]
        );

        if (count($this->getMemcachedOptions()) === 0) {
            $this->addMemcachedOption($this->comment('Memcached::OPT_CONNECT_TIMEOUT  => 2000'));
        }

        $this->setMemcachedDefaultServerHost($this->string($this->getMemcachedDefaultServerHost()));
        $this->setMemcachedDefaultServerPort($this->int($this->getMemcachedDefaultServerPort()));
        $this->setMemcachedDefaultServerWeight($this->int($this->getMemcachedDefaultServerWeight()));

        $this->setRedisDriver($this->string($this->getRedisDriver()));
        $this->setRedisConnection($this->string($this->getRedisConnection()));

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
     * @return CacheBuilder
     */
    private function buildConfigArray(): CacheBuilder
    {
        $this->use(Str::class);

        $this->stmt($this->nop());

        $this->retArr([
            $this->getDefaultKey(),
            $this->getStoresKey(),
            $this->getPrefixKey()
        ]);

        return $this;
    }

    /**
     * @param $memcachedOption
     * @return $this
     */
    public function addMemcachedOption($memcachedOption): CacheBuilder
    {
        $this->memcachedOptions[] = $memcachedOption;
        return $this;
    }

    /**
     * @param $option
     * @return $this
     */
    public function addMemcachedSASLOption($option): CacheBuilder
    {
        $this->memcachedSASL[] = $option;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getDefaultCacheDriver()
    {
        return $this->defaultCacheDriver;
    }

    /**
     * @param $defaultCacheDriver
     * @return CacheBuilder
     */
    public function setDefaultCacheDriver($defaultCacheDriver): CacheBuilder
    {
        $this->defaultCacheDriver = $defaultCacheDriver;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getApcDriver()
    {
        return $this->apcDriver;
    }

    /**
     * @param $apcDriver
     * @return CacheBuilder
     */
    public function setApcDriver($apcDriver): CacheBuilder
    {
        $this->apcDriver = $apcDriver;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getArrayDriver()
    {
        return $this->arrayDriver;
    }

    /**
     * @param $arrayDriver
     * @return CacheBuilder
     */
    public function setArrayDriver($arrayDriver): CacheBuilder
    {
        $this->arrayDriver = $arrayDriver;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getDatabaseDriver()
    {
        return $this->databaseDriver;
    }

    /**
     * @param $databaseDriver
     * @return CacheBuilder
     */
    public function setDatabaseDriver($databaseDriver): CacheBuilder
    {
        $this->databaseDriver = $databaseDriver;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getDatabaseConnection()
    {
        return $this->databaseConnection;
    }

    /**
     * @param $databaseConnection
     * @return CacheBuilder
     */
    public function setDatabaseConnection($databaseConnection): CacheBuilder
    {
        $this->databaseConnection = $databaseConnection;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getFileDriver()
    {
        return $this->fileDriver;
    }

    /**
     * @param $fileDriver
     * @return CacheBuilder
     */
    public function setFileDriver($fileDriver): CacheBuilder
    {
        $this->fileDriver = $fileDriver;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param $filePath
     * @return CacheBuilder
     */
    public function setFilePath($filePath): CacheBuilder
    {
        $this->filePath = $filePath;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getMemcachedDriver()
    {
        return $this->memcachedDriver;
    }

    /**
     * @param $memcachedDriver
     * @return CacheBuilder
     */
    public function setMemcachedDriver($memcachedDriver): CacheBuilder
    {
        $this->memcachedDriver = $memcachedDriver;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMemcachedPersistentId()
    {
        return $this->memcachedPersistentId;
    }

    /**
     * @param $memcachedPersistentId
     * @return CacheBuilder
     */
    public function setMemcachedPersistentId($memcachedPersistentId): CacheBuilder
    {
        $this->memcachedPersistentId = $memcachedPersistentId;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getMemcachedSASL()
    {
        return $this->memcachedSASL;
    }

    /**
     * @param $memcachedSASLOptions
     * @return CacheBuilder
     */
    public function setMemcachedSASL($memcachedSASLOptions): CacheBuilder
    {
        $this->memcachedSASL = $memcachedSASLOptions;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getMemcachedOptions()
    {
        return $this->memcachedOptions;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getMemcachedDefaultServerHost()
    {
        return $this->memcachedDefaultServerHost;
    }

    /**
     * @param $memcachedDefaultServerHost
     * @return CacheBuilder
     */
    public function setMemcachedDefaultServerHost($memcachedDefaultServerHost): CacheBuilder
    {
        $this->memcachedDefaultServerHost = $memcachedDefaultServerHost;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getMemcachedDefaultServerPort()
    {
        return $this->memcachedDefaultServerPort;
    }

    /**
     * @param $memcachedDefaultServerPort
     * @return CacheBuilder
     */
    public function setMemcachedDefaultServerPort($memcachedDefaultServerPort): CacheBuilder
    {
        $this->memcachedDefaultServerPort = $memcachedDefaultServerPort;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getMemcachedDefaultServerWeight()
    {
        return $this->memcachedDefaultServerWeight;
    }

    /**
     * @param $memcachedDefaultServerWeight
     * @return CacheBuilder
     */
    public function setMemcachedDefaultServerWeight($memcachedDefaultServerWeight): CacheBuilder
    {
        $this->memcachedDefaultServerWeight = $memcachedDefaultServerWeight;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getRedisDriver()
    {
        return $this->redisDriver;
    }

    /**
     * @param $redisDriver
     * @return CacheBuilder
     */
    public function setRedisDriver($redisDriver): CacheBuilder
    {
        $this->redisDriver = $redisDriver;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getRedisConnection()
    {
        return $this->redisConnection;
    }

    /**
     * @param $redisConnection
     * @return CacheBuilder
     */
    public function setRedisConnection($redisConnection): CacheBuilder
    {
        $this->redisConnection = $redisConnection;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getDatabaseTable()
    {
        return $this->databaseTable;
    }

    /**
     * @param $databaseTable
     * @return CacheBuilder
     */
    public function setDatabaseTable($databaseTable): CacheBuilder
    {
        $this->databaseTable = $databaseTable;
        return $this;
    }

    /**
     * @return string
     */
    public function getDynamoDbDriver(): string
    {
        return $this->dynamoDbDriver;
    }

    /**
     * @return ArrayItem
     */
    private function getDefaultKey(): ArrayItem
    {
        return $this->assoc(
            'default',
            $this->envFuncCall('CACHE_DRIVER', [$this->getDefaultCacheDriver()]),
            'Default Cache Store',
            'This option controls the default cache connection that gets used while using this caching library. This connection is used when another is not explicitly specified when executing a given caching function. Supported: "apc", "array", "database", "file", "memcached", "redis"'
        );
    }

    /**
     * @return ArrayItem
     */
    private function getStoresKey(): ArrayItem
    {
        return $this->assoc('stores', $this->arr([
            $this->assoc('apc', $this->arr([
                $this->assoc('driver', $this->getApcDriver())
            ])),
            $this->assoc('array', $this->arr([
                $this->assoc('driver', $this->getArrayDriver()),
                $this->assoc('serialize', $this->const('false')),
            ])),
            $this->assoc('database', $this->arr([
                $this->assoc('driver', $this->getDatabaseDriver()),
                $this->assoc('table', $this->getDefaultCacheDriver()),
                $this->assoc('connection', $this->getDatabaseConnection()),
            ])),
            $this->assoc('file', $this->arr([
                $this->assoc('driver', $this->getFileDriver()),
                $this->assoc('path', $this->getFilePath()),
            ])),
            $this->assoc('memcached', $this->arr([
                $this->assoc('driver', $this->getMemcachedDriver()),
                $this->assoc('persistent_id', $this->getMemcachedPersistentId()),
                $this->assoc('sasl', $this->arr($this->getMemcachedSASL())),
                $this->assoc('options', $this->arr($this->getMemcachedOptions())),
                $this->assoc('servers', $this->arr([
                    $this->assoc('host', $this->envFuncCall('MEMCACHED_HOST', [$this->getMemcachedDefaultServerHost()])),
                    $this->assoc('port', $this->envFuncCall('MEMCACHED_PORT', [$this->getMemcachedDefaultServerPort()])),
                    $this->assoc('weight', $this->getMemcachedDefaultServerWeight())
                ]))
            ])),

            $this->assoc('redis', $this->arr([
                $this->assoc('driver', $this->getRedisDriver()),
                $this->assoc('connection', $this->getRedisConnection()),
            ])),

            $this->assoc('dynamodb', $this->arr([
                $this->assoc('driver', $this->string($this->getDynamoDbDriver())),
                $this->assoc('key', $this->envFuncCall('AWS_ACCESS_KEY_ID')),
                $this->assoc('secret', $this->envFuncCall('AWS_SECRET_ACCESS_KEY')),
                $this->assoc('region', $this->envFuncCall('AWS_DEFAULT_REGION', [$this->string('us-east-1')])),
                $this->assoc('table', $this->envFuncCall('DYNAMODB_CACHE_TABLE', [$this->string('cache')])),
                $this->assoc('endpoint', $this->envFuncCall('DYNAMODB_ENDPOINT')),
            ])),

        ]), 'Cache Stores', 'Here you may define all of the cache "stores" for your application as well as their drivers. You may even define multiple stores for the same cache driver to group types of items stored in your caches.');
    }

    /**
     * @return ArrayItem
     */
    private function getPrefixKey(): ArrayItem
    {
        return $this->assoc('prefix', $this->arr([
            $this->envFuncCall('CACHE_PREFIX', [
                $this->concat(
                    $this->staticCall('Str', 'slug', [
                        $this->envFuncCall('APP_NAME', [$this->string('laravel')]),
                        $this->string('_')
                    ]),
                    $this->string('_cache')
                )
            ])
        ]), 'Cache Key Prefix', 'When utilizing a RAM based store such as APC or Memcached, there might be other applications utilizing the same cache. So, we\'ll specify a value to get prefixed to all our keys so we can avoid collisions.');
    }

}
