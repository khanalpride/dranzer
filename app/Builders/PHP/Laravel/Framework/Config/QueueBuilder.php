<?php

/** @noinspection SpellCheckingInspection */

namespace App\Builders\PHP\Laravel\Framework\Config;

use App\Builders\PHP\FileBuilder;
use PhpParser\Node\Expr\ArrayItem;

/**
 * Class QueueBuilder
 * @package App\Builders\PHP\Laravel\Framework\Config
 */
class QueueBuilder extends FileBuilder
{
    /**
     * @var string
     */
    protected string $filename = 'queue.php';

    /**
     * @return QueueBuilder
     */
    public function prepare(): QueueBuilder
    {
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
     * @return QueueBuilder
     */
    private function buildConfigArray(): QueueBuilder
    {
        $this->retArr([
            $this->getDefaultKey(),
            $this->getConnectionsKey(),
            $this->getFailedKey(),
        ]);

        return $this;
    }

    /**
     * @return ArrayItem
     */
    private function getDefaultKey(): ArrayItem
    {
        return $this->assoc('default', $this->envFuncCall('QUEUE_CONNECTION', [$this->string('sync')]), 'Default Queue Connection Name', 'Laravel\'s queue API supports an assortment of back-ends via a single API, giving you convenient access to each back-end using the same syntax for every one. Here you may define a default connection.');
    }

    /**
     * @return ArrayItem
     */
    private function getConnectionsKey(): ArrayItem
    {
        return $this->assoc('connections', $this->arr([
            $this->getSyncConfigKey(),
            $this->getDatabaseConfigKey(),
            $this->getBeanStalkdConfigKey(),
            $this->getSQSConfigKey(),
            $this->getRedisConfigKey(),
        ]), 'Queue Connections', 'Here you may configure the connection information for each server that is used by your application. A default configuration has been added for each back-end shipped with Laravel. You are free to add more. Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"');
    }

    /**
     * @return ArrayItem
     */
    private function getSyncConfigKey(): ArrayItem
    {
        return $this->assoc('sync', $this->arr([
            $this->assoc('driver', 'sync')
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getDatabaseConfigKey(): ArrayItem
    {
        return $this->assoc('database', $this->arr([
            $this->assoc('driver', 'database'),
            $this->assoc('table', 'jobs'),
            $this->assoc('queue', 'default'),
            $this->assoc('retry_after', $this->int(90)),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getBeanStalkdConfigKey(): ArrayItem
    {
        return $this->assoc('beanstalkd', $this->arr([
            $this->assoc('driver', 'beanstalkd'),
            $this->assoc('host', 'localhost'),
            $this->assoc('queue', 'default'),
            $this->assoc('retry_after', $this->int(90)),
            $this->assoc('block_for', $this->int(0)),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getSQSConfigKey(): ArrayItem
    {
        return $this->assoc('sqs', $this->arr([
            $this->assoc('driver', 'sqs'),
            $this->assoc('key', $this->envFuncCall('SQS_KEY', [$this->string('your-public-key')])),
            $this->assoc('secret', $this->envFuncCall('SQS_SECRET', [$this->string('your-secret-key')])),
            $this->assoc('prefix', $this->envFuncCall('SQS_PREFIX', [$this->string('https://sqs.us-east-1.amazonaws.com/your-account-id')])),
            $this->assoc('queue', $this->envFuncCall('SQS_QUEUE', [$this->string('your-queue-name')])),
            $this->assoc('suffix', $this->envFuncCall('SQS_SUFFIX')),
            $this->assoc('region', $this->envFuncCall('SQS_REGION', [$this->string('us-east-1')])),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getRedisConfigKey(): ArrayItem
    {
        return $this->assoc('redis', $this->arr([
            $this->assoc('driver', 'redis'),
            $this->assoc('connection', 'default'),
            $this->assoc('queue', $this->envFuncCall('REDIS_QUEUE', [$this->string('default')])),
            $this->assoc('retry_after', $this->int(90)),
            $this->assoc('block_for', $this->const('null')),
        ]));
    }

    /**
     * @return ArrayItem
     */
    private function getFailedKey(): ArrayItem
    {
        return $this->assoc('failed', $this->arr([
            $this->assoc('driver', $this->envFuncCall('QUEUE_FAILED_DRIVER', [$this->string('database-uuids')])),
            $this->assoc('database', $this->envFuncCall('DB_CONNECTION', [$this->string('mysql')])),
            $this->assoc('table', 'failed_jobs')
        ]), 'Failed Queue Jobs', 'These options configure the behavior of failed queue job logging so you can control which database and table are used to store the jobs that have failed. You may change them to any database / table you wish.');
    }


}
