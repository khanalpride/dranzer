<?php

namespace App\Builders\PHP\Laravel\Framework\Config;

use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;

/**
 * Class BroadcastingBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class BroadcastingBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'broadcasting.php';
    /**
     * @var string
     */
    private $defaultBroadcastingDriver = 'null';
    /**
     * @var string
     */
    private $pusherDriver = 'pusher';
    /**
     * @var null
     */
    private $pusherKey;
    /**
     * @var null
     */
    private $pusherSecret;
    /**
     * @var null
     */
    private $pusherAppId;
    /**
     * @var null
     */
    private $pusherCluster;
    /**
     * @var null
     */
    private $pusherEncrypted;

    /**
     * @var string
     */
    private $redisDriver = 'redis';
    /**
     * @var string
     */
    private $defaultRedisConnection = 'default';

    /**
     * @var string
     */
    private $defaultLogDriver = 'log';
    /**
     * @var string
     */
    private $defaultNullDriver = 'null';

    /**
     * @return BroadcastingBuilder
     */
    public function prepare(): BroadcastingBuilder
    {
        return $this->setDefaults();
    }

    /**
     * @return BroadcastingBuilder
     */
    private function setDefaults(): BroadcastingBuilder
    {
        $this->setDefaultBroadcastingDriver($this->string($this->getDefaultBroadcastingDriver()));

        $this->setPusherDriver($this->string($this->getPusherDriver()));
        $this->setPusherKey($this->envFuncCall('PUSHER_APP_KEY'));
        $this->setPusherSecret($this->envFuncCall('PUSHER_APP_SECRET'));
        $this->setPusherAppId($this->envFuncCall('PUSHER_APP_ID'));
        $this->setPusherCluster($this->envFuncCall('PUSHER_APP_CLUSTER'));
        $this->setPusherEncrypted($this->const(true));

        $this->setRedisDriver($this->string($this->getRedisDriver()));
        $this->setDefaultRedisConnection($this->string($this->getDefaultRedisConnection()));

        $this->setDefaultLogDriver($this->string($this->getDefaultLogDriver()));
        $this->setDefaultNullDriver($this->string($this->getDefaultNullDriver()));

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
     * @return BroadcastingBuilder
     */
    private function buildConfigArray(): BroadcastingBuilder
    {
        $this->retArr([
            $this->getDefaultKey(),
            $this->getConnectionsKey(),
        ]);
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getDefaultBroadcastingDriver()
    {
        return $this->defaultBroadcastingDriver;
    }

    /**
     * @param $defaultBroadcastingDriver
     * @return BroadcastingBuilder
     */
    public function setDefaultBroadcastingDriver($defaultBroadcastingDriver): BroadcastingBuilder
    {
        $this->defaultBroadcastingDriver = $defaultBroadcastingDriver;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getPusherDriver()
    {
        return $this->pusherDriver;
    }

    /**
     * @param $pusherDriver
     * @return BroadcastingBuilder
     */
    public function setPusherDriver($pusherDriver): BroadcastingBuilder
    {
        $this->pusherDriver = $pusherDriver;
        return $this;
    }

    /**
     * @return null
     */
    public function getPusherKey()
    {
        return $this->pusherKey;
    }

    /**
     * @param $pusherKey
     * @return BroadcastingBuilder
     */
    public function setPusherKey($pusherKey): BroadcastingBuilder
    {
        $this->pusherKey = $pusherKey;
        return $this;
    }

    /**
     * @return null
     */
    public function getPusherSecret()
    {
        return $this->pusherSecret;
    }

    /**
     * @param $pusherSecret
     * @return BroadcastingBuilder
     */
    public function setPusherSecret($pusherSecret): BroadcastingBuilder
    {
        $this->pusherSecret = $pusherSecret;
        return $this;
    }

    /**
     * @return null
     */
    public function getPusherAppId()
    {
        return $this->pusherAppId;
    }

    /**
     * @param $pusherAppId
     * @return BroadcastingBuilder
     */
    public function setPusherAppId($pusherAppId): BroadcastingBuilder
    {
        $this->pusherAppId = $pusherAppId;
        return $this;
    }

    /**
     * @return null
     */
    public function getPusherCluster()
    {
        return $this->pusherCluster;
    }

    /**
     * @param $pusherCluster
     * @return BroadcastingBuilder
     */
    public function setPusherCluster($pusherCluster): BroadcastingBuilder
    {
        $this->pusherCluster = $pusherCluster;
        return $this;
    }

    /**
     * @return null
     */
    public function getPusherEncrypted()
    {
        return $this->pusherEncrypted;
    }

    /**
     * @param $pusherEncrypted
     * @return BroadcastingBuilder
     */
    public function setPusherEncrypted($pusherEncrypted): BroadcastingBuilder
    {
        $this->pusherEncrypted = $pusherEncrypted;
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
     * @return BroadcastingBuilder
     */
    public function setRedisDriver($redisDriver): BroadcastingBuilder
    {
        $this->redisDriver = $redisDriver;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getDefaultRedisConnection()
    {
        return $this->defaultRedisConnection;
    }

    /**
     * @param $defaultRedisConnection
     * @return BroadcastingBuilder
     */
    public function setDefaultRedisConnection($defaultRedisConnection): BroadcastingBuilder
    {
        $this->defaultRedisConnection = $defaultRedisConnection;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getDefaultLogDriver()
    {
        return $this->defaultLogDriver;
    }

    /**
     * @param $defaultLogDriver
     * @return BroadcastingBuilder
     */
    public function setDefaultLogDriver($defaultLogDriver): BroadcastingBuilder
    {
        $this->defaultLogDriver = $defaultLogDriver;
        return $this;
    }

    /**
     * @return mixed
     *
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function getDefaultNullDriver()
    {
        return $this->defaultNullDriver;
    }

    /**
     * @param $defaultNullDriver
     * @return BroadcastingBuilder
     */
    public function setDefaultNullDriver($defaultNullDriver): BroadcastingBuilder
    {
        $this->defaultNullDriver = $defaultNullDriver;
        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getDefaultKey(): ArrayItem
    {
        return $this->assoc('default',
            $this->envFuncCall('BROADCAST_DRIVER', [$this->getDefaultBroadcastingDriver()]),
            'Default Broadcaster',
            'This option controls the default broadcaster that will be used by the framework when an event needs to be broadcast. You may set this to any of the connections defined in the "connections" array below. Supported: "pusher", "redis", "log", "null"'
        );
    }

    /**
     * @return ArrayItem
     */
    private function getConnectionsKey(): ArrayItem
    {
        return $this->assoc('connection', $this->arr([
            $this->assoc('pusher', $this->arr([
                $this->assoc('driver', $this->getPusherDriver()),
                $this->assoc('key', $this->getPusherKey()),
                $this->assoc('secret', $this->getPusherSecret()),
                $this->assoc('app_id', $this->getPusherAppId()),
                $this->assoc('options', $this->arr([
                    $this->assoc('cluster', $this->getPusherCluster()),
                    $this->assoc('encrypted', $this->getPusherEncrypted())
                ]))
            ])),

            $this->assoc('redis', $this->arr([
                $this->assoc('driver', $this->getRedisDriver()),
                $this->assoc('connection', $this->getDefaultRedisConnection()),
            ])),

            $this->assoc('log', $this->arr([
                $this->assoc('driver', $this->getDefaultLogDriver())
            ])),

            $this->assoc('null', $this->arr([
                $this->assoc('driver', $this->getDefaultNullDriver())
            ]))

        ]), 'Broadcast Connections', 'Here you may define all of the broadcast connections that will be used to broadcast events to other systems or over websockets. Samples of each available type of connection are provided inside this array.');
    }

}
